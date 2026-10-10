<?php

use App\Jobs\SyncIndiaMartLeadsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ── IndiaMART lead sync — every 30 minutes ────────────────────────
Schedule::job(new SyncIndiaMartLeadsJob)->everyThirtyMinutes();

// ── Lead SLA breach check — every 15 minutes ──────────────────────
Schedule::command('leads:check-sla')->everyFifteenMinutes();

// ── Follow-up due/overdue reminders — every 5 minutes ─────────────
Schedule::command('followups:remind')->everyFiveMinutes();

// ── WhatsApp Gateway dedupe ids — the gateway retries for 6h at most,
// so a fortnight of event ids is plenty — daily ───────────────────
Schedule::call(fn () => DB::table('whatsapp_webhook_events')->where('created_at', '<', now()->subDays(14))->delete())
    ->name('whatsapp-gateway:prune-events')
    ->dailyAt('03:30');

// ── Invoice payment due/overdue reminders — daily ─────────────────
Schedule::command('invoices:remind-payments')->daily();

// ── Task due-date reminders — daily ────────────────────────────────
Schedule::command('tasks:remind')->dailyAt('09:00');

// ── Product batch expiry alerts — daily ────────────────────────────
Schedule::command('products:check-batch-expiry')->dailyAt('08:00');

// ── Auto-renew subscriptions opted into auto-renew, before the expiry
// alert runs so a genuinely-expiring one only fires for non-auto-renew
// subscriptions ───────────────────────────────────────────────────
Schedule::command('subscriptions:auto-renew')->dailyAt('06:00');

// ── Service subscription expiry alerts — daily ─────────────────────
Schedule::command('subscriptions:remind-expiry')->dailyAt('08:30');

// ── Platform (workspace) subscription lifecycle — expire lapsed plans
// and warn tenant admins before their own CRM plan runs out — daily ──
Schedule::command('subscriptions:check-platform')->dailyAt('07:30');

// ── Upcoming appointment reminders — hourly (24h-ahead window) ─────
Schedule::command('appointments:remind-upcoming')->hourly();

// ── Same-day appointment reminders — once every morning ─────────────
Schedule::command('appointments:remind-today')->dailyAt('07:00');

// ── Auto-mark past appointments as no-show if staff never updated
// their status — hourly ─────────────────────────────────────────────
Schedule::command('appointments:mark-no-show')->hourly();

// ── Ticket SLA breach check (no staff reply within priority window) —
// every 15 minutes, same cadence as the lead SLA check ─────────────
Schedule::command('tickets:check-sla')->everyFifteenMinutes();

// ── Loyalty points expiry — lapse earn lots past their expiry date — daily
Schedule::command('loyalty:expire-points')->dailyAt('02:00');

// ── Loyalty points-expiry reminders (nudge before they lapse) — daily ──
Schedule::command('loyalty:expiry-reminders')->dailyAt('09:30');

// ── Loyalty birthday / anniversary rewards — every morning ─────────
Schedule::command('loyalty:occasion-offers')->dailyAt('07:15');

// ── Loyalty win-back digest to tenant admins — weekly (Monday) ─────
Schedule::command('loyalty:win-back-digest')->weeklyOn(1, '08:00');

// ── Customer portal — soft-delete junk accounts (never logged in, no shop link) — weekly
Schedule::command('customers:prune')->weekly();

// ── Loyalty stamp cards — reset part-filled cards idle past the tenant window — daily
Schedule::command('loyalty:expire-stamps')->dailyAt('02:30');
