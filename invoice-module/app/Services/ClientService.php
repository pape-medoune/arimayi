<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Exception;

class ClientService
{
    /**
     * Crée un nouveau client
     */
    public function createClient(array $data): Client
    {
        try {
            return Client::create($data);
        } catch (Exception $e) {
            throw new Exception('Erreur lors de la création du client: ' . $e->getMessage());
        }
    }
    
    /**
     * Met à jour un client existant
     */
    public function updateClient(Client $client, array $data): Client
    {
        try {
            $client->update($data);
            return $client->fresh();
        } catch (Exception $e) {
            throw new Exception('Erreur lors de la mise à jour du client: ' . $e->getMessage());
        }
    }
    
    /**
     * Supprime un client
     */
    public function deleteClient(Client $client): bool
    {
        // Vérifier s'il y a des factures associées
        if ($client->invoices()->count() > 0) {
            throw new Exception('Impossible de supprimer le client car il a des factures associées.');
        }
        
        try {
            return $client->delete();
        } catch (Exception $e) {
            throw new Exception('Erreur lors de la suppression du client: ' . $e->getMessage());
        }
    }
    
    /**
     * Récupère tous les clients avec pagination
     */
    public function getAllClients(int $perPage = 15)
    {
        return Client::with('invoices')
            ->orderBy('nom')
            ->paginate($perPage);
    }
    
    /**
     * Recherche des clients par nom ou email
     */
    public function searchClients(string $search, int $perPage = 15)
    {
        return Client::where('nom', 'LIKE', "%{$search}%")
            ->orWhere('email', 'LIKE', "%{$search}%")
            ->orWhere('siret', 'LIKE', "%{$search}%")
            ->with('invoices')
            ->orderBy('nom')
            ->paginate($perPage);
    }
    
    /**
     * Récupère les statistiques des clients
     */
    public function getClientStatistics(): array
    {
        $totalClients = Client::count();
        $clientsWithInvoices = Client::has('invoices')->count();
        $clientsWithoutInvoices = $totalClients - $clientsWithInvoices;
        
        // Top 5 des clients par chiffre d'affaires
        $topClients = Client::select('clients.*')
            ->selectRaw('SUM(invoices.total_ttc) as total_ca')
            ->leftJoin('invoices', 'clients.id', '=', 'invoices.client_id')
            ->groupBy('clients.id')
            ->orderByDesc('total_ca')
            ->limit(5)
            ->get();
        
        return [
            'total_clients' => $totalClients,
            'clients_with_invoices' => $clientsWithInvoices,
            'clients_without_invoices' => $clientsWithoutInvoices,
            'top_clients' => $topClients
        ];
    }
    
    /**
     * Valide les données d'un client
     */
    public function validateClientData(array $data, ?int $clientId = null): array
    {
        $errors = [];
        
        // Validation du nom
        if (!isset($data['nom']) || empty(trim($data['nom']))) {
            $errors['nom'] = 'Le nom du client est obligatoire.';
        } elseif (strlen($data['nom']) > 255) {
            $errors['nom'] = 'Le nom du client ne peut pas dépasser 255 caractères.';
        }
        
        // Validation de l'email
        if (!isset($data['email']) || empty(trim($data['email']))) {
            $errors['email'] = 'L\'email du client est obligatoire.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email du client n\'est pas valide.';
        } else {
            // Vérifier l'unicité de l'email
            $existingClient = Client::where('email', $data['email'])
                ->when($clientId, function ($query) use ($clientId) {
                    return $query->where('id', '!=', $clientId);
                })
                ->first();
            
            if ($existingClient) {
                $errors['email'] = 'Cet email est déjà utilisé par un autre client.';
            }
        }
        
        // Validation du SIRET
        if (!isset($data['siret']) || empty(trim($data['siret']))) {
            $errors['siret'] = 'Le SIRET du client est obligatoire.';
        } elseif (strlen($data['siret']) !== 14) {
            $errors['siret'] = 'Le SIRET doit contenir exactement 14 caractères.';
        } elseif (!ctype_digit($data['siret'])) {
            $errors['siret'] = 'Le SIRET ne doit contenir que des chiffres.';
        } else {
            // Vérifier l'unicité du SIRET
            $existingClient = Client::where('siret', $data['siret'])
                ->when($clientId, function ($query) use ($clientId) {
                    return $query->where('id', '!=', $clientId);
                })
                ->first();
            
            if ($existingClient) {
                $errors['siret'] = 'Ce SIRET est déjà utilisé par un autre client.';
            }
        }
        
        // Validation de la date de création
        if (!isset($data['date_creation']) || empty($data['date_creation'])) {
            $errors['date_creation'] = 'La date de création est obligatoire.';
        } elseif (!strtotime($data['date_creation'])) {
            $errors['date_creation'] = 'La date de création n\'est pas valide.';
        }
        
        return $errors;
    }
    
    /**
     * Formate les données d'un client pour l'affichage
     */
    public function formatClientData(Client $client): array
    {
        return [
            'id' => $client->id,
            'nom' => $client->nom,
            'email' => $client->email,
            'siret' => $client->siret,
            'date_creation' => $client->date_creation->format('d/m/Y'),
            'nombre_factures' => $client->invoices->count(),
            'chiffre_affaires' => $client->invoices->sum('total_ttc'),
            'derniere_facture' => $client->invoices->sortByDesc('date_facture')->first()?->date_facture?->format('d/m/Y')
        ];
    }
}