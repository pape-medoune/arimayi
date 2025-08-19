<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ClientService;
use App\Services\InvoiceService;
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'nom' => 'required|string|max:255',
                'email' => 'required|email|unique:clients,email',
                'siret' => 'required|string|size:14|unique:clients,siret',
                'date_creation' => 'required|date'
            ]);

            $client = Client::create($validatedData);

            return response()->json([
                'success' => true,
                'data' => $client,
                'message' => 'Client créé avec succès'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du client',
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $client = Client::findOrFail($id);

            $validatedData = $request->validate([
                'nom' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:clients,email,' . $id,
                'siret' => 'sometimes|required|string|size:14|unique:clients,siret,' . $id,
                'date_creation' => 'sometimes|required|date'
            ]);

            $client->update($validatedData);

            return response()->json([
                'success' => true,
                'data' => $client,
                'message' => 'Client mis à jour avec succès'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du client',
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
