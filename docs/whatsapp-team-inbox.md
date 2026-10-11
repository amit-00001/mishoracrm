# WhatsApp Team Inbox — Guide, Seller Notes & Rollout Tracker

Living document. Update the **Progress tracker** after every step and read it first when resuming.

## Progress tracker

| Step | What | Status |
|---|---|---|
| 1 | Shared Team Inbox — chat list, conversation, reply (text + file), 24-hour window + template fallback, unread, resolve/reopen, link to Lead/Contact, live refresh | **Built — needs `php artisan migrate` and a live test** |
| 1a | Plan/superadmin gating (`whatsapp_inbox` module) + role permission (`whatsapp.inbox`) | Built |
| 2 | Assign conversations, auto-assign, Quick Replies, internal notes, more permissions | Not started (`assigned_to` column already exists) |
| 3 | Team performance, broadcast segmentation, campaign dashboard | Not started |
| 4 | Product Catalog Sharing | Deferred (needs Meta Commerce Manager catalog + `catalog_management` permission) |

Known limits of step 1: inbound images/documents show as `[image]` / `[document]` text (the file itself is not downloaded yet); chat is not paused while an agent is replying (the chatbot may still answer keywords); a chat only appears once the customer has written to you.

---

## 1. How a tenant gets the Inbox (superadmin / plans)

The Inbox is a **premium module** named `whatsapp_inbox` ("WhatsApp Team Inbox"), registered in `config/modules.php`. A tenant can use it only if **both** are true:

1. **Module access** — either their **plan includes it**, or superadmin has **switched it on for that tenant**.
2. **Role permission** — the user's role has `whatsapp.inbox` (admins and staff get it by default; to send replies they also need `whatsapp.send`).

Without module access the "WA Inbox" menu item is hidden and the URL returns *403 – module is not enabled for your account*.

**Option A — sell it as part of a plan**
Superadmin → **Plans** → edit a plan → tick **WhatsApp Team Inbox** → save. Every tenant on that plan gets it. By default the seeder (`PlanSeeder`) bundles it from the **Pro** plan upward (`tier => pro` in `config/modules.php`); re-run the seeder or tick it by hand for existing plans.

**Option B — grant or revoke it for one tenant**
Superadmin → **Tenants** → open the tenant → **Modules** → toggle **WhatsApp Team Inbox**. This override beats the plan in both directions (grant it on a lower plan as an add-on, or switch it off on a plan that includes it). "Follow plan" removes the override.

**Option C — take it away from some staff**
Tenant owner → **Settings → Roles** → untick `whatsapp.inbox` (view) or `whatsapp.send` (reply) for that role.

The tenant also needs **WhatsApp connected** (WhatsApp → API Settings); otherwise the Inbox shows a warning and cannot send.

## 2. Using the Inbox (tenant staff)

Menu: **Messages → WA Inbox**.

- **Left side** — your chats, newest first. Tabs: **Open**, **Unread**, **Resolved**, **All** (with counts). Search by name or number. A green badge shows unread messages. The list refreshes itself every few seconds.
- **Right side** — the conversation. Customer messages are on the left, yours on the right (green) with the name of the teammate who sent it. A red bubble means the message failed, with the reason.
- **Reply** — type and press **Enter** (Shift+Enter for a new line). Use 📎 to attach an image, PDF, Word or Excel file (up to 16 MB).
- **Mark resolved / Reopen** — resolved chats leave the Open tab. If the customer writes again, the chat reopens by itself.
- **Lead / Contact link** — if the customer's number matches a Lead or Contact, the name shows in the header with a link to the record.

**The 24-hour rule.** WhatsApp allows free typing only within 24 hours of the customer's last message. After that the box is replaced by an **approved template** picker (available when your number is linked through the WhatsApp gateway). Fill the template fields and send — the customer's reply then reopens the 24-hour window. If no templates are available, ask the customer to message you first.

Everything sent from the Inbox also appears in **WhatsApp → Logs**.

## 3. Seller notes

**One-line pitch:** "Your whole team answers customers from one WhatsApp number — nobody shares a phone, nothing gets missed."

**Sell it to:** any customer already using WhatsApp for enquiries — real estate, coaching, clinics, salons, service/AMC companies — where more than one person answers chats.

**Demo (3 minutes):** (1) send a message to the demo number from your phone, (2) show it land in the Inbox with the unread badge, (3) reply from the CRM and show it on the phone, (4) mark resolved, (5) show the Lead name auto-linked.

**Pricing / packaging:** included from **Pro** by default; can be added to a Starter tenant by superadmin as a per-tenant add-on (Tenant → Modules).

**Don't promise yet (coming in later steps):** assigning chats to a specific agent, saved quick replies, internal notes, agent performance reports, segment-based broadcasts, product catalogs. Be honest that customers' incoming photos/documents show only as a label for now.

**Objections:** *"Can two people reply at once?"* — yes, both see new messages within seconds, and each reply is stamped with the sender's name. *"What if the chatbot is on?"* — the chatbot still answers its keywords; staff can reply on top.

## 4. Developer notes

- **Data:** table `whatsapp_conversations` (one row per customer number per tenant: `wa_id` digits-only, `status` open|resolved, `unread_count`, `last_message_preview`, `last_message_at`, `last_inbound_at`, `lead_id`, `contact_id`, `assigned_to`). Messages are **not copied** — they are the existing `whatsapp_logs` rows (`status='received'` = customer; anything else = us), matched on digits-only phone (`WhatsappConversation::messages()`).
- **Writes:** `WhatsappInboundService::handle()` calls `WhatsappConversation::touchFor()` for every inbound message — shared by the direct-Meta and the gateway webhook, so both feed the Inbox. This runs for **all tenants**, regardless of module access, so history exists the day a tenant is switched on. Outbound replies are logged by `WhatsappInboxController::reply()`.
- **Gating:** routes in `routes/web.php` under `whatsapp/inbox` use `module:whatsapp_inbox` + `permission:whatsapp.inbox` (reply also `permission:whatsapp.send`). Sidebar link checks both. Plan editor, tenant toggle and PlanSeeder are driven from `config/modules.php`.
- **Permission rollout:** migration `2026_10_13_100000_create_whatsapp_conversations_table` creates `whatsapp.inbox` and gives it to the `tenant_admin` and `staff` roles; the seeder also lists it for fresh installs. Custom tenant roles must be ticked by the owner.
- **Live refresh:** the page polls `GET whatsapp/inbox/poll` every 6 s (list + messages after the last id). No websockets.
- **Deploy:** `php artisan migrate`, then (to bundle it into the stock plans) `php artisan db:seed --class=PlanSeeder`.
- **Files:** `app/Models/WhatsappConversation.php`, `app/Http/Controllers/Web/Tenant/WhatsappInboxController.php`, `resources/views/tenant/whatsapp/inbox.blade.php`, sidebar entry in `resources/views/components/sidebar.blade.php`.
