<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Exception;

class InvoiceService
{
    /**
     * Crée une nouvelle facture avec ses lignes
     */
    public function createInvoice(array $data): Invoice
    {
        DB::beginTransaction();
        
        try {
            // Générer un numéro de facture automatique si non fourni
            if (!isset($data['numero_facture'])) {
                $data['numero_facture'] = $this->generateInvoiceNumber();
            }
            
            // Créer la facture
            $invoice = Invoice::create([
                'client_id' => $data['client_id'],
                'numero_facture' => $data['numero_facture'],
                'date_facture' => $data['date_facture'],
                'total_ht' => 0,
                'total_tva' => 0,
                'total_ttc' => 0
            ]);
            
            // Ajouter les lignes de facture
            if (isset($data['lines']) && is_array($data['lines'])) {
                foreach ($data['lines'] as $lineData) {
                    $this->addInvoiceLine($invoice, $lineData);
                }
            }
            
            DB::commit();
            
            return $invoice->load(['client', 'invoiceLines']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Ajoute une ligne à une facture
     */
    public function addInvoiceLine(Invoice $invoice, array $lineData): InvoiceLine
    {
        $line = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'description' => $lineData['description'],
            'quantite' => $lineData['quantite'],
            'prix_unitaire_ht' => $lineData['prix_unitaire_ht'],
            'taux_tva' => $lineData['taux_tva']
        ]);
        
        return $line;
    }
    
    /**
     * Met à jour une facture existante
     */
    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        DB::beginTransaction();
        
        try {
            // Mettre à jour les données de base
            $invoice->update([
                'client_id' => $data['client_id'] ?? $invoice->client_id,
                'numero_facture' => $data['numero_facture'] ?? $invoice->numero_facture,
                'date_facture' => $data['date_facture'] ?? $invoice->date_facture
            ]);
            
            // Mettre à jour les lignes si fournies
            if (isset($data['lines'])) {
                $this->updateInvoiceLines($invoice, $data['lines']);
            }
            
            DB::commit();
            
            return $invoice->load(['client', 'invoiceLines']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Met à jour les lignes d'une facture
     */
    private function updateInvoiceLines(Invoice $invoice, array $lines): void
    {
        // Supprimer les anciennes lignes
        $invoice->invoiceLines()->delete();
        
        // Créer les nouvelles lignes
        foreach ($lines as $lineData) {
            $this->addInvoiceLine($invoice, $lineData);
        }
    }
    
    /**
     * Génère un numéro de facture automatique
     */
    public function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        // Compter les factures du mois en cours
        $count = Invoice::whereYear('date_facture', $year)
            ->whereMonth('date_facture', $month)
            ->count();
        
        $nextNumber = $count + 1;
        
        return sprintf('FAC-%s%s-%04d', $year, $month, $nextNumber);
    }
    
    /**
     * Calcule les statistiques des factures
     */
    public function getInvoiceStatistics(): array
    {
        $totalInvoices = Invoice::count();
        $totalAmount = Invoice::sum('total_ttc');
        $averageAmount = $totalInvoices > 0 ? $totalAmount / $totalInvoices : 0;
        
        $currentMonth = Invoice::whereMonth('date_facture', date('m'))
            ->whereYear('date_facture', date('Y'))
            ->count();
        
        $currentMonthAmount = Invoice::whereMonth('date_facture', date('m'))
            ->whereYear('date_facture', date('Y'))
            ->sum('total_ttc');
        
        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => round($totalAmount, 2),
            'average_amount' => round($averageAmount, 2),
            'current_month_invoices' => $currentMonth,
            'current_month_amount' => round($currentMonthAmount, 2)
        ];
    }
    
    /**
     * Récupère les factures d'un client
     */
    public function getClientInvoices(Client $client, int $perPage = 15)
    {
        return $client->invoices()
            ->with('invoiceLines')
            ->orderBy('date_facture', 'desc')
            ->paginate($perPage);
    }
    
    /**
     * Valide les données d'une facture
     */
    public function validateInvoiceData(array $data): array
    {
        $errors = [];
        
        // Vérifier que le client existe
        if (!isset($data['client_id']) || !Client::find($data['client_id'])) {
            $errors['client_id'] = 'Le client spécifié n\'existe pas.';
        }
        
        // Vérifier l'unicité du numéro de facture
        if (isset($data['numero_facture'])) {
            $existingInvoice = Invoice::where('numero_facture', $data['numero_facture'])
                ->when(isset($data['invoice_id']), function ($query) use ($data) {
                    return $query->where('id', '!=', $data['invoice_id']);
                })
                ->first();
            
            if ($existingInvoice) {
                $errors['numero_facture'] = 'Ce numéro de facture existe déjà.';
            }
        }
        
        // Vérifier les lignes de facture
        if (isset($data['lines']) && is_array($data['lines'])) {
            if (empty($data['lines'])) {
                $errors['lines'] = 'Une facture doit contenir au moins une ligne.';
            } else {
                foreach ($data['lines'] as $index => $line) {
                    if (!isset($line['quantite']) || $line['quantite'] <= 0) {
                        $errors["lines.{$index}.quantite"] = 'La quantité doit être supérieure à 0.';
                    }
                    
                    if (!isset($line['prix_unitaire_ht']) || $line['prix_unitaire_ht'] < 0) {
                        $errors["lines.{$index}.prix_unitaire_ht"] = 'Le prix unitaire ne peut pas être négatif.';
                    }
                    
                    if (!isset($line['taux_tva']) || $line['taux_tva'] < 0 || $line['taux_tva'] > 100) {
                        $errors["lines.{$index}.taux_tva"] = 'Le taux de TVA doit être entre 0 et 100.';
                    }
                }
            }
        }
        
        return $errors;
    }
}