<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantite',
        'prix_unitaire_ht',
        'taux_tva',
        'montant_ht',
        'montant_tva',
        'montant_ttc'
    ];

    protected $casts = [
        'quantite' => 'decimal:2',
        'prix_unitaire_ht' => 'decimal:2',
        'taux_tva' => 'decimal:2',
        'montant_ht' => 'decimal:2',
        'montant_tva' => 'decimal:2',
        'montant_ttc' => 'decimal:2'
    ];

    /**
     * Relation avec la facture
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Calcule automatiquement les montants lors de la sauvegarde
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($invoiceLine) {
            $invoiceLine->calculateAmounts();
        });

        static::saved(function ($invoiceLine) {
            $invoiceLine->invoice->calculateTotals();
        });

        static::deleted(function ($invoiceLine) {
            $invoiceLine->invoice->calculateTotals();
        });
    }

    /**
     * Calcule les montants HT, TVA et TTC
     */
    public function calculateAmounts(): void
    {
        $this->montant_ht = $this->quantite * $this->prix_unitaire_ht;
        $this->montant_tva = $this->montant_ht * ($this->taux_tva / 100);
        $this->montant_ttc = $this->montant_ht + $this->montant_tva;
    }
}
