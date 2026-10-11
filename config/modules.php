<?php

// ── Premium module registry ─────────────────────────────────────────────
// Single source of truth for the middleware-gated premium modules. Each key
// is (a) sellable as a Plan feature (superadmin plan editor), (b) togglable
// per-tenant by superadmin, (c) enforced by the `module:<key>` route
// middleware (see App\Http\Middleware\EnsureModuleEnabled), and (d) listed
// on the pricing / plan-selection pages.
//
// Access is resolved in App\Models\Tenant::hasModuleEnabled():
//   settings['modules'][key] === true  → force ON  (superadmin override)
//   settings['modules'][key] === false → force OFF (superadmin override)
//   key absent                         → inherit from Plan.features[key]
//
// NOTE: `whatsapp`, `reports` and `social_leads` are deliberately NOT here —
// they are soft, view-only plan flags with no `module:` middleware.
//
// `icon` keys map to the shared $icons path map in
// resources/views/superadmin/tenants/show.blade.php.
//
// `tier` = the lowest paid plan that bundles this module by default. Consumed
// only by Database\Seeders\PlanSeeder to keep the module→plan mapping DRY:
//   'starter'  → included from the Starter plan up (Starter, Pro, Business, Enterprise)
//   'pro'      → included from the Pro plan up (Pro, Business, Enterprise)
//   'business' → included from the Business plan up (Business, Enterprise)
// Superadmin can still add/remove any module on any plan in the plan editor.

return [
    'manufacturing' => [
        'label' => 'Manufacturing',
        'desc'  => 'Work Orders + Product Batches (production tracking)',
        'blurb' => 'Work orders, BOM costing & production batches',
        'icon'  => 'cube',
        'tier'  => 'business',
    ],
    'service' => [
        'label' => 'Service Catalog',
        'desc'  => 'Service line items in Quotations / Invoices',
        'blurb' => 'Service line items on quotations & invoices',
        'icon'  => 'wrench',
        'tier'  => 'pro',
    ],
    'subscriptions' => [
        'label' => 'Service Subscriptions',
        'desc'  => 'Customer subscription tracking, expiry reminders, renewals',
        'blurb' => 'Recurring customer plans, renewals & reminders',
        'icon'  => 'renew',
        'tier'  => 'pro',
    ],
    'appointments' => [
        'label' => 'Appointments / Booking',
        'desc'  => 'Public online booking link + staff appointment management',
        'blurb' => 'Public online booking + staff scheduling',
        'icon'  => 'calendar',
        'tier'  => 'starter',
    ],
    'time_tracking' => [
        'label' => 'Time Tracking',
        'desc'  => 'Task timers, billable hours, convert time to invoices',
        'blurb' => 'Task timers & billable hours',
        'icon'  => 'clock',
        'tier'  => 'starter',
    ],
    'tickets' => [
        'label' => 'Tickets / Helpdesk',
        'desc'  => 'Customer support tickets, public submission form, reply thread',
        'blurb' => 'Support tickets with a public submission form',
        'icon'  => 'chat',
        'tier'  => 'starter',
    ],
    'loyalty' => [
        'label' => 'Customer Loyalty',
        'desc'  => 'Points, tiers, tenant-set earn/redeem rules, auto tier tagging',
        'blurb' => 'Points, tiers, rewards & win-back campaigns',
        'icon'  => 'gift',
        'tier'  => 'business',
    ],
    'whatsapp_inbox' => [
        'label' => 'WhatsApp Team Inbox',
        'desc'  => 'Shared inbox for customer WhatsApp chats — team replies, unread tracking, resolve/reopen',
        'blurb' => 'One WhatsApp number, whole team replying from a shared inbox',
        'icon'  => 'chat',
        'tier'  => 'pro',
    ],
    'customer_portal' => [
        'label' => 'Customer Portal / Wallet',
        'desc'  => 'Global customer login + cross-shop loyalty wallet, QR counter redemption. Requires the Customer Loyalty module to also be ON.',
        'blurb' => "One login, every shop's points & stamps in one wallet",
        'icon'  => 'wallet',
        'tier'  => 'business',
    ],
];
