<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceScreenshot;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ScreenshotController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }
    public function upload(Request $request)
    {
        $request->validate([
            'image'         => 'required|string', // base64
            'attendance_id' => 'required|exists:attendances,id',
            'type'          => 'in:auto,clockout',
        ]);

        // Attendance verify — belongs to current staff
        $attendance = Attendance::with('staff')->findOrFail($request->attendance_id);


        if ($attendance->staff->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

     

        // Clockout ke baad auto screenshot band
        if ($attendance->clock_out && $request->type !== 'clockout') {
            return response()->json(['error' => 'Already clocked out'], 422);
        }

        // Base64 → image file
        $base64 = $request->image;
        // return $base64;

        // "data:image/png;base64,..." strip karo
        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64)[1];
        }

        $imageData = base64_decode($base64);

        if (! $imageData || strlen($imageData) < 1000) {
            return response()->json(['error' => 'Invalid image data'], 422);
        }


        // Path: screenshots/tenant_id/staff_id/date/filename.jpg
        $tenantId  = $this->tenantId();
        $staffId   = $attendance->staff_id;
        $date      = now()->format('Y-m-d');
        $filename  = Str::uuid() . '.jpg';
        $path      = "screenshots/{$tenantId}/{$staffId}/{$date}/{$filename}";

        $disk = Storage::disk('public');
        if (! $disk->put($path, $imageData, 'public')) {
            return response()->json(['error' => 'Unable to save screenshot file'], 500);
        }

        $screenshot = AttendanceScreenshot::create([
            'tenant_id'     => $tenantId,
            'attendance_id' => $attendance->id,
            'staff_id'      => $staffId,
            'path'          => $path,
            'filename'      => $filename,
            'file_size'     => strlen($imageData),
            'captured_at'   => now(),
            'type'          => $request->type ?? 'auto',
        ]);

        return response()->json([
            'success' => true,
            'id'      => $screenshot->id,
            'time'    => now()->format('h:i A'),
        ]);
    }

    // ── Admin: View screenshots for an attendance ─────────────

    public function show(Attendance $attendance)
    {
        // A staff member may review their own screenshots; anyone else's are admin-only.
        abort_unless(
            auth()->user()->user_type === 'tenant_admin' || $attendance->staff?->user_id === auth()->id(),
            403
        );

        $tenantSlug   = auth()->user()->tenant->subdomain;
        $screenshots  = $attendance->screenshots()->get();

        return view('tenant.attendances.screenshots', compact(
            'attendance',
            'screenshots',
            'tenantSlug'
        ));
    }

    // ── Admin: Delete a screenshot ────────────────────────────

    public function destroy(AttendanceScreenshot $screenshot)
    {
        abort_unless(auth()->user()->user_type === 'tenant_admin', 403, 'Only workspace admins can delete screenshots.');

        Storage::disk('public')->delete($screenshot->path);
        $screenshot->delete();

        return back()->with('success', 'Screenshot delete ho gaya!');
    }
}
