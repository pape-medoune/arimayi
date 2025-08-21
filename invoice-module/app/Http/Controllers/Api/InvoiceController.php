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
     * @OA\Get(
     *     path="/invoices",
     *     tags={"Invoices"},
     *     summary="Récupère la liste des factures",
     *     description="Retourne une liste paginée de toutes les factures avec leurs clients et lignes",
     *     @OA\Response(
     *         response=200,
     *         description="Liste des factures récupérée avec succès",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="current_page", type="integer", example=1),
     *                         @OA\Property(
     *                             property="data",
     *                             type="array",
     *                             @OA\Items(ref="#/components/schemas/Invoice")
     *                         ),
     *                         @OA\Property(property="total", type="integer", example=25)
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/invoices",
     *     tags={"Invoices"},
     *     summary="Crée une nouvelle facture",
     *     description="Crée une nouvelle facture avec ses lignes et calculs automatiques",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreInvoiceRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Facture créée avec succès",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/Invoice")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/invoices/{id}",
     *     tags={"Invoices"},
     *     summary="Récupère une facture spécifique",
     *     description="Retourne les détails d'une facture avec son client et ses lignes",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la facture",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Facture récupérée avec succès",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/Invoice")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Facture non trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Put(
     *     path="/invoices/{id}",
     *     tags={"Invoices"},
     *     summary="Met à jour une facture",
     *     description="Met à jour une facture existante avec recalcul automatique des totaux",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la facture",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateInvoiceRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Facture mise à jour avec succès",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/Invoice")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Facture non trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
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
     * @OA\Delete(
     *     path="/invoices/{id}",
     *     tags={"Invoices"},
     *     summary="Supprime une facture",
     *     description="Supprime une facture et toutes ses lignes associées",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la facture",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Facture supprimée avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Facture non trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/invoices/stats",
     *     tags={"Invoices"},
     *     summary="Récupère les statistiques des factures",
     *     description="Retourne des statistiques globales sur les factures (totaux, moyennes, etc.)",
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques récupérées avec succès",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/InvoiceStats")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
