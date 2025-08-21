<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ClientService;
use App\Services\InvoiceService;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    protected ClientService $clientService;
    protected InvoiceService $invoiceService;

    public function __construct(ClientService $clientService, InvoiceService $invoiceService)
    {
        $this->clientService = $clientService;
        $this->invoiceService = $invoiceService;
    }
    /**
     * @OA\Get(
     *     path="/clients",
     *     tags={"Clients"},
     *     summary="Récupère la liste des clients",
     *     description="Retourne une liste paginée de tous les clients avec leurs factures",
     *     @OA\Response(
     *         response=200,
     *         description="Liste des clients récupérée avec succès",
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
     *                             @OA\Items(ref="#/components/schemas/Client")
     *                         ),
     *                         @OA\Property(property="total", type="integer", example=50)
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
            $clients = Client::with('invoices')->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $clients,
                'message' => 'Clients récupérés avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des clients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/clients",
     *     tags={"Clients"},
     *     summary="Crée un nouveau client",
     *     description="Crée un nouveau client avec validation des données",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreClientRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Client créé avec succès",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/Client")
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
    public function store(StoreClientRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $client = Client::create($validated);

            return response()->json([
                'success' => true,
                'data' => $client,
                'message' => 'Client créé avec succès'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/clients/{id}",
     *     tags={"Clients"},
     *     summary="Récupère un client spécifique",
     *     description="Retourne les détails d'un client avec ses factures et lignes de facture",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client récupéré avec succès",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/Client")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $client = Client::with('invoices.invoiceLines')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $client,
                'message' => 'Client récupéré avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Client non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/clients/{id}",
     *     tags={"Clients"},
     *     summary="Met à jour un client",
     *     description="Met à jour les informations d'un client existant",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateClientRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client mis à jour avec succès",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/Client")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client non trouvé",
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
    public function update(UpdateClientRequest $request, string $id): JsonResponse
    {
        try {
            $client = Client::findOrFail($id);
            $validated = $request->validated();
            $client->update($validated);

            return response()->json([
                'success' => true,
                'data' => $client->fresh(),
                'message' => 'Client mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/clients/{id}",
     *     tags={"Clients"},
     *     summary="Supprime un client",
     *     description="Supprime un client s'il n'a pas de factures associées",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client supprimé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Impossible de supprimer le client car il a des factures associées",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $client = Client::findOrFail($id);
            
            // Vérifier s'il y a des factures associées
            if ($client->invoices()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer le client car il a des factures associées'
                ], 409);
            }

            $client->delete();

            return response()->json([
                'success' => true,
                'message' => 'Client supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/clients/{id}/invoices",
     *     tags={"Clients"},
     *     summary="Récupère les factures d'un client",
     *     description="Retourne toutes les factures associées à un client spécifique",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Factures du client récupérées avec succès",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/Invoice")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * Récupère les factures d'un client
     */
    public function getClientInvoices(Client $client): JsonResponse
    {
        try {
            $invoices = $this->invoiceService->getClientInvoices($client);

            return response()->json([
                'success' => true,
                'data' => $invoices,
                'message' => 'Factures du client récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des factures du client',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
