# WhatsApp Team Inbox — Guide, Seller Notes & Rollout Tracker

Living document. Update the **Progress tracker** after every step and read it first when resuming.

## Progress tracker

| Step | What | Status |
|---|---|---|
| 1 | Shared Team Inbox — chat list, conversation, reply (text + file), 24-hour window + template fallback, unread, resolve/reopen, link to Lead/Contact, live refresh | **Built — needs `php artisan migrate` and a live test** |
| 1a | Plan/superadmin gating (`whatsapp_inbox` module) + role permission (`whatsapp.inbox`) | Built |
| 2 | Assign conversations, auto-assign (round-robin, off by default), Quick Replies (`/shortcut`), private internal notes, permissions `whatsapp.assign` / `whatsapp.view_all` / `whatsapp.quick_replies` | **Built — needs `php artisan migrate` and a live test** |
| 3 | Team performance, broadcast segmentation, campaign dashboard | Not started |
| 4 | Product Catalog Sharing | Deferred (needs Meta Commerce Manager catalog + `catalog_management` permission) |

Known limits (steps 1–2): inbound images/documents show as `[image]` / `[document]` text (the file itself is not downloaded yet); chat is not paused while an agent is replying (the chatbot may still answer keywords); notes cannot be edited or deleted; auto-assign does not look at who is online or on leave; a chat only appears once the customer has written to you.

---

## 1. How a tenant gets the Inbox (superadmin / plans)

The Inbox is a **premium module** named `whatsapp_inbox` ("WhatsApp Team Inbox"), registered in `config/modules.php`. A tenant can use it only if **both** are true:

1. **Module access** — either their **plan includes it**, or superadmin has **switched it on for that tenant**.
2. **Role permission** — the user's role has `whatsapp.inbox` (admins and staff get it by default; to send replies they also need `whatsapp.send`).

Step 2 adds three more role permissions (all need the module to be on first):

| Permission | What it allows | Default |
|---|---|---|
| `whatsapp.view_all` | See every chat. Without it a person sees only chats assigned to them plus unassigned ones. | Admin + Staff |
| `whatsapp.assign` | Assign / reassign chats, and change the auto-assign settings | Admin + Staff |
| `whatsapp.quick_replies` | Add, edit and delete quick replies (everyone with inbox access can *use* them) | Admin only |

To make staff see only their own chats, the owner unticks `whatsapp.view_all` for the Staff role in Settings → Roles.

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

**Assigning chats.** Each chat shows who owns it (👤 name in the list, a dropdown in the header). Pick a teammate in the header dropdown to hand the chat over, or "Unassigned" to put it back in the pool. Replying to a chat nobody owns makes it yours automatically. Tabs: **Mine** (open chats assigned to you) and **Unassigned** (open chats nobody owns yet).

**Auto-assign (owner).** Click **Auto-assign** at the top, tick "Share new chats…" and choose the people. New chats then go to them in turn. A customer who writes again stays with the same person. It is **off by default**; manual assignment always works.

**Quick replies.** Click **⚡ Quick replies** (admins) to save common answers with a short name, e.g. `price`. In the reply box type `/` and the list appears — use ↑ ↓ and Enter, or click. Write `{{name}}` in the text to insert the customer's first name.

**Private notes.** Switch the reply box to **Private note**, write, and press Enter. The note appears in the chat as a yellow card and is **never sent to the customer** — use it for hand-over details ("customer wants the 3BHK, call after 5").

Everything sent from the Inbox also appears in **WhatsApp → Logs**.

## 3. Seller notes

**One-line pitch:** "Your whole team answers customers from one WhatsApp number — nobody shares a phone, nothing gets missed."

**Sell it to:** any customer already using WhatsApp for enquiries — real estate, coaching, clinics, salons, service/AMC companies — where more than one person answers chats.

**Demo (3 minutes):** (1) send a message to the demo number from your phone, (2) show it land in the Inbox with the unread badge, (3) assign it to a teammate, (4) reply with a `/quick reply` and show it on the phone, (5) add a private note, (6) mark resolved.

**Pricing / packaging:** included from **Pro** by default; can be added to a Starter tenant by superadmin as a per-tenant add-on (Tenant → Modules).

**Don't promise yet (coming in later steps):** agent performance reports (response times), segment-based broadcasts, campaign analytics, product catalogs. Be honest that customers' incoming photos/documents show only as a label for now.

**Objections:** *"Can two people reply at once?"* — yes, both see new messages within seconds, and each reply is stamped with the sender's name. *"Can I stop staff seeing each other's chats?"* — yes, untick `whatsapp.view_all` for that role; they then see only their own and unassigned chats. *"Can chats be shared automatically?"* — yes, auto-assign (round-robin) among the people you choose. *"What if the chatbot is on?"* — the chatbot still answers its keywords; staff can reply on top.

## 4. Developer notes

- **Data:** table `whatsapp_conversations` (one row per customer number per tenant: `wa_id` digits-only, `status` open|resolved, `unread_count`, `last_message_preview`, `last_message_at`, `last_inbound_at`, `lead_id`, `contact_id`, `assigned_to`). Messages are **not copied** — they are the existing `whatsapp_logs` rows (`status='received'` = customer; anything else = us), matched on digits-only phone (`WhatsappConversation::messages()`).
- **Writes:** `WhatsappInboundService::handle()` calls `WhatsappConversation::touchFor()` for every inbound message — shared by the direct-Meta and the gateway webhook, so both feed the Inbox. This runs for **all tenants**, regardless of module access, so history exists the day a tenant is switched on. Outbound replies are logged by `WhatsappInboxController::reply()`.
- **Gating:** routes in `routes/web.php` under `whatsapp/inbox` use `module:whatsapp_inbox` + `permission:whatsapp.inbox` (reply also `permission:whatsapp.send`). Sidebar link checks both. Plan editor, tenant toggle and PlanSeeder are driven from `config/modules.php`.
- **Permission rollout:** migration `2026_10_13_100000_create_whatsapp_conversations_table` creates `whatsapp.inbox` and gives it to the `tenant_admin` and `staff` roles; the seeder also lists it for fresh installs. Custom tenant roles must be ticked by the owner.
- **Step 2 data:** `whatsapp_conversations.assigned_to` (user id), `whatsapp_quick_replies` (`shortcut` unique per tenant, `body`), `whatsapp_conversation_notes` (private, per conversation), and on `whatsapp_settings`: `inbox_auto_assign`, `inbox_auto_assign_users` (json ids), `inbox_last_assigned_to` (round-robin cursor). Migration `2026_10_14_100000_add_team_features_to_whatsapp_inbox` also creates the three permissions and grants them to the `tenant_admin`/`staff` roles.
- **Visibility rule:** `WhatsappInboxController::scoped()` — without `whatsapp.view_all`, queries add `assigned_to = me OR assigned_to IS NULL`. It is applied to the list, counts, and every single-chat lookup.
- **Auto-assign:** `WhatsappConversation::touchFor()` calls `nextAutoAssignee()` for an inbound message on an unassigned chat; it picks the next active user id after `inbox_last_assigned_to` from the saved list.
- **Notes in the thread:** `timeline()` merges messages and notes sorted by `ts`; the page keeps two cursors (`after`, `after_note`) when polling.
- **Live refresh:** the page polls `GET whatsapp/inbox/poll` every 6 s (list + messages after the last id). No websockets.
- **Deploy:** `php artisan migrate`, then (to bundle it into the stock plans) `php artisan db:seed --class=PlanSeeder`.
- **Files:** `app/Models/WhatsappConversation.php`, `app/Http/Controllers/Web/Tenant/WhatsappInboxController.php`, `resources/views/tenant/whatsapp/inbox.blade.php`, sidebar entry in `resources/views/components/sidebar.blade.php`.
