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

class Invoice extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'invoice_date',
        'due_date',
        'reference',
        'terms',
        'notes',
        'footer',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'amount_paid',
        'amount_due',
        'currency_id',
        'exchange_rate',
        'status',
        'sent_at',
        'viewed_at',
        'paid_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
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
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function journalEntry(): MorphOne
    {
        return $this->morphOne(JournalEntry::class, 'entryable');
    }

    // Générer le numéro de facture
    public static function generateNumber(): string
    {
        $year = now()->year;
        $lastInvoice = static::where('invoice_number', 'like', "FAC-{$year}-%")
            ->orderByDesc('invoice_number')
            ->first();

        $number = $lastInvoice ? intval(substr($lastInvoice->invoice_number, -3)) + 1 : 1;
        return sprintf('FAC-%s-%03d', $year, $number);
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

    // Marquer comme envoyée
    public function markAsSent(): void
    {
        $this->status = 'sent';
        $this->sent_at = now();
        $this->save();
    }

    // Attributs
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Brouillon',
            'sent' => 'Envoyée',
            'viewed' => 'Vue',
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
            'sent' => 'info',
            'viewed' => 'warning',
            'partial' => 'warning',
            'paid' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }
}
