<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Expense extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'expense_number',
        'expense_category_id',
        'vendor_id',
        'expense_date',
        'amount',
        'tax_id',
        'tax_amount',
        'total_amount',
        'currency_id',
        'exchange_rate',
        'payment_method_id',
        'account_id',
        'reference',
        'description',
        'attachments',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'paid_by',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'attachments' => 'array',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relations
    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function journalEntry(): MorphOne
    {
        return $this->morphOne(JournalEntry::class, 'entryable');
    }

    // Générer le numéro de dépense
    public static function generateNumber(): string
    {
        $year = now()->year;
        $lastExpense = static::where('expense_number', 'like', "DEP-{$year}-%")
            ->orderByDesc('expense_number')
            ->first();

        $number = $lastExpense ? intval(substr($lastExpense->expense_number, -4)) + 1 : 1;
        return sprintf('DEP-%s-%04d', $year, $number);
    }

    // Méthodes métier
    public function approve(): bool
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Seules les dépenses en attente peuvent être approuvées');
        }

        $this->status = 'approved';
        $this->approved_by = auth()->id();
        $this->approved_at = now();

        return $this->save();
    }

    public function reject(string $reason): bool
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Seules les dépenses en attente peuvent être rejetées');
        }

        $this->status = 'rejected';
        $this->rejection_reason = $reason;

        return $this->save();
    }

    public function markAsPaid(): bool
    {
        if ($this->status !== 'approved') {
            throw new \Exception('Seules les dépenses approuvées peuvent être marquées comme payées');
        }

        $this->status = 'paid';
        $this->paid_by = auth()->id();
        $this->paid_at = now();

        return $this->save();
    }

    // Calculer le total avec taxe
    protected static function booted()
    {
        static::saving(function ($expense) {
            // Calculer la taxe
            if ($expense->tax_id && $expense->isDirty('amount')) {
                $tax = Tax::find($expense->tax_id);
                if ($tax) {
                    $expense->tax_amount = $tax->calculate($expense->amount);
                }
            }

            // Calculer le montant total
            $expense->total_amount = $expense->amount + $expense->tax_amount;
        });
    }

    // Attributs
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvée',
            'rejected' => 'Rejetée',
            'paid' => 'Payée',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'rejected' => 'danger',
            'paid' => 'success',
            default => 'gray',
        };
    }
}
