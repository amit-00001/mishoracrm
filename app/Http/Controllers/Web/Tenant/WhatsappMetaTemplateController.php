<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\WhatsappSetting;
use App\Services\WhatsappGatewayClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Meta-approved message templates, kept on the tenant's own WhatsApp Business
// Account and managed through the WhatsApp Gateway. Separate from the local
// "WhatsApp Templates" (plain text snippets the CRM fills in itself): only a
// Meta template can start a conversation or reply after the 24-hour window.
class WhatsappMetaTemplateController extends Controller
{
    private const LANGUAGES = [
        'en' => 'English', 'en_US' => 'English (US)', 'en_GB' => 'English (UK)', 'hi' => 'Hindi',
        'mr' => 'Marathi', 'gu' => 'Gujarati', 'bn' => 'Bengali', 'ta' => 'Tamil', 'te' => 'Telugu',
        'kn' => 'Kannada', 'ml' => 'Malayalam', 'pa' => 'Punjabi',
    ];

    private const CATEGORIES = ['UTILITY' => 'Utility', 'MARKETING' => 'Marketing'];

    // The tenant's gateway workspace id — null unless the gateway is on and
    // this tenant's number is linked through it.
    private function workspaceId(): ?string
    {
        $settings = WhatsappSetting::forTenant(auth()->user()->tenant_id);

        return ($settings->viaGateway() && $settings->is_connected) ? $settings->gateway_workspace_id : null;
    }

    private function unavailable(): RedirectResponse
    {
        return redirect()->route('tenant.whatsapp.templates')
            ->with('error', 'WhatsApp message templates are available once WhatsApp is connected through the gateway.');
    }

    public function index(): View|RedirectResponse
    {
        if (!$workspaceId = $this->workspaceId()) {
            return $this->unavailable();
        }

        $result = WhatsappGatewayClient::templates($workspaceId, ['limit' => 100]);

        return view('tenant.whatsapp.meta-templates', [
            'templates'  => $result['ok'] ? $result['data'] : [],
            'loadError'  => $result['ok'] ? null : $result['error'],
            'languages'  => self::LANGUAGES,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (!$workspaceId = $this->workspaceId()) {
            return $this->unavailable();
        }

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'language'  => ['required', 'in:' . implode(',', array_keys(self::LANGUAGES))],
            'category'  => ['required', 'in:' . implode(',', array_keys(self::CATEGORIES))],
            'body'      => ['required', 'string', 'max:1024'],
            'examples'  => ['nullable', 'array'],
            'examples.*' => ['nullable', 'string', 'max:200'],
        ], [
            'name.regex' => 'Template name can only use lowercase letters, numbers and underscores.',
        ]);

        // {{1}}, {{2}}, … must run in order with no gaps, and Meta wants a
        // sample value for each so its reviewers can see what the message looks like.
        preg_match_all('/\{\{(\d+)\}\}/', $data['body'], $matches);
        $found    = array_unique(array_map('intval', $matches[1]));
        sort($found);
        $expected = $found ? range(1, count($found)) : [];

        if ($found !== $expected) {
            return back()->withInput()->with('error', 'Number the variables in order: {{1}}, {{2}}, {{3}} … with none skipped.');
        }

        $examples = array_map('trim', array_slice(array_values($data['examples'] ?? []), 0, count($found)));
        if (count($found) && (count($examples) < count($found) || in_array('', $examples, true))) {
            return back()->withInput()->with('error', 'Add a sample value for every variable — Meta needs them to review the template.');
        }

        $result = WhatsappGatewayClient::createTemplate($workspaceId, $data['name'], $data['language'], $data['category'], $data['body'], $examples);

        if (!$result['ok']) {
            return back()->withInput()->with('error', $result['error']);
        }

        return redirect()->route('tenant.whatsapp.meta-templates')
            ->with('success', 'Template submitted to Meta for review. It can be used as soon as it shows Approved.');
    }

    public function destroy(string $name): RedirectResponse
    {
        if (!$workspaceId = $this->workspaceId()) {
            return $this->unavailable();
        }

        $result = WhatsappGatewayClient::deleteTemplate($workspaceId, $name);

        return $result['ok']
            ? back()->with('success', 'Template deleted.')
            : back()->with('error', $result['error']);
    }
}
