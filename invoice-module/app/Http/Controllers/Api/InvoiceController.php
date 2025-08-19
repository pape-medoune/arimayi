<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Services\InvoiceService;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $invoices = Invoice::with(['client', 'invoiceLines'])
                ->orderBy('date_facture', 'desc')
                ->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $invoices,
                'message' => 'Factures récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des factures',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            DB::beginTransaction();

            // Créer la facture
            $invoice = Invoice::create([
                'client_id' => $validatedData['client_id'],
                'numero_facture' => $validatedData['numero_facture'],
                'date_facture' => $validatedData['date_facture'],
                'total_ht' => 0,
                'total_tva' => 0,
                'total_ttc' => 0
            ]);

            // Créer les lignes de facture
            foreach ($validatedData['lines'] as $lineData) {
                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'description' => $lineData['description'],
                    'quantite' => $lineData['quantite'],
                    'prix_unitaire_ht' => $lineData['prix_unitaire_ht'],
                    'taux_tva' => $lineData['taux_tva']
                ]);
            }

            // Recharger la facture avec ses relations
            $invoice->load(['client', 'invoiceLines']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Facture créée avec succès'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $invoice = Invoice::with(['client', 'invoiceLines'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Facture récupérée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Facture non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceRequest $request, string $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);

            $validatedData = $request->validated();

            DB::beginTransaction();

            // Mettre à jour la facture
            $invoice->update([
                'client_id' => $validatedData['client_id'] ?? $invoice->client_id,
                'numero_facture' => $validatedData['numero_facture'] ?? $invoice->numero_facture,
                'date_facture' => $validatedData['date_facture'] ?? $invoice->date_facture
            ]);

            // Mettre à jour les lignes si fournies
            if (isset($validatedData['lines'])) {
                // Supprimer les anciennes lignes
                $invoice->invoiceLines()->delete();

                // Créer les nouvelles lignes
                foreach ($validatedData['lines'] as $lineData) {
                    InvoiceLine::create([
                        'invoice_id' => $invoice->id,
                        'description' => $lineData['description'],
                        'quantite' => $lineData['quantite'],
                        'prix_unitaire_ht' => $lineData['prix_unitaire_ht'],
                        'taux_tva' => $lineData['taux_tva']
                    ]);
                }
            }

            // Recharger la facture avec ses relations
            $invoice->load(['client', 'invoiceLines']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Facture mise à jour avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);
            
            DB::beginTransaction();
            
            // Supprimer les lignes de facture
            $invoice->invoiceLines()->delete();
            
            // Supprimer la facture
            $invoice->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Facture supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les statistiques des factures
     */
    public function getInvoiceStats(): JsonResponse
    {
        try {
            $stats = $this->invoiceService->getInvoiceStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistiques des factures récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
