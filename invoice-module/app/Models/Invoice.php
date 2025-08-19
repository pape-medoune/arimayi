<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'numero_facture',
        'date_facture',
        'total_ht',
        'total_tva',
        'total_ttc'
    ];

    protected $casts = [
        'date_facture' => 'date',
        'total_ht' => 'decimal:2',
        'total_tva' => 'decimal:2',
        'total_ttc' => 'decimal:2'
    ];

    /**
     * Relation avec le client
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relation avec les lignes de facture
     */
    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Calcule automatiquement les totaux à partir des lignes
     */
    public function calculateTotals(): void
    {
        $lines = $this->invoiceLines;
        
        $this->total_ht = $lines->sum('montant_ht');
        $this->total_tva = $lines->sum('montant_tva');
        $this->total_ttc = $lines->sum('montant_ttc');
        
        $this->save();
    }
}
