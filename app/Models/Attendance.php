<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'date',
        'clock_in',
        'clock_out',
        'status',
        'notes',
    ];

    protected $casts = [
        'date'      => 'date',
        'clock_in'  => 'datetime',
        'clock_out' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)
            ->whereMonth('date', $month);
    }

    public function scopeForStaff($query, $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function getWorkedHoursAttribute(): ?string
    {
        if ($this->clock_in && $this->clock_out) {
            $minutes = (int) floor($this->clock_in->diffInMinutes($this->clock_out));
            return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
        }
        return null;
    }

    public function isClockedIn(): bool
    {
        return $this->clock_in !== null && $this->clock_out === null;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'present'  => 'green',
            'absent'   => 'red',
            'half_day' => 'amber',
            'holiday'  => 'purple',
            'leave'    => 'blue',
            default    => 'grey',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'present'  => 'Present',
            'absent'   => 'Absent',
            'half_day' => 'Half Day',
            'holiday'  => 'Holiday',
            'leave'    => 'On Leave',
            default    => ucfirst($this->status),
        };
    }

    // Attendance.php ke andar add karo
    public function screenshots(): HasMany
    {
        return $this->hasMany(AttendanceScreenshot::class)->orderBy('captured_at');
    }
}
