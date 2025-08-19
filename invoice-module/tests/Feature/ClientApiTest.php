<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ClientApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test de récupération de tous les clients
     */
    public function test_can_get_all_clients()
    {
        // Créer des clients de test
        Client::factory()->count(3)->create();

        $response = $this->getJson('/api/clients');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'nom',
                             'email',
                             'siret',
                             'date_creation',
                             'created_at',
                             'updated_at'
                         ]
                     ],
                     'message'
                 ])
                 ->assertJson([
                     'success' => true
                 ]);

        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test de création d'un client
     */
    public function test_can_create_client()
    {
        $clientData = [
            'nom' => 'Entreprise Test',
            'email' => 'test@entreprise.com',
            'siret' => '12345678901234',
            'date_creation' => '2024-01-15'
        ];

        $response = $this->postJson('/api/clients', $clientData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'nom',
                         'email',
                         'siret',
                         'date_creation',
                         'created_at',
                         'updated_at'
                     ],
                     'message'
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => $clientData
                 ]);

        $this->assertDatabaseHas('clients', $clientData);
    }

    /**
     * Test de validation lors de la création d'un client
     */
    public function test_client_creation_validation()
    {
        // Test sans nom (requis)
        $response = $this->postJson('/api/clients', [
            'email' => 'test@entreprise.com'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nom']);

        // Test avec email invalide
        $response = $this->postJson('/api/clients', [
            'nom' => 'Entreprise Test',
            'email' => 'email-invalide'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);

        // Test avec SIRET invalide (pas 14 caractères)
        $response = $this->postJson('/api/clients', [
            'nom' => 'Entreprise Test',
            'email' => 'test@entreprise.com',
            'siret' => '123456789'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['siret']);
    }

    /**
     * Test de récupération d'un client spécifique
     */
    public function test_can_get_specific_client()
    {
        $client = Client::factory()->create();

        $response = $this->getJson("/api/clients/{$client->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'nom',
                         'email',
                         'siret',
                         'date_creation',
                         'created_at',
                         'updated_at'
                     ],
                     'message'
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $client->id,
                         'nom' => $client->nom,
                         'email' => $client->email
                     ]
                 ]);
    }

    /**
     * Test de client non trouvé
     */
    public function test_client_not_found()
    {
        $response = $this->getJson('/api/clients/999');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Client non trouvé'
                 ]);
    }

    /**
     * Test de mise à jour d'un client
     */
    public function test_can_update_client()
    {
        $client = Client::factory()->create();
        
        $updateData = [
            'nom' => 'Nom Modifié',
            'email' => 'nouveau@email.com'
        ];

        $response = $this->putJson("/api/clients/{$client->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => $updateData
                 ]);

        $this->assertDatabaseHas('clients', array_merge(
            ['id' => $client->id],
            $updateData
        ));
    }

    /**
     * Test de suppression d'un client sans factures
     */
    public function test_can_delete_client_without_invoices()
    {
        $client = Client::factory()->create();

        $response = $this->deleteJson("/api/clients/{$client->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Client supprimé avec succès'
                 ]);

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    /**
     * Test de suppression d'un client avec factures (doit échouer)
     */
    public function test_cannot_delete_client_with_invoices()
    {
        $client = Client::factory()->create();
        Invoice::factory()->create(['client_id' => $client->id]);

        $response = $this->deleteJson("/api/clients/{$client->id}");

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Impossible de supprimer le client car il a des factures associées'
                 ]);

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    /**
     * Test de récupération des factures d'un client
     */
    public function test_can_get_client_invoices()
    {
        $client = Client::factory()->create();
        Invoice::factory()->count(2)->create(['client_id' => $client->id]);

        $response = $this->getJson("/api/clients/{$client->id}/invoices");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'client_id',
                             'numero_facture',
                             'date_facture',
                             'total_ht',
                             'total_tva',
                             'total_ttc',
                             'created_at',
                             'updated_at'
                         ]
                     ],
                     'message'
                 ])
                 ->assertJson([
                     'success' => true
                 ]);

        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test d'unicité de l'email
     */
    public function test_email_must_be_unique()
    {
        $existingClient = Client::factory()->create([
            'email' => 'existing@test.com'
        ]);

        $response = $this->postJson('/api/clients', [
            'nom' => 'Nouveau Client',
            'email' => 'existing@test.com'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test d'unicité du SIRET
     */
    public function test_siret_must_be_unique()
    {
        $existingClient = Client::factory()->create([
            'siret' => '12345678901234'
        ]);

        $response = $this->postJson('/api/clients', [
            'nom' => 'Nouveau Client',
            'email' => 'nouveau@test.com',
            'siret' => '12345678901234'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['siret']);
    }
}