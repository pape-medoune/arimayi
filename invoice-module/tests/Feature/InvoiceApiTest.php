<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = Client::factory()->create();
    }

    /**
     * Test de récupération de toutes les factures
     */
    public function test_can_get_all_invoices()
    {
        Invoice::factory()->count(3)->create(['client_id' => $this->client->id]);

        $response = $this->getJson('/api/invoices');

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
                             'updated_at',
                             'client' => [
                                 'id',
                                 'nom',
                                 'email'
                             ]
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
     * Test de création d'une facture avec lignes
     */
    public function test_can_create_invoice_with_lines()
    {
        $invoiceData = [
            'client_id' => $this->client->id,
            'numero_facture' => 'FAC-2024-0001',
            'date_facture' => '2024-01-20',
            'lines' => [
                [
                    'description' => 'Prestation de développement',
                    'quantite' => 10,
                    'prix_unitaire_ht' => 500.00,
                    'taux_tva' => 20.00
                ],
                [
                    'description' => 'Formation',
                    'quantite' => 2,
                    'prix_unitaire_ht' => 800.00,
                    'taux_tva' => 20.00
                ]
            ]
        ];

        $response = $this->postJson('/api/invoices', $invoiceData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'client_id',
                         'numero_facture',
                         'date_facture',
                         'total_ht',
                         'total_tva',
                         'total_ttc',
                         'created_at',
                         'updated_at',
                         'lines' => [
                             '*' => [
                                 'id',
                                 'invoice_id',
                                 'description',
                                 'quantite',
                                 'prix_unitaire_ht',
                                 'taux_tva',
                                 'montant_ht',
                                 'montant_tva',
                                 'montant_ttc',
                                 'created_at',
                                 'updated_at'
                             ]
                         ],
                         'client'
                     ],
                     'message'
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'client_id' => $this->client->id,
                         'numero_facture' => 'FAC-2024-0001',
                         'date_facture' => '2024-01-20',
                         'total_ht' => '6600.00', // (10 * 500) + (2 * 800)
                         'total_tva' => '1320.00', // 6600 * 0.20
                         'total_ttc' => '7920.00'  // 6600 + 1320
                     ]
                 ]);

        // Vérifier que la facture est en base
        $this->assertDatabaseHas('invoices', [
            'client_id' => $this->client->id,
            'numero_facture' => 'FAC-2024-0001',
            'total_ht' => 6600.00
        ]);

        // Vérifier que les lignes sont en base
        $this->assertDatabaseHas('invoice_lines', [
            'description' => 'Prestation de développement',
            'quantite' => 10,
            'prix_unitaire_ht' => 500.00,
            'montant_ht' => 5000.00
        ]);

        $this->assertDatabaseHas('invoice_lines', [
            'description' => 'Formation',
            'quantite' => 2,
            'prix_unitaire_ht' => 800.00,
            'montant_ht' => 1600.00
        ]);
    }

    /**
     * Test de validation lors de la création d'une facture
     */
    public function test_invoice_creation_validation()
    {
        // Test sans client_id (requis)
        $response = $this->postJson('/api/invoices', [
            'date_facture' => '2024-01-20',
            'lines' => []
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['client_id', 'lines']);

        // Test avec client_id inexistant
        $response = $this->postJson('/api/invoices', [
            'client_id' => 999,
            'date_facture' => '2024-01-20',
            'lines' => [
                [
                    'description' => 'Test',
                    'quantite' => 1,
                    'prix_unitaire_ht' => 100,
                    'taux_tva' => 20
                ]
            ]
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['client_id']);

        // Test sans lignes
        $response = $this->postJson('/api/invoices', [
            'client_id' => $this->client->id,
            'date_facture' => '2024-01-20',
            'lines' => []
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['lines']);
    }

    /**
     * Test de récupération d'une facture spécifique
     */
    public function test_can_get_specific_invoice()
    {
        $invoice = Invoice::factory()->create(['client_id' => $this->client->id]);
        InvoiceLine::factory()->count(2)->create(['invoice_id' => $invoice->id]);

        $response = $this->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'client_id',
                         'numero_facture',
                         'date_facture',
                         'total_ht',
                         'total_tva',
                         'total_ttc',
                         'created_at',
                         'updated_at',
                         'lines' => [
                             '*' => [
                                 'id',
                                 'invoice_id',
                                 'description',
                                 'quantite',
                                 'prix_unitaire_ht',
                                 'taux_tva',
                                 'montant_ht',
                                 'montant_tva',
                                 'montant_ttc'
                             ]
                         ],
                         'client'
                     ],
                     'message'
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $invoice->id,
                         'client_id' => $this->client->id
                     ]
                 ]);

        $this->assertCount(2, $response->json('data.lines'));
    }

    /**
     * Test de facture non trouvée
     */
    public function test_invoice_not_found()
    {
        $response = $this->getJson('/api/invoices/999');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Facture non trouvée'
                 ]);
    }

    /**
     * Test de mise à jour d'une facture
     */
    public function test_can_update_invoice()
    {
        $invoice = Invoice::factory()->create(['client_id' => $this->client->id]);
        
        $updateData = [
            'date_facture' => '2024-01-25',
            'lines' => [
                [
                    'description' => 'Prestation modifiée',
                    'quantite' => 5,
                    'prix_unitaire_ht' => 600.00,
                    'taux_tva' => 20.00
                ]
            ]
        ];

        $response = $this->putJson("/api/invoices/{$invoice->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'date_facture' => '2024-01-25',
                         'total_ht' => '3000.00', // 5 * 600
                         'total_tva' => '600.00',  // 3000 * 0.20
                         'total_ttc' => '3600.00'  // 3000 + 600
                     ]
                 ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'date_facture' => '2024-01-25',
            'total_ht' => 3000.00
        ]);
    }

    /**
     * Test de suppression d'une facture
     */
    public function test_can_delete_invoice()
    {
        $invoice = Invoice::factory()->create(['client_id' => $this->client->id]);
        InvoiceLine::factory()->count(2)->create(['invoice_id' => $invoice->id]);

        $response = $this->deleteJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Facture supprimée avec succès'
                 ]);

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('invoice_lines', ['invoice_id' => $invoice->id]);
    }

    /**
     * Test de récupération des statistiques des factures
     */
    public function test_can_get_invoice_stats()
    {
        // Créer quelques factures avec des montants connus
        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'total_ht' => 1000.00,
            'total_tva' => 200.00,
            'total_ttc' => 1200.00,
            'date_facture' => now()->format('Y-m-d')
        ]);

        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'total_ht' => 2000.00,
            'total_tva' => 400.00,
            'total_ttc' => 2400.00,
            'date_facture' => now()->format('Y-m-d')
        ]);

        $response = $this->getJson('/api/invoices/stats');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'total_invoices',
                         'total_amount_ht',
                         'total_amount_tva',
                         'total_amount_ttc',
                         'average_amount_ttc',
                         'invoices_this_month',
                         'amount_this_month'
                     ],
                     'message'
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'total_invoices' => 2,
                         'total_amount_ht' => '3000.00',
                         'total_amount_tva' => '600.00',
                         'total_amount_ttc' => '3600.00',
                         'average_amount_ttc' => '1800.00'
                     ]
                 ]);
    }

    /**
     * Test d'unicité du numéro de facture
     */
    public function test_invoice_number_must_be_unique()
    {
        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'numero_facture' => 'FAC-2024-0001'
        ]);

        $response = $this->postJson('/api/invoices', [
            'client_id' => $this->client->id,
            'numero_facture' => 'FAC-2024-0001',
            'date_facture' => '2024-01-20',
            'lines' => [
                [
                    'description' => 'Test',
                    'quantite' => 1,
                    'prix_unitaire_ht' => 100,
                    'taux_tva' => 20
                ]
            ]
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['numero_facture']);
    }

    /**
     * Test de génération automatique du numéro de facture
     */
    public function test_invoice_number_auto_generation()
    {
        $response = $this->postJson('/api/invoices', [
            'client_id' => $this->client->id,
            'date_facture' => '2024-01-20',
            'lines' => [
                [
                    'description' => 'Test',
                    'quantite' => 1,
                    'prix_unitaire_ht' => 100,
                    'taux_tva' => 20
                ]
            ]
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true
                 ]);

        // Vérifier qu'un numéro a été généré
        $invoiceNumber = $response->json('data.numero_facture');
        $this->assertNotNull($invoiceNumber);
        $this->assertStringStartsWith('FAC-', $invoiceNumber);
    }
}