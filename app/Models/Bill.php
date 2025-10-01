<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Bill extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'bill_number',
        'vendor_id',
        'bill_date',
        'due_date',
        'reference',
        'notes',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'amount_paid',
        'amount_due',
        'currency_id',
        'exchange_rate',
        'status',
        'received_at',
        'paid_at',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'received_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total', 'amount_paid'])
            ->logOnlyDirty();
    }

    // Relations
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function journalEntry(): MorphOne
    {
        return $this->morphOne(JournalEntry::class, 'entryable');
    }

    // Générer le numéro
    public static function generateNumber(): string
    {
        $year = now()->year;
        $lastBill = static::where('bill_number', 'like', "FACT-FRS-{$year}-%")
            ->orderByDesc('bill_number')
            ->first();

        $number = $lastBill ? intval(substr($lastBill->bill_number, -3)) + 1 : 1;
        return sprintf('FACT-FRS-%s-%03d', $year, $number);
    }

    // Calculer les totaux
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price - $item->discount_amount;
        });

        $this->tax_amount = $this->items->sum('tax_amount');
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->amount_due = $this->total - $this->amount_paid;

        $this->save();
    }

    // Mettre à jour le statut
    public function updateStatus(): void
    {
        if ($this->amount_paid >= $this->total) {
            $this->status = 'paid';
            $this->paid_at = now();
        } elseif ($this->amount_paid > 0) {
            $this->status = 'partial';
        } elseif ($this->due_date < now() && $this->status !== 'paid') {
            $this->status = 'overdue';
        }

        $this->save();
    }

    // Attributs
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Brouillon',
            'received' => 'Reçue',
            'partial' => 'Partiellement payée',
            'paid' => 'Payée',
            'overdue' => 'En retard',
            'cancelled' => 'Annulée',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'received' => 'info',
            'partial' => 'warning',
            'paid' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }
}
