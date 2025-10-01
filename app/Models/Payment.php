<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'payment_number',
        'paymentable_type',
        'paymentable_id',
        'payment_date',
        'amount',
        'currency_id',
        'exchange_rate',
        'payment_method_id',
        'account_id',
        'reference',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount', 'payment_date'])
            ->logOnlyDirty();
    }

    // Relations
    public function paymentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function journalEntry(): MorphOne
    {
        return $this->morphOne(JournalEntry::class, 'entryable');
    }

    // Générer le numéro de paiement
    public static function generateNumber(): string
    {
        $year = now()->year;
        $lastPayment = static::where('payment_number', 'like', "PAY-{$year}-%")
            ->orderByDesc('payment_number')
            ->first();

        $number = $lastPayment ? intval(substr($lastPayment->payment_number, -3)) + 1 : 1;
        return sprintf('PAY-%s-%03d', $year, $number);
    }
}
