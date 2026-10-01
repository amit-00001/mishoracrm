<?php

namespace App\Http\Controllers;

use App\Events\WhatsappMessageReceived;
use App\Models\TenantWhatsappAccount;
use App\Models\WhatsappMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// The one URL Milan CRM calls whenever something happens on any of your tenants' WhatsApp numbers:
// a customer wrote, a message was delivered/read, a number got connected, a template was approved.
//
// Rules the gateway follows (so this class does the same on its side):
//   * every call is signed — we reject anything not signed with your webhook secret;
//   * the same event can arrive more than once (retries) — we process each event id once;
//   * answer 2xx quickly (within 8 seconds) — do the slow work in a queued listener.
class MilanWhatsappWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->assertSigned($request);

        $event = $request->json()->all();
        abort_if(empty($event['id']) || empty($event['event']) || !isset($event['workspace']['id']), 422, 'Malformed event.');

        // Dedupe + handle in one transaction: if handling throws, the "seen" row is rolled back too,
        // so the gateway's retry gets a second chance instead of being ignored as a duplicate.
        DB::transaction(function () use ($event) {
            $isNew = DB::table('whatsapp_webhook_events')->insertOrIgnore([
                'event_id' => $event['id'], 'event' => $event['event'], 'created_at' => now(), 'updated_at' => now(),
            ]);

            if ($isNew) {
                $this->handle((string) $event['workspace']['id'], $event['event'], $event['data'] ?? []);
            }
        });

        return response()->json(['ok' => true]);
    }

    private function handle(string $tenantId, string $event, array $data): void
    {
        switch ($event) {
            case 'account.connected':
                TenantWhatsappAccount::forTenant($tenantId)->update([
                    'connected'            => true,
                    'display_phone_number' => $data['display_phone_number'] ?? null,
                    'verified_name'        => $data['verified_name'] ?? null,
                    'last_warning'         => !empty($data['warnings']) ? implode(' ', $data['warnings']) : null,
                    'connected_at'         => now(),
                ]);
                break;

            case 'message.received':
                $message = WhatsappMessage::syncFromGateway($tenantId, $data['message']);
                // After commit, so a queued listener never runs before the message row exists.
                DB::afterCommit(fn() => WhatsappMessageReceived::dispatch($tenantId, $message));
                break;

            case 'message.status':       // sent -> delivered -> read, or failed (with the reason)
                WhatsappMessage::syncFromGateway($tenantId, $data['message']);
                break;

            case 'template.status':      // Meta approved / rejected a template: $data['name'], ['status'], ['reason']
            case 'account.update':       // quality / account notices from Meta
                Log::info("WhatsApp gateway {$event}", ['tenant' => $tenantId, 'data' => $data]);
                break;
        }
    }

    // X-Gateway-Signature = "sha256=" + HMAC_SHA256(secret, X-Gateway-Timestamp + "." + raw body)
    private function assertSigned(Request $request): void
    {
        $secret    = (string) config('services.milan_wa.secret');
        $timestamp = (string) $request->header('X-Gateway-Timestamp');

        $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $request->getContent(), $secret);

        abort_unless($secret !== '' && hash_equals($expected, (string) $request->header('X-Gateway-Signature')), 401, 'Bad signature.');
        abort_if(abs(time() - (int) $timestamp) > 300, 401, 'Stale request.');   // blocks replays of old calls
    }
}
