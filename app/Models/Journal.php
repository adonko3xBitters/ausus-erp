<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'default_debit_account_id',
        'default_credit_account_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function defaultDebitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_debit_account_id');
    }

    public function defaultCreditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_credit_account_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    // Générer le prochain numéro d'écriture
    public function getNextEntryNumber(): string
    {
        $year = now()->year;
        $lastEntry = $this->entries()
            ->where('entry_number', 'like', "{$this->code}-{$year}-%")
            ->orderByDesc('entry_number')
            ->first();

        if ($lastEntry) {
            $lastNumber = (int) substr($lastEntry->entry_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s-%04d', $this->code, $year, $nextNumber);
    }

    // Types de journaux avec labels
    public static function getTypes(): array
    {
        return [
            'sales' => 'Ventes',
            'purchases' => 'Achats',
            'bank' => 'Banque',
            'cash' => 'Caisse',
            'general' => 'Opérations diverses',
        ];
    }
}
