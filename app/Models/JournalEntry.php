<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JournalEntry extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'entry_number',
        'journal_id',
        'date',
        'reference',
        'description',
        'currency_id',
        'exchange_rate',
        'status',
        'total_debit',
        'total_credit',
        'entryable_type',
        'entryable_id',
        'created_by',
        'posted_by',
        'posted_at',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'exchange_rate' => 'decimal:6',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_debit', 'total_credit'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relations
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function entryable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    // Scopes
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // Méthodes métier
    public function calculateTotals(): void
    {
        $this->total_debit = $this->transactions()->sum('debit');
        $this->total_credit = $this->transactions()->sum('credit');
        $this->save();
    }

    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    public function post(): bool
    {
        if (!$this->isBalanced()) {
            throw new \Exception('L\'écriture n\'est pas équilibrée (débit ≠ crédit)');
        }

        if ($this->status === 'posted') {
            throw new \Exception('L\'écriture est déjà validée');
        }

        $this->status = 'posted';
        $this->posted_by = auth()->id();
        $this->posted_at = now();

        return $this->save();
    }

    public function cancel(): bool
    {
        if ($this->status !== 'posted') {
            throw new \Exception('Seules les écritures validées peuvent être annulées');
        }

        $this->status = 'cancelled';
        return $this->save();
    }

    // Attributs
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Brouillon',
            'posted' => 'Validée',
            'cancelled' => 'Annulée',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'warning',
            'posted' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }
}
