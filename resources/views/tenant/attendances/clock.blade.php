@extends('layouts.app')
@section('title', 'Clock In/Out')

@section('content')
    @php
        $tenantSlug = auth()->user()->tenant->subdomain;
        $currentStaffId = auth()->user()->staff?->id ?? 0;
    @endphp

    <div class="page-head">
        <div>
            <div class="page-title">⏱ Clock In / Out</div>
            <div class="page-sub">{{ today()->format('l, d F Y') }}</div>
        </div>
        <div class="page-actions">
            <a href="{{ route('tenant.attendances.index') }}" class="btn btn-secondary">← Back</a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div style="background:var(--green-dim);border:1px solid var(--green);color:var(--green);
                    border-radius:var(--r-sm);padding:10px 16px;margin-bottom:16px;font-size:13.5px">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:var(--red-dim);border:1px solid var(--red);color:var(--red);
                    border-radius:var(--r-sm);padding:10px 16px;margin-bottom:16px;font-size:13.5px">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    {{-- Screenshot Status Bar (only visible when monitoring active) --}}
    <div id="monitorBar" style="display:none;
         background:var(--green-dim);border:1px solid var(--green);
         border-radius:var(--r-sm);padding:10px 16px;margin-bottom:16px;
         display:none;align-items:center;gap:12px;font-size:13px;color:var(--green)">
        <span style="width:8px;height:8px;background:var(--green);border-radius:50%;
                     display:inline-block;animation:pulse 2s infinite"></span>
        <span id="monitorText">Screen monitoring active</span>
        <span id="nextCapture" style="margin-left:auto;color:var(--text-300);font-size:12px"></span>
    </div>

    <style>
        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: .5;
                transform: scale(1.3);
            }
        }
    </style>

    <div class="card">
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Worked</th>
                        <th>Screenshots</th>
                        <th>Status</th>
                        <th style="text-align:center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($staffList as $staff)
                        @php $att = $today[$staff->id] ?? null; @endphp
                        <tr>
                            <td data-label="Staff">
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="width:36px;height:36px;border-radius:50%;
                                                background:var(--accent-dim);color:var(--accent);
                                                display:flex;align-items:center;justify-content:center;
                                                font-size:14px;font-weight:700;flex-shrink:0">
                                        {{ strtoupper(substr($staff->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">
                                            {{ $staff->name }}
                                        </div>
                                        @if($staff->designation)
                                            <div style="font-size:11.5px;color:var(--text-400)">
                                                {{ $staff->designation }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="td-mono" style="font-size:13px" data-label="Clock In">
                                {{ $att?->clock_in?->format('h:i A') ?? '—' }}
                            </td>
                            <td class="td-mono" style="font-size:13px" data-label="Clock Out">
                                {{ $att?->clock_out?->format('h:i A') ?? '—' }}
                            </td>
                            <td class="td-mono" style="font-size:13px;color:var(--text-200)" data-label="Worked">
                                {{ $att?->worked_hours ?? '—' }}
                            </td>

                            {{-- Screenshots count --}}
                            <td data-label="Screenshots">
                                @if($att && $att->screenshots_count > 0 && (auth()->user()->user_type === 'tenant_admin' || $staff->id === $currentStaffId))
                                    <a href="{{ route('tenant.screenshots.show', $att->id) }}" style="font-size:12.5px;color:var(--accent);text-decoration:none;
                                              display:flex;align-items:center;gap:4px">
                                        📷 {{ $att->screenshots_count }}
                                        <span style="color:var(--text-400)">shots</span>
                                    </a>
                                @else
                                    <span style="font-size:12px;color:var(--text-400)">—</span>
                                @endif
                            </td>

                            <td data-label="Status">
                                @if($att)
                                    <span class="status-badge badge-{{ $att->status_color }}">
                                        {{ $att->status_label }}
                                    </span>
                                @else
                                    <span style="font-size:12px;color:var(--text-400)">Not marked</span>
                                @endif
                            </td>

                            <td style="text-align:center" data-label="Action">
                                @if($staff->id !== $currentStaffId)
                                    <span style="font-size:12px;color:var(--text-400)">Not your account</span>

                                @elseif(!$att || !$att->clock_in)
                                    {{-- Clock In Button --}}
                                    <button type="button" onclick="startClockIn({{ $staff->id }}, '{{ $staff->name }}')"
                                        class="btn btn-secondary" style="color:var(--green);border-color:var(--green);
                                                       background:var(--green-dim);font-size:12.5px">
                                        ▶ Clock In
                                    </button>

                                @elseif(!$att->clock_out)
                                    {{-- Clock Out Button --}}
                                    <button type="button" onclick="startClockOut({{ $staff->id }}, {{ $att->id }})"
                                        class="btn btn-secondary" style="color:var(--red);border-color:var(--red);
                                                       background:var(--red-dim);font-size:12.5px">
                                        ⏹ Clock Out
                                    </button>

                                @else
                                    <span style="font-size:12px;color:var(--green);font-weight:600">
                                        ✅ Done
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Hidden forms --}}
    <form id="clockInForm" method="POST" action="{{ route('tenant.attendances.clock.in', ['tenant' => $tenantSlug]) }}"
        style="display:none">
        @csrf
        <input type="hidden" name="staff_id" id="clockInStaffId" value="{{ $currentStaffId ?: '' }}">
    </form>

    <form id="clockOutForm" method="POST" action="{{ route('tenant.attendances.clock.out', ['tenant' => $tenantSlug]) }}"
        style="display:none">
        @csrf
        <input type="hidden" name="staff_id" id="clockOutStaffId" value="{{ $currentStaffId ?: '' }}">
    </form>

@endsection

@push('scripts')
    <script>
        // ── Config ────────────────────────────────────────────────────────
        const UPLOAD_URL = "{{ route('tenant.screenshots.upload') }}";

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const CSRF_TOKEN = "{{ csrf_token() }}";
        const CURRENT_STAFF_ID = {{ $currentStaffId }};
        const INTERVAL_MS = 0.1 * 60 * 1000; // 1 minute
        const PREVIEW_QUALITY = 0.7;            // JPEG quality

        // ── State ─────────────────────────────────────────────────────────
        let mediaStream = null;
        let captureInterval = null;
        let currentAttendId = null;
        let nextCaptureTime = null;
        let countdownTimer = null;

        // ─────────────────────────────────────────────────────────────────
        // CLOCK IN FLOW
        // 1. Screen share permission maango
        // 2. Pehla screenshot lo
        // 3. Form submit karo (server pe attendance bana)
        // 4. Har 30 min pe auto capture start karo
        // ─────────────────────────────────────────────────────────────────
        async function startClockIn(staffId, staffName) {
            if (staffId !== CURRENT_STAFF_ID) {
                showToast('Aap sirf apna hi clock in kar sakte hain.', 'error');
                return;
            }

            // Step 1: Screen share permission
            const granted = await requestScreenShare();
            if (!granted) {
                showToast('Screen share allow karna zaroori hai monitoring ke liye.', 'error');
                return;
            }

            showToast(`${staffName} ka clock in ho raha hai...`, 'info');

            // Step 2: Pehla screenshot
            // (attendance_id abhi nahi pata — clock in ke baad milega)
            // Toh seedha form submit karte hain, baad mein screenshot upload karenge
            document.getElementById('clockInStaffId').value = staffId;

            // Hidden field add karo — server ko batao ki screenshot monitoring shuru ho
            document.getElementById('clockInForm').submit();
        }

        // ─────────────────────────────────────────────────────────────────
        // CLOCK OUT FLOW
        // 1. Final screenshot lo
        // 2. Stream band karo
        // 3. Form submit karo
        // ─────────────────────────────────────────────────────────────────
        async function startClockOut(staffId, attendanceId) {
            if (staffId !== CURRENT_STAFF_ID) {
                showToast('Aap sirf apna hi clock out kar sakte hain.', 'error');
                return;
            }

            showToast('Final screenshot le raha hai...', 'info');

            // Final screenshot
            if (mediaStream) {
                await captureAndUpload(attendanceId, 'clockout');
            }

            // Stream band karo
            stopScreenShare();

            // Form submit
            document.getElementById('clockOutStaffId').value = staffId;
            document.getElementById('clockOutForm').submit();
        }

        // ─────────────────────────────────────────────────────────────────
        // SCREEN SHARE
        // ─────────────────────────────────────────────────────────────────
        async function requestScreenShare() {

            // Browser support check
            if (
                !navigator.mediaDevices ||
                !navigator.mediaDevices.getDisplayMedia
            ) {
                console.error('getDisplayMedia not supported');

                showToast(
                    'Screen sharing is not supported in this browser or insecure connection.',
                    'error'
                );

                return false;
            }

            try {

                mediaStream = await navigator.mediaDevices.getDisplayMedia({
                    video: {
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                    },
                    audio: false,
                });

                // User manually stopped sharing
                mediaStream.getVideoTracks()[0].addEventListener('ended', () => {
                    stopMonitoring();
                    showToast('Screen share band ho gaya. Monitoring stop.', 'error');
                });

                return true;

            } catch (err) {

                console.error('Screen share error:', err);

                showToast(
                    err.message || 'Unable to start screen sharing',
                    'error'
                );

                return false;
            }
        }

        function stopScreenShare() {
            if (mediaStream) {
                mediaStream.getTracks().forEach(t => t.stop());
                mediaStream = null;
            }
        }

        // ─────────────────────────────────────────────────────────────────
        // AUTO MONITORING (30 min interval)
        // Clock In page reload ke baad check karo — kya active attendance hai?
        // ─────────────────────────────────────────────────────────────────
        function startMonitoring(attendanceId) {
            currentAttendId = attendanceId;

            // Monitor bar show karo
            const bar = document.getElementById('monitorBar');
            bar.style.display = 'flex';

            // Countdown update
            scheduleNext();

            // Har 30 min pe capture
            captureInterval = setInterval(async () => {
                await captureAndUpload(currentAttendId, 'auto');
                scheduleNext();
            }, INTERVAL_MS);
        }

        function stopMonitoring() {
            clearInterval(captureInterval);
            clearInterval(countdownTimer);
            captureInterval = null;
            document.getElementById('monitorBar').style.display = 'none';
        }

        function scheduleNext() {
            nextCaptureTime = Date.now() + INTERVAL_MS;
            updateCountdown();

            countdownTimer = setInterval(() => {
                updateCountdown();
            }, 1000);
        }

        function updateCountdown() {
            const remaining = Math.max(0, nextCaptureTime - Date.now());
            const mins = Math.floor(remaining / 60000);
            const secs = Math.floor((remaining % 60000) / 1000);
            const el = document.getElementById('nextCapture');
            if (el) {
                el.textContent = `Next screenshot: ${mins}m ${String(secs).padStart(2, '0')}s`;
            }
        }

        // ─────────────────────────────────────────────────────────────────
        // CAPTURE + UPLOAD
        // ─────────────────────────────────────────────────────────────────
        async function captureAndUpload(attendanceId, type = 'auto') {
            if (!mediaStream) return;

            try {
                // Video element pe stream draw karo
                const video = document.createElement('video');
                video.srcObject = mediaStream;
                video.muted = true;
                await video.play();

                // Canvas pe capture
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth || 1280;
                canvas.height = video.videoHeight || 720;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Timestamp watermark
                ctx.fillStyle = 'rgba(0,0,0,0.55)';
                ctx.fillRect(0, canvas.height - 32, 280, 32);
                ctx.fillStyle = '#ffffff';
                ctx.font = '13px monospace';
                ctx.fillText(
                    `📷 ${new Date().toLocaleString('en-IN')}`,
                    10,
                    canvas.height - 10
                );

                video.pause();

                // Base64 JPEG
                const base64 = canvas.toDataURL('image/jpeg', PREVIEW_QUALITY);

                // Upload to server
                const res = await fetch(UPLOAD_URL, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        image: base64,
                        attendance_id: attendanceId,
                        type: type,
                    }),
                });

                const data = await res.json();

                if (data.success) {
                    console.log(`✅ Screenshot uploaded at ${data.time}`);
                    document.getElementById('monitorText').textContent =
                        `Screen monitoring active — Last: ${data.time}`;
                }

            } catch (err) {
                console.error('Screenshot error:', err);
            }
        }

        // ─────────────────────────────────────────────────────────────────
        // PAGE LOAD: Check karo kya koi active clock-in hai is device pe
        // LocalStorage mein attendance_id save karte hain
        // ─────────────────────────────────────────────────────────────────
        window.addEventListener('load', async () => {
            const saved = localStorage.getItem('active_attendance');
            if (!saved) return;

            const { attendanceId, staffId } = JSON.parse(saved);

            // Kya ye staff abhi bhi clocked in hai? (page mein check karo)
            // Agar clock out button dikh raha hai matlab active hai
            const clockOutBtn = document.querySelector(`[onclick*="startClockOut(${staffId},"]`);
            if (!clockOutBtn) {
                // Already clocked out — clear karo
                localStorage.removeItem('active_attendance');
                return;
            }

            // Screen share wapas lo (page reload ke baad)
            showToast('Active monitoring detect hua — screen share reconnect karo', 'info');

            const granted = await requestScreenShare();
            if (granted) {
                startMonitoring(attendanceId);
                // Pehla screenshot wapas lo
                await captureAndUpload(attendanceId, 'auto');
            }
        });

        // Clock In form submit hone ke baad — attendance_id localStorage mein save karo
        // Server redirect ke baad attendance_id URL se ya session se lena padega
        // Isliye hum ek simple approach use karte hain:
        // Server pe clock_in ke baad redirect mein ?att_id=X pass karo

        @if(request()->has('att_id'))
            (function () {
                const attId = {{ request('att_id') }};
                const staffId = {{ request('staff_id', 0) }};
                localStorage.setItem('active_attendance', JSON.stringify({
                    attendanceId: attId,
                    staffId: staffId,
                }));

                // Screen share yahan dobara nahi maangte — 'load' listener neeche
                // localStorage se ise pick karke ek hi baar request karega,
                // isliye clock-in redirect ke turant baad prompt do baar nahi aayega.
            })();
        @endif

        // Clock out ke baad clear karo
        @if(session('clocked_out'))
            localStorage.removeItem('active_attendance');
        @endif

        // ─────────────────────────────────────────────────────────────────
        // TOAST HELPER
        // ─────────────────────────────────────────────────────────────────
        function showToast(msg, type = 'info') {
            const colors = {
                info: ['var(--accent-dim)', 'var(--accent)'],
                error: ['var(--red-dim)', 'var(--red)'],
                ok: ['var(--green-dim)', 'var(--green)'],
            };
            const [bg, color] = colors[type] || colors.info;

            const el = document.createElement('div');
            el.style.cssText = `
            position:fixed;bottom:24px;right:24px;z-index:9999;
            background:${bg};border:1px solid ${color};color:${color};
            padding:12px 18px;border-radius:var(--r-sm);font-size:13.5px;
            box-shadow:0 4px 20px rgba(0,0,0,0.3);
            animation:fadeUp 0.3s ease;max-width:320px;line-height:1.4;
        `;
            el.textContent = msg;
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 4000);
        }
    </script>
@endpush