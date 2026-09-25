<!DOCTYPE html>
<html lang="en" data-theme="{{ Auth::user()?->theme ?? 'dark' }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}" />
    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('favicon-64.png') }}" />
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}" />
    <title>@yield('title', 'Dashboard') — {{ Auth::user()?->tenant?->name ?? 'Mishora CRM' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: '1' }}" />
    @stack('styles')
</head>

<body>

    {{-- ── NProgress loading bar ──────────────────────────────────────── --}}
    <div id="nprogress-bar"></div>

    {{-- ── Tooltip ────────────────────────────────────────────────────── --}}
    <div id="crm-tooltip" role="tooltip"></div>

    <div class="app-shell">

        {{-- ── Sidebar ────────────────────────────────────────────────── --}}
        @include('components.sidebar')

        {{-- ── Main area ───────────────────────────────────────────────── --}}
        <div class="main-area" id="mainArea">

            {{-- Topbar --}}
            @include('components.topbar')

            {{-- ── Flash messages ──────────────────────────────────────── --}}
            <div id="flash-region" aria-live="polite">

                @if(session('success'))
                    <div class="flash flash-success" role="alert" data-flash>
                        <div class="flash-icon">
                            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="flash-text">{{ session('success') }}</span>
                        <button class="flash-close" onclick="dismissFlash(this)" aria-label="Dismiss">
                            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="flash flash-error" role="alert" data-flash>
                        <div class="flash-icon">
                            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <span class="flash-text">{{ session('error') }}</span>
                        <button class="flash-close" onclick="dismissFlash(this)" aria-label="Dismiss">
                            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="flash flash-warning" role="alert" data-flash>
                        <div class="flash-icon">
                            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.553-13.07c.866-1.5 3.032-1.5 3.898 0l7.553 13.07z" />
                            </svg>
                        </div>
                        <span class="flash-text">{{ session('warning') }}</span>
                        <button class="flash-close" onclick="dismissFlash(this)" aria-label="Dismiss">
                            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="flash flash-info" role="alert" data-flash>
                        <div class="flash-icon">
                            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        </div>
                        <span class="flash-text">{{ session('info') }}</span>
                        <button class="flash-close" onclick="dismissFlash(this)" aria-label="Dismiss">
                            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif

            </div>{{-- /flash-region --}}

            {{-- ── Page content ─────────────────────────────────────────── --}}
            <main class="page-body" id="pageBody">
                @yield('content')
            </main>

        </div>{{-- /main-area --}}

    </div>{{-- /app-shell --}}

    {{-- ── Confirm modal (global) ──────────────────────────────────────── --}}
    <div class="modal-backdrop" id="confirmBackdrop" style="display:none" onclick="closeConfirm()">
        <div class="modal-box" onclick="event.stopPropagation()" role="dialog" aria-modal="true">
            <div class="modal-icon modal-icon-danger">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <div class="modal-title" id="confirmTitle">Are you sure?</div>
            <div class="modal-body" id="confirmBody">This action cannot be undone.</div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeConfirm()">Cancel</button>
                <button class="btn btn-danger" id="confirmOkBtn">Delete</button>
            </div>
        </div>
    </div>

    {{-- ── Global scripts ────────────────────────────────────────────────── --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        /* ──────────────────────────────────────────────
           NProgress-like loading bar
        ────────────────────────────────────────────── */
        const npbar = document.getElementById('nprogress-bar');
        function npStart() { npbar.style.width = '0'; npbar.style.opacity = '1'; setTimeout(() => npbar.style.width = '70%', 10); }
        function npDone() { npbar.style.width = '100%'; setTimeout(() => { npbar.style.opacity = '0'; setTimeout(() => npbar.style.width = '0', 300); }, 200); }
        document.addEventListener('click', function (e) {
            const a = e.target.closest('a[href]');
            if (a && !a.target && !a.href.startsWith('#') && !a.href.startsWith('javascript') && a.href !== window.location.href) npStart();
        });
        window.addEventListener('pageshow', npDone);

        /* ──────────────────────────────────────────────
           Theme
        ────────────────────────────────────────────── */
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.dataset.theme === 'dark';
            const next = isDark ? 'light' : 'dark';
            html.dataset.theme = next;
            localStorage.setItem('crm_theme', next);
            const moon = document.getElementById('ico-moon');
            const sun = document.getElementById('ico-sun');
            if (moon) moon.style.display = isDark ? '' : 'none';
            if (sun) sun.style.display = isDark ? 'none' : '';

            // Persist the preference to the user's account so it follows them to any device/browser.
            fetch('{{ route('tenant.settings.theme') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ theme: next }),
            }).catch(() => {});
        }
        (function () {
            // Theme itself is already applied in <head> to avoid a flash; just sync the icon.
            if (document.documentElement.dataset.theme === 'light') {
                const moon = document.getElementById('ico-moon');
                const sun = document.getElementById('ico-sun');
                if (moon) moon.style.display = '';
                if (sun) sun.style.display = 'none';
            }
        })();

        /* ──────────────────────────────────────────────
           Sidebar
        ────────────────────────────────────────────── */
        let sbCollapsed = localStorage.getItem('crm_sb') === '1';
        const sidebar = document.getElementById('sidebar');
        const mainArea = document.getElementById('mainArea');
        const overlay = document.getElementById('sbOverlay');

        function applyCollapse() {
            sidebar.classList.toggle('collapsed', sbCollapsed);
            mainArea.classList.toggle('collapsed', sbCollapsed);
        }
        applyCollapse();

        function toggleSidebar() {
            if (window.innerWidth <= 768) {
                const isOpen = sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('show', isOpen);
                document.body.classList.toggle('sb-lock', isOpen);
            } else {
                sbCollapsed = !sbCollapsed;
                localStorage.setItem('crm_sb', sbCollapsed ? '1' : '0');
                applyCollapse();
            }
        }
        function closeMobile() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('show');
            document.body.classList.remove('sb-lock');
        }
        window.addEventListener('resize', () => { if (window.innerWidth > 768) closeMobile(); });

        /* ──────────────────────────────────────────────
           Submenus
        ────────────────────────────────────────────── */
        function toggleSub(id, btn) {
            const sub = document.getElementById(id);
            const isOpen = sub.classList.toggle('open');
            btn.classList.toggle('sub-open', isOpen);
        }

        /* ──────────────────────────────────────────────
           Dropdowns
        ────────────────────────────────────────────── */
        function toggleDrop(id) {
            document.querySelectorAll('.drop-menu').forEach(m => {
                if (m.id !== id) m.classList.remove('open');
            });
            document.getElementById(id)?.classList.toggle('open');
        }
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.drop-menu') && !e.target.closest('[onclick*="toggleDrop"]')) {
                document.querySelectorAll('.drop-menu').forEach(m => m.classList.remove('open'));
            }
        });

        /* ──────────────────────────────────────────────
           Flash messages
        ────────────────────────────────────────────── */
        function dismissFlash(btn) {
            const el = btn.closest('[data-flash]');
            el.classList.add('flash-dismiss');
            setTimeout(() => el.remove(), 300);
        }
        // Auto-dismiss after 5s
        document.querySelectorAll('[data-flash]').forEach(el => {
            setTimeout(() => {
                if (el.isConnected) dismissFlash(el.querySelector('.flash-close'));
            }, 5000);
        });

        /* ──────────────────────────────────────────────
           Global Search
        ────────────────────────────────────────────── */
        function handleSearch(e) {
            if (e.key === 'Enter') {
                const q = e.target.value.trim();
                if (q) { npStart(); window.location.href = '/search?q=' + encodeURIComponent(q); }
            }
            if (e.key === 'Escape') e.target.blur();
        }
        document.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                const gs = document.getElementById('globalSearch');
                if (gs) { gs.focus(); gs.select(); }
            }
        });

        /* ──────────────────────────────────────────────
           Confirm modal
        ────────────────────────────────────────────── */
        let _confirmCallback = null;
        function confirmAction(opts = {}) {
            document.getElementById('confirmTitle').textContent = opts.title ?? 'Are you sure?';
            document.getElementById('confirmBody').textContent = opts.message ?? 'This action cannot be undone.';
            const okBtn = document.getElementById('confirmOkBtn');
            okBtn.textContent = opts.ok ?? 'Confirm';
            okBtn.className = 'btn ' + (opts.danger !== false ? 'btn-danger' : 'btn-primary');
            _confirmCallback = opts.onConfirm ?? null;
            okBtn.onclick = () => { closeConfirm(); if (_confirmCallback) _confirmCallback(); };
            const bd = document.getElementById('confirmBackdrop');
            bd.style.display = 'flex';
            requestAnimationFrame(() => bd.classList.add('open'));
        }
        function closeConfirm() {
            const bd = document.getElementById('confirmBackdrop');
            bd.classList.remove('open');
            setTimeout(() => bd.style.display = 'none', 200);
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeConfirm(); });

        /* ──────────────────────────────────────────────
           Tooltip
        ────────────────────────────────────────────── */
        const _tip = document.getElementById('crm-tooltip');
        let _tipTimer;
        document.addEventListener('mouseover', function (e) {
            const el = e.target.closest('[data-tip]');
            if (!el) return;
            clearTimeout(_tipTimer);
            _tipTimer = setTimeout(() => {
                _tip.textContent = el.dataset.tip;
                _tip.classList.add('show');
                const r = el.getBoundingClientRect();
                const tw = _tip.offsetWidth;
                let left = r.left + r.width / 2 - tw / 2 + window.scrollX;
                left = Math.max(8, Math.min(left, window.innerWidth - tw - 8));
                _tip.style.left = left + 'px';
                _tip.style.top = (r.top + window.scrollY - _tip.offsetHeight - 8) + 'px';
            }, 350);
        });
        document.addEventListener('mouseout', function (e) {
            if (!e.target.closest('[data-tip]')) return;
            clearTimeout(_tipTimer);
            _tip.classList.remove('show');
        });

        /* ──────────────────────────────────────────────
           CSRF helper
        ────────────────────────────────────────────── */
        window.CrmCsrf = document.querySelector('meta[name="csrf-token"]')?.content;
        window.crmPost = async function (url, data = {}) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.CrmCsrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });
            return res.json();
        };

        /* ──────────────────────────────────────────────
           Toast (programmatic flash from JS)
           Usage: showToast('Saved!', 'success')
        ────────────────────────────────────────────── */
        function showToast(msg, type = 'success') {
            const icons = {
                success: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                error: '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                warning: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.553-13.07c.866-1.5 3.032-1.5 3.898 0l7.553 13.07z"/>',
                info: '<path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>',
            };
            const el = document.createElement('div');
            el.className = `flash flash-${type}`;
            el.dataset.flash = '';
            el.innerHTML = `
        <div class="flash-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">${icons[type] ?? icons.success}</svg>
        </div>
        <span class="flash-text">${msg}</span>
        <button class="flash-close" onclick="dismissFlash(this)" aria-label="Dismiss">
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>`;
            document.getElementById('flash-region').appendChild(el);
            setTimeout(() => { if (el.isConnected) dismissFlash(el.querySelector('.flash-close')); }, 5000);
        }

        /* ──────────────────────────────────────────────
           Select2 — auto-applied to real <select> elements app-wide so
           long/dynamic dropdowns (staff, sources, customers, etc.) are
           searchable, without needing per-page markup.

           Short, fixed dropdowns (status filters, yes/no, priority, ...)
           are skipped and left as plain native <select> — select2 slightly
           changes their look and adds no value when there's only a
           handful of options to scan.

           Opt out with class="no-select2" or data-no-select2.
           Opt in anyway (force select2 despite few options) with
           class="force-select2" or data-force-select2.
        ────────────────────────────────────────────── */
        const SELECT2_MIN_OPTIONS = 6;

        function initSelect2(scope) {
            $(scope || document).find('select').each(function () {
                const $el = $(this);
                if ($el.hasClass('select2-hidden-accessible')) return;
                if ($el.hasClass('no-select2') || $el.is('[data-no-select2]')) return;
                if ($el.is(':disabled')) return;

                const forceOn = $el.hasClass('force-select2') || $el.is('[data-force-select2]');
                if (!forceOn && $el.find('option').length < SELECT2_MIN_OPTIONS) return;

                const $modal = $el.closest('.modal-box');
                const hasBlankOption = $el.find('option[value=""]').length > 0;

                $el.select2({
                    // 'resolve' sizes the widget from the <select>'s own CSS width
                    // (or its natural shrink-to-content width if none is set), so
                    // full-width form selects stay full width and compact inline
                    // filter-bar selects stay compact instead of all becoming 100%.
                    width: 'resolve',
                    allowClear: hasBlankOption && !$el.prop('required'),
                    placeholder: hasBlankOption ? ($el.find('option[value=""]').first().text() || ' ') : undefined,
                    dropdownParent: $modal.length ? $modal : $(document.body),
                });
            });
        }

        $(document).ready(function () {
            initSelect2();

            // Re-scan for <select> elements added dynamically later on (e.g. rows
            // inserted by page JS), so new dropdowns also become searchable.
            const selectObserver = new MutationObserver(function (mutations) {
                for (const m of mutations) {
                    m.addedNodes.forEach(function (node) {
                        if (node.nodeType !== 1) return;
                        if (node.matches && node.matches('select')) initSelect2(node.parentNode);
                        else if (node.querySelector && node.querySelector('select')) initSelect2(node);
                    });
                }
            });
            selectObserver.observe(document.body, { childList: true, subtree: true });
        });

    </script>
    {{-- push notifications --}}
    <script type="module">
        import { Capacitor } from 'https://cdn.jsdelivr.net/npm/@capacitor/core@latest/dist/index.js';

        window.CapacitorCore = Capacitor;

        console.log("Capacitor Core Loaded", Capacitor);
    </script>
    <script src="{{ asset('js/push-notifications.js') }}"></script>
    @stack('scripts')
</body>

</html>