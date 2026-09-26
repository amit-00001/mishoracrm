<aside class="sidebar" id="sidebar">

    {{-- ── Logo ─────────────────────────────────────────────────── --}}
    <div class="sb-header">
        @include('components.brand-logo', ['h' => 24])
    </div>

    {{-- ── Nav body ─────────────────────────────────────────────── --}}
    <div class="sb-body">

    @if(auth()->user()?->user_type === 'superadmin')
        {{-- ════════════════ SUPERADMIN NAV ════════════════ --}}
        <div class="sb-section-label">Super Admin</div>

        <a href="{{ route('superadmin.dashboard') }}"
           class="sb-item {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                </svg>
            </span>
            <span class="sb-label">Dashboard</span>
        </a>

        <div class="sb-section-label">Management</div>

        <a href="{{ route('superadmin.tenants.index') }}"
           class="sb-item {{ request()->routeIs('superadmin.tenants.*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                </svg>
            </span>
            <span class="sb-label">Tenants</span>
        </a>

        <a href="{{ route('superadmin.customers.index') }}"
           class="sb-item {{ request()->routeIs('superadmin.customers.*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </span>
            <span class="sb-label">Portal Customers</span>
        </a>

        <a href="{{ route('superadmin.plans.index') }}"
           class="sb-item {{ request()->routeIs('superadmin.plans.*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                </svg>
            </span>
            <span class="sb-label">Plans</span>
        </a>

        <a href="{{ route('superadmin.billing-profile.edit') }}"
           class="sb-item {{ request()->routeIs('superadmin.billing-profile.*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                </svg>
            </span>
            <span class="sb-label">Billing Profile</span>
        </a>

        <a href="{{ route('superadmin.coupons.index') }}"
           class="sb-item {{ request()->routeIs('superadmin.coupons.*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z"/>
                </svg>
            </span>
            <span class="sb-label">Coupons</span>
        </a>

        <a href="{{ route('superadmin.contact-enquiries.index') }}"
           class="sb-item {{ request()->routeIs('superadmin.contact-enquiries.*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
            </span>
            <span class="sb-label">
                Sales Enquiries
                @php $newEnquiries = \App\Models\ContactEnquiry::where('status', 'new')->count(); @endphp
                @if($newEnquiries > 0)
                    <span style="margin-left:auto;background:var(--red);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;">{{ $newEnquiries }}</span>
                @endif
            </span>
        </a>

        <a href="{{ route('superadmin.permissions.index') }}"
           class="sb-item {{ request()->routeIs('superadmin.permissions.*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                </svg>
            </span>
            <span class="sb-label">Roles & Permissions</span>
        </a>

        <a href="{{ route('superadmin.lead-integrations.index') }}"
           class="sb-item {{ request()->routeIs('superadmin.lead-integrations.*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
                </svg>
            </span>
            <span class="sb-label">Lead Sources</span>
        </a>

        <a href="{{ route('superadmin.error-logs.index') }}"
           class="sb-item {{ request()->routeIs('superadmin.error-logs.*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </span>
            <span class="sb-label">Error Logs
                @php $openErrors = \App\Models\ErrorLog::unresolved()->count(); @endphp
                @if($openErrors > 0)
                    <span style="margin-left:auto;background:var(--red);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;">{{ $openErrors }}</span>
                @endif
            </span>
        </a>

        <div class="sb-section-label">Automation</div>

        <a href="{{ route('superadmin.workflow-templates.index') }}"
           class="sb-item {{ request()->routeIs('superadmin.workflow-templates.*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                </svg>
            </span>
            <span class="sb-label">Templates</span>
        </a>

        <a href="{{ route('superadmin.workflow-requests.index') }}"
           class="sb-item {{ request()->routeIs('superadmin.workflow-requests.*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                </svg>
            </span>
            <span class="sb-label">
                Requests
                @php $newWfReqs = \App\Models\WorkflowRequest::where('status','new')->count(); @endphp
                @if($newWfReqs > 0)
                    <span style="margin-left:auto;background:var(--red);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;">{{ $newWfReqs }}</span>
                @endif
            </span>
        </a>

        <div class="sb-section-label">Platform</div>

        <a href="{{ route('superadmin.platform-settings.meta') }}"
           class="sb-item {{ request()->routeIs('superadmin.platform-settings.*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </span>
            <span class="sb-label">Meta App</span>
        </a>

    @else
        {{-- ════════════════ TENANT NAV ════════════════ --}}
        {{-- Main --}}
        <div class="sb-section-label">Main</div>

        <a href="{{ route('tenant.dashboard') }}"
           class="sb-item {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                </svg>
            </span>
            <span class="sb-label">Dashboard</span>
        </a>

        {{-- CRM (collapsible group) --}}
        <div class="sb-section-label">CRM</div>

        <button type="button"
                class="sb-item {{ request()->routeIs('tenant.leads.*','tenant.contacts.*','tenant.deals.*','tenant.followups.*','tenant.lead-integrations.*') ? 'sub-open' : '' }}"
                onclick="toggleSub('sub-crm', this)">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </span>
            <span class="sb-label">CRM</span>
            @if(($newLeadsCount ?? 0) > 0)
                <span class="sb-badge">{{ $newLeadsCount }}</span>
            @endif
            <span class="sb-arrow">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </span>
        </button>

        <div class="sb-sub {{ request()->routeIs('tenant.leads.*','tenant.contacts.*','tenant.deals.*','tenant.followups.*','tenant.lead-integrations.*') ? 'open' : '' }}"
             id="sub-crm">

            <a href="{{ route('tenant.leads.index') }}"
               class="sb-item {{ request()->routeIs('tenant.leads.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                </span>
                <span class="sb-label">Leads</span>
                @if(($newLeadsCount ?? 0) > 0)
                    <span class="sb-badge">{{ $newLeadsCount }}</span>
                @endif
            </a>

            <a href="{{ route('tenant.contacts.index') }}"
               class="sb-item {{ request()->routeIs('tenant.contacts.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </span>
                <span class="sb-label">Contacts</span>
            </a>

            <a href="{{ route('tenant.deals.index') }}"
               class="sb-item {{ request()->routeIs('tenant.deals.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
                    </svg>
                </span>
                <span class="sb-label">Deals</span>
            </a>

            <a href="{{ route('tenant.followups.index') }}"
               class="sb-item {{ request()->routeIs('tenant.followups.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <span class="sb-label">Follow-ups</span>
            </a>

            <a href="{{ route('tenant.lead-integrations.index') }}"
               class="sb-item {{ request()->routeIs('tenant.lead-integrations.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
                    </svg>
                </span>
                <span class="sb-label">Lead Sources</span>
            </a>
        </div>

        {{-- Work (collapsible group) --}}
        <div class="sb-section-label">Work</div>

        <button type="button"
                class="sb-item {{ request()->routeIs('tenant.tasks.*','tenant.calendar.*') ? 'sub-open' : '' }}"
                onclick="toggleSub('sub-work', this)">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <span class="sb-label">Work</span>
            <span class="sb-arrow">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </span>
        </button>

        <div class="sb-sub {{ request()->routeIs('tenant.tasks.*','tenant.calendar.*') ? 'open' : '' }}"
             id="sub-work">

            <a href="{{ route('tenant.tasks.index') }}"
               class="sb-item {{ request()->routeIs('tenant.tasks.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <span class="sb-label">Tasks</span>
            </a>

            <a href="{{ route('tenant.calendar.index') }}"
               class="sb-item {{ request()->routeIs('tenant.calendar.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                    </svg>
                </span>
                <span class="sb-label">Calendar</span>
            </a>
        </div>

        {{-- Automation --}}
        <div class="sb-section-label">Automation</div>

        <a href="{{ route('tenant.automation.index') }}"
           class="sb-item {{ request()->routeIs('tenant.automation.*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                </svg>
            </span>
            <span class="sb-label">AI Automation</span>
        </a>

        {{-- Finance (collapsible group) --}}
        <div class="sb-section-label">Finance</div>

        <button type="button"
                class="sb-item {{ request()->routeIs('quotations.*','invoices.*','products.*') ? 'sub-open' : '' }}"
                onclick="toggleSub('sub-finance', this)">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
            </span>
            <span class="sb-label">Finance</span>
            <span class="sb-arrow">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </span>
        </button>

        <div class="sb-sub {{ request()->routeIs('quotations.*','invoices.*','products.*') ? 'open' : '' }}"
             id="sub-finance">
          
            <a href="{{ route('tenant.quotations.index') }}"
               class="sb-item {{ request()->routeIs('quotations.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z"/>
                    </svg>
                </span>
                <span class="sb-label">Quotations</span>
            </a>
            <a href="{{ route('tenant.invoices.index') }}"
               class="sb-item {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                    </svg>
                </span>
                <span class="sb-label">Invoices</span>
            </a>
            <a href="{{ route('tenant.products.index') }}"
               class="sb-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                    </svg>
                </span>
                <span class="sb-label">Products</span>
            </a>
            @if(auth()->user()->tenant?->hasModuleEnabled('service'))
            <a href="{{ route('tenant.services.index') }}"
               class="sb-item {{ request()->routeIs('services.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
                    </svg>
                </span>
                <span class="sb-label">Services</span>
            </a>
            @endif
            @if(auth()->user()->tenant?->hasModuleEnabled('subscriptions'))
            <a href="{{ route('tenant.subscriptions.index') }}"
               class="sb-item {{ request()->routeIs('subscriptions.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <span class="sb-label">Subscriptions</span>
            </a>
            @endif
            @if(auth()->user()->tenant?->hasModuleEnabled('appointments'))
            <a href="{{ route('tenant.appointments.index') }}"
               class="sb-item {{ request()->routeIs('appointments.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008z"/>
                    </svg>
                </span>
                <span class="sb-label">Appointments</span>
            </a>
            @endif
            @if(auth()->user()->tenant?->hasModuleEnabled('time_tracking'))
            <a href="{{ route('tenant.time-entries.index') }}"
               class="sb-item {{ request()->routeIs('tenant.time-entries.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <span class="sb-label">Time Tracking</span>
            </a>
            @endif
            @if(auth()->user()->tenant?->hasModuleEnabled('tickets'))
            <a href="{{ route('tenant.tickets.index') }}"
               class="sb-item {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>
                    </svg>
                </span>
                <span class="sb-label">Tickets</span>
            </a>
            @endif
            @if(auth()->user()->tenant?->hasModuleEnabled('loyalty') && auth()->user()->can('loyalty.view'))
            <a href="{{ route('tenant.loyalty.index') }}"
               class="sb-item {{ request()->routeIs('loyalty.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                    </svg>
                </span>
                <span class="sb-label">Loyalty</span>
            </a>
            @endif
            @if(auth()->user()->tenant?->hasModuleEnabled('loyalty') && auth()->user()->tenant?->hasModuleEnabled('customer_portal')
                && (auth()->user()->can('loyalty.stamp') || auth()->user()->can('loyalty.manage')))
            <a href="{{ route('tenant.loyalty.counter.index') }}"
               class="sb-item {{ request()->routeIs('tenant.loyalty.counter.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5zM6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z"/>
                    </svg>
                </span>
                <span class="sb-label">Counter</span>
            </a>
            @endif
        </div>

        {{-- Purchase (collapsible group) --}}
        <div class="sb-section-label">Purchase</div>

        <button type="button"
                class="sb-item {{ request()->routeIs('purchase-requests.*','purchase-orders.*','vendors.*','vendor-bills.*','products.low-stock') ? 'sub-open' : '' }}"
                onclick="toggleSub('sub-purchase', this)">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.94-4.788 2.436-7.404.083-.436-.24-.836-.68-.836H5.106M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
                </svg>
            </span>
            <span class="sb-label">Purchase</span>
            <span class="sb-arrow">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </span>
        </button>

        <div class="sb-sub {{ request()->routeIs('purchase-requests.*','purchase-orders.*','vendors.*','vendor-bills.*','products.low-stock') ? 'open' : '' }}"
             id="sub-purchase">

            <a href="{{ route('tenant.products.low-stock') }}"
               class="sb-item {{ request()->routeIs('products.low-stock') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </span>
                <span class="sb-label">Low Stock</span>
            </a>
            <a href="{{ route('tenant.purchase-requests.index') }}"
               class="sb-item {{ request()->routeIs('purchase-requests.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75M3.75 4.5h16.5v15a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25v-15zM15 4.5V3a.75.75 0 00-.75-.75h-4.5A.75.75 0 009 3v1.5"/>
                    </svg>
                </span>
                <span class="sb-label">Purchase Requests</span>
            </a>
            <a href="{{ route('tenant.purchase-orders.index') }}"
               class="sb-item {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z"/>
                    </svg>
                </span>
                <span class="sb-label">Purchase Orders</span>
            </a>
            <a href="{{ route('tenant.vendors.index') }}"
               class="sb-item {{ request()->routeIs('vendors.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21"/>
                    </svg>
                </span>
                <span class="sb-label">Vendors</span>
            </a>
            <a href="{{ route('tenant.vendor-bills.index') }}"
               class="sb-item {{ request()->routeIs('vendor-bills.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75M3.75 4.5h16.5v15a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25v-15zM15 4.5V3a.75.75 0 00-.75-.75h-4.5A.75.75 0 009 3v1.5"/>
                    </svg>
                </span>
                <span class="sb-label">Vendor Bills</span>
            </a>
            @if(auth()->user()->tenant?->hasModuleEnabled('manufacturing'))
            <a href="{{ route('tenant.work-orders.index') }}"
               class="sb-item {{ request()->routeIs('work-orders.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.909 4.909m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
                    </svg>
                </span>
                <span class="sb-label">Work Orders</span>
            </a>
            @endif
        </div>

        {{-- Communication (collapsible group) --}}
        <div class="sb-section-label">Communication</div>

        <button type="button"
                class="sb-item {{ request()->routeIs('whatsapp.*','email.*','tenant.instagram.*') ? 'sub-open' : '' }}"
                onclick="toggleSub('sub-comm', this)">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                </svg>
            </span>
            <span class="sb-label">Messages</span>
            <span class="sb-arrow">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </span>
        </button>

        <div class="sb-sub {{ request()->routeIs('whatsapp.*','email.*','tenant.instagram.*') ? 'open' : '' }}"
             id="sub-comm">
            <a href="{{ route('tenant.whatsapp.index') }}"
               class="sb-item {{ request()->routeIs('whatsapp.*') && !request()->routeIs('whatsapp.chatbot*','whatsapp.api-settings*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 01-.923 1.785A5.969 5.969 0 006 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337z"/>
                    </svg>
                </span>
                <span class="sb-label">WhatsApp</span>
            </a>
            <a href="{{ route('tenant.whatsapp.chatbot') }}"
               class="sb-item {{ request()->routeIs('tenant.whatsapp.chatbot*','tenant.whatsapp.api-settings*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/>
                    </svg>
                </span>
                <span class="sb-label">WA Chatbot</span>
            </a>
            <a href="{{ route('tenant.email.index') }}"
               class="sb-item {{ request()->routeIs('tenant.email.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                </span>
                <span class="sb-label">Email</span>
            </a>
            @if(auth()->user()?->user_type === 'tenant_admin')
            <a href="{{ route('tenant.instagram.index') }}"
               class="sb-item {{ request()->routeIs('tenant.instagram.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
                    </svg>
                </span>
                <span class="sb-label">Instagram</span>
            </a>
            @endif
        </div>

        {{-- HR (collapsible group — Attendance has no permission gate, so the
             group itself always renders; Departments/Staff keep their own
             @can gates unchanged inside it) --}}
        <div class="sb-section-label">HR</div>

        <button type="button"
                class="sb-item {{ request()->routeIs('tenant.departments.*','tenant.staffs.*','tenant.attendances.*') ? 'sub-open' : '' }}"
                onclick="toggleSub('sub-hr', this)">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                </svg>
            </span>
            <span class="sb-label">HR</span>
            <span class="sb-arrow">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </span>
        </button>

        <div class="sb-sub {{ request()->routeIs('tenant.departments.*','tenant.staffs.*','tenant.attendances.*') ? 'open' : '' }}"
             id="sub-hr">

            @can('departments.view')
             <a href="{{ route('tenant.departments.index') }}"
               class="sb-item {{ request()->routeIs('tenant.departments.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                    </svg>
                </span>
                <span class="sb-label">Departments</span>
              </a>
            @endcan

            @can('staff.view')
            <a href="{{ route('tenant.staffs.index') }}"
               class="sb-item {{ request()->routeIs('tenant.staffs.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                    </svg>
                </span>
                <span class="sb-label">Staff</span>
            </a>
            @endcan

            <a href="{{ route('tenant.attendances.index') }}"
               class="sb-item {{ request()->routeIs('tenant.attendances.*') ? 'active' : '' }}">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z"/>
                    </svg>
                </span>
                <span class="sb-label">Attendance</span>
            </a>
        </div>

        {{-- Analytics --}}
        <div class="sb-section-label">Analytics</div>

        <a href="{{ route('tenant.reports.overview') }}"
           class="sb-item {{ request()->routeIs('tenant.reports.*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
            </span>
            <span class="sb-label">Reports</span>
        </a>

        {{-- System --}}
        <div class="sb-section-label">System</div>

        <a href="{{ route('tenant.settings.index') }}"
           class="sb-item {{ request()->routeIs('tenant.settings*') ? 'active' : '' }}">
            <span class="sb-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </span>
            <span class="sb-label">Settings</span>
        </a>

        {{-- Admin (tenant_admin only, collapsible group) --}}
        @if(auth()->user()?->user_type === 'tenant_admin')
            <div class="sb-section-label">Admin</div>

            <button type="button"
                    class="sb-item {{ request()->routeIs('tenant.roles.*','tenant.audit-logs.*') ? 'sub-open' : '' }}"
                    onclick="toggleSub('sub-admin', this)">
                <span class="sb-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                </span>
                <span class="sb-label">Admin</span>
                <span class="sb-arrow">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </span>
            </button>

            <div class="sb-sub {{ request()->routeIs('tenant.roles.*','tenant.audit-logs.*') ? 'open' : '' }}"
                 id="sub-admin">

                <a href="{{ route('tenant.roles.index') }}"
                   class="sb-item {{ request()->routeIs('tenant.roles.*') ? 'active' : '' }}">
                    <span class="sb-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                        </svg>
                    </span>
                    <span class="sb-label">Roles & Permissions</span>
                </a>

                <a href="{{ route('tenant.audit-logs.index') }}"
                   class="sb-item {{ request()->routeIs('tenant.audit-logs.*') ? 'active' : '' }}">
                    <span class="sb-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                        </svg>
                    </span>
                    <span class="sb-label">Audit Logs</span>
                </a>
            </div>
        @endif

    @endif {{-- end superadmin/tenant conditional --}}

    </div>

    {{-- ── User footer ──────────────────────────────────────────── --}}
    <div class="sb-footer">
        <div class="sb-user" onclick="toggleDrop('userMenuDrop')">
            <div class="sb-avatar">
                @if(auth()->user()->avatar)
                    <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="avatar"/>
                @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                @endif
            </div>
            <div class="sb-user-info">
                <div class="sb-user-name">{{ auth()->user()->name }}</div>
                <div class="sb-user-role">{{ ucfirst(str_replace('_',' ', auth()->user()->user_type)) }}</div>
            </div>
        </div>

        {{-- User dropdown --}}
        <div class="drop-menu" id="userMenuDrop" style="bottom:72px; top:auto; left:8px; right:8px; min-width:auto;">
            <a href="{{ route('tenant.profile.show') }}"
             class="drop-item">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
                My Profile
            </a>
            <a href="{{ route('tenant.settings.index') }}" 
            class="drop-item">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Settings
            </a>
            <div class="drop-sep"></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="drop-item red">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                    </svg>
                    Sign out
                </button>
            </form>
        </div>
    </div>

</aside>

{{-- Mobile overlay --}}
<div class="sb-overlay" id="sbOverlay" onclick="closeMobile()"></div>