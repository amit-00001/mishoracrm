<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Reset cached roles/permissions ────────────────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─────────────────────────────────────────────────────────
        // PERMISSIONS — Developer define karta hai
        // Format: "module.action"
        // Tenant admin sirf inhe roles ko assign kar sakta hai
        // Naye permissions add karna = developer ka kaam
        // ─────────────────────────────────────────────────────────
        $permissions = [

            // ── Leads ─────────────────────────────────────────────
            'leads.view_own',         // Sirf apne assigned leads
            'leads.view_all',         // Sab tenant ke leads
            'leads.create',
            'leads.edit_own',         // Sirf apne assigned leads edit
            'leads.edit_all',         // Koi bhi lead edit karo
            'leads.delete',
            'leads.assign',           // Kisi bhi staff ko assign karo
            'leads.convert',          // Lead ko contact mein convert karo
            'leads.export',
            'leads.import',
            'leads.merge',            // Duplicate leads merge karo

            // ── Contacts ─────────────────────────────────────────
            'contacts.view_own',
            'contacts.view_all',
            'contacts.create',
            'contacts.edit_own',
            'contacts.edit_all',
            'contacts.delete',
            'contacts.export',
            'contacts.import',
            'contacts.merge',         // Duplicate contacts merge karo

            // ── Deals ─────────────────────────────────────────────
            'deals.view_own',
            'deals.view_all',
            'deals.create',
            'deals.edit_own',
            'deals.edit_all',
            'deals.delete',
            'deals.export',

            // ── Follow-ups ────────────────────────────────────────
            'followups.view_own',
            'followups.view_all',
            'followups.create',
            'followups.edit_own',
            'followups.edit_all',
            'followups.delete',

            // ── Tasks ─────────────────────────────────────────────
            'tasks.view_own',
            'tasks.view_all',
            'tasks.create',
            'tasks.edit_own',
            'tasks.edit_all',
            'tasks.delete',

            // ── Quotations ────────────────────────────────────────
            'quotations.view_own',
            'quotations.view_all',
            'quotations.create',
            'quotations.edit',
            'quotations.delete',
            'quotations.send',
            'quotations.export',

            // ── Invoices ─────────────────────────────────────────
            'invoices.view_own',
            'invoices.view_all',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',
            'invoices.send',
            'invoices.record_payment',
            'invoices.export',

            // ── Vendors ───────────────────────────────────────────
            'vendors.view',
            'vendors.create',
            'vendors.edit',
            'vendors.delete',

            // ── Purchase Requests ─────────────────────────────────
            'purchase_requests.view_own',
            'purchase_requests.view_all',
            'purchase_requests.create',
            'purchase_requests.edit',
            'purchase_requests.approve',
            'purchase_requests.delete',

            // ── Purchase Orders ───────────────────────────────────
            'purchase_orders.view_own',
            'purchase_orders.view_all',
            'purchase_orders.create',
            'purchase_orders.edit',
            'purchase_orders.delete',
            'purchase_orders.send',
            'purchase_orders.receive',
            'purchase_orders.export',

            // ── Vendor Bills (Accounts Payable) ───────────────────
            'vendor_bills.view_own',
            'vendor_bills.view_all',
            'vendor_bills.create',
            'vendor_bills.edit',
            'vendor_bills.delete',
            'vendor_bills.record_payment',

            // ── Work Orders ───────────────────────────────────────
            'work_orders.view_own',
            'work_orders.view_all',
            'work_orders.create',
            'work_orders.edit',
            'work_orders.manage',
            'work_orders.delete',

            // ── Service Subscriptions ─────────────────────────────
            'subscriptions.view',
            'subscriptions.create',
            'subscriptions.edit',
            'subscriptions.delete',
            'subscriptions.cancel',
            'subscriptions.renew',
            'subscriptions.send_reminder',
            'subscriptions.manage_templates', // reminder channels, timing, message templates

            // ── Appointments / Booking ─────────────────────────────
            'appointments.view',
            'appointments.create',
            'appointments.edit',
            'appointments.cancel',
            'appointments.manage_settings', // business hours, slot duration, bookable services

            // ── Time Tracking ──────────────────────────────────────
            'time_entries.view',
            'time_entries.create',
            'time_entries.edit',
            'time_entries.delete',
            'time_entries.convert_to_invoice',

            // ── Tickets / Helpdesk ─────────────────────────────────
            'tickets.view_own',
            'tickets.view_all',
            'tickets.create',
            'tickets.reply',
            'tickets.edit',
            'tickets.delete',

            // ── Customer Loyalty ──────────────────────────────────
            'loyalty.view',           // See members, points, tiers, ledger
            'loyalty.manage',         // Edit rules, manually adjust points
            'loyalty.stamp',          // Counter: scan a customer's wallet QR, add stamps, redeem

            // ── Staff ─────────────────────────────────────────────
            'staff.view',
            'staff.create',
            'staff.edit',
            'staff.delete',
            'staff.activate_deactivate',

            // ── Departments ───────────────────────────────────────
            'departments.view',
            'departments.create',
            'departments.edit',
            'departments.delete',

            // ── WhatsApp ──────────────────────────────────────────
            'whatsapp.send',
            'whatsapp.bulk_send',
            'whatsapp.view_logs',
            'whatsapp.manage_templates',
            'whatsapp.inbox',          // Team Inbox (also needs the whatsapp_inbox module)
            'whatsapp.assign',         // Inbox: assign chats, auto-assign settings
            'whatsapp.view_all',       // Inbox: see every chat (without it: own + unassigned only)
            'whatsapp.quick_replies',  // Inbox: manage saved quick replies

            // ── Email ─────────────────────────────────────────────
            'email.send',
            'email.bulk_send',
            'email.view_logs',
            'email.manage_templates',

            // ── Reports ───────────────────────────────────────────
            'reports.view_basic',     // Own stats
            'reports.view_all',       // Full team reports
            'reports.export',

            // ── Settings ─────────────────────────────────────────
            'settings.company',       // Company info, logo
            'settings.billing',       // Plan & subscription
            'settings.custom_fields', // Custom field config
            'settings.roles',         // Roles management (view only for non-admin)

            // ── Notifications ─────────────────────────────────────
            'notifications.view',
            'notifications.manage_preferences',

            // ── Audit Logs ────────────────────────────────────────
            'audit_logs.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ─────────────────────────────────────────────────────────
        // SYSTEM ROLES — Fixed, not deletable by tenant
        // ─────────────────────────────────────────────────────────

        // SuperAdmin — everything (no permission check)
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        // Tenant Admin — everything within their tenant
        $tenantAdmin = Role::firstOrCreate(['name' => 'tenant_admin', 'guard_name' => 'web']);
        $tenantAdmin->forceFill(['description' => 'Full access to every module and setting (platform-managed).'])->save();
        $tenantAdmin->syncPermissions(Permission::all());

        // Staff — basic access (tenant admin can create custom roles for more)
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->forceFill(['description' => 'Standard access for team members. Each workspace can customise its own copy.'])->save();
        $staff->syncPermissions([
            'leads.view_own',
            'leads.create',
            'leads.edit_own',
            'contacts.view_own',
            'contacts.create',
            'contacts.edit_own',
            'deals.view_own',
            'followups.view_own',
            'followups.create',
            'followups.edit_own',
            'tasks.view_own',
            'tasks.create',
            'tasks.edit_own',
            'purchase_requests.view_own',
            'purchase_requests.create',
            'whatsapp.send',
            'whatsapp.inbox',
            'whatsapp.assign',
            'whatsapp.view_all',
            'email.send',
            'reports.view_basic',
            'notifications.view',
            'notifications.manage_preferences',
            'subscriptions.view',
            'subscriptions.create',
            'subscriptions.send_reminder',
            'appointments.view',
            'appointments.create',
            'appointments.edit',
            'appointments.cancel',
            'time_entries.view',
            'time_entries.create',
            'time_entries.edit',
            'tickets.view_own',
            'tickets.create',
            'tickets.reply',
            'tickets.edit',
        ]);

        $this->command->info('✅ Permissions seeded: ' . Permission::count());
        $this->command->info('✅ Roles seeded: superadmin, tenant_admin, staff');
    }
}