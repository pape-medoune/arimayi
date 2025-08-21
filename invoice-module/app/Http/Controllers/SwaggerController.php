<?php

namespace App\Http\Controllers;

use Illuminate\Http\Controller;

/**
 * @OA\Info(
 *     title="API Module de Facturation",
 *     version="1.0.0",
 *     description="API REST pour la gestion des clients et factures avec calculs automatiques de TVA et totaux.",
 *     @OA\Contact(
 *         email="support@facturation.com",
 *         name="Support Technique"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Serveur de développement"
 * )
 *
 * @OA\Server(
 *     url="https://api.facturation.com/api",
 *     description="Serveur de production"
 * )
 *
 * @OA\Tag(
 *     name="Health",
 *     description="Vérification de l'état de l'API"
 * )
 *
 * @OA\Tag(
 *     name="Clients",
 *     description="Gestion des clients"
 * )
 *
 * @OA\Tag(
 *     name="Invoices",
 *     description="Gestion des factures"
 * )
 *
 * @OA\Schema(
 *     schema="Client",
 *     type="object",
 *     required={"nom", "email", "siret", "date_creation"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", maxLength=255, example="Entreprise ACME"),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, example="contact@acme.com"),
 *     @OA\Property(property="siret", type="string", pattern="^[0-9]{14}$", example="12345678901234"),
 *     @OA\Property(property="date_creation", type="string", format="date", example="2024-01-15"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="Invoice",
 *     type="object",
 *     required={"client_id", "numero_facture", "date_facture"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="client_id", type="integer", example=1),
 *     @OA\Property(property="numero_facture", type="string", maxLength=50, example="FAC-2024-001"),
 *     @OA\Property(property="date_facture", type="string", format="date", example="2024-01-15"),
 *     @OA\Property(property="total_ht", type="number", format="float", example=1000.00),
 *     @OA\Property(property="total_tva", type="number", format="float", example=200.00),
 *     @OA\Property(property="total_ttc", type="number", format="float", example=1200.00),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *     @OA\Property(
 *         property="client",
 *         ref="#/components/schemas/Client"
 *     ),
 *     @OA\Property(
 *         property="lines",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/InvoiceLine")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="InvoiceLine",
 *     type="object",
 *     required={"description", "quantite", "prix_unitaire_ht", "taux_tva"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="invoice_id", type="integer", example=1),
 *     @OA\Property(property="description", type="string", maxLength=500, example="Développement application web"),
 *     @OA\Property(property="quantite", type="number", format="float", minimum=0.01, example=10.5),
 *     @OA\Property(property="prix_unitaire_ht", type="number", format="float", minimum=0.01, example=95.24),
 *     @OA\Property(property="taux_tva", type="number", format="float", minimum=0, maximum=100, example=20.0),
 *     @OA\Property(property="total_ht", type="number", format="float", example=1000.02),
 *     @OA\Property(property="total_tva", type="number", format="float", example=200.00),
 *     @OA\Property(property="total_ttc", type="number", format="float", example=1200.02),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="StoreClientRequest",
 *     type="object",
 *     required={"nom", "email", "siret", "date_creation"},
 *     @OA\Property(property="nom", type="string", maxLength=255, example="Entreprise ACME"),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, example="contact@acme.com"),
 *     @OA\Property(property="siret", type="string", pattern="^[0-9]{14}$", example="12345678901234"),
 *     @OA\Property(property="date_creation", type="string", format="date", example="2024-01-15")
 * )
 *
 * @OA\Schema(
 *     schema="UpdateClientRequest",
 *     type="object",
 *     @OA\Property(property="nom", type="string", maxLength=255, example="Entreprise ACME Modifiée"),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, example="nouveau@acme.com"),
 *     @OA\Property(property="siret", type="string", pattern="^[0-9]{14}$", example="98765432109876"),
 *     @OA\Property(property="date_creation", type="string", format="date", example="2024-01-20")
 * )
 *
 * @OA\Schema(
 *     schema="StoreInvoiceRequest",
 *     type="object",
 *     required={"client_id", "numero_facture", "date_facture", "lines"},
 *     @OA\Property(property="client_id", type="integer", example=1),
 *     @OA\Property(property="numero_facture", type="string", maxLength=50, example="FAC-2024-001"),
 *     @OA\Property(property="date_facture", type="string", format="date", example="2024-01-15"),
 *     @OA\Property(
 *         property="lines",
 *         type="array",
 *         minItems=1,
 *         @OA\Items(
 *             type="object",
 *             required={"description", "quantite", "prix_unitaire_ht", "taux_tva"},
 *             @OA\Property(property="description", type="string", maxLength=500, example="Développement application web"),
 *             @OA\Property(property="quantite", type="number", format="float", minimum=0.01, example=10.5),
 *             @OA\Property(property="prix_unitaire_ht", type="number", format="float", minimum=0.01, example=95.24),
 *             @OA\Property(property="taux_tva", type="number", format="float", minimum=0, maximum=100, example=20.0)
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UpdateInvoiceRequest",
 *     type="object",
 *     @OA\Property(property="client_id", type="integer", example=1),
 *     @OA\Property(property="numero_facture", type="string", maxLength=50, example="FAC-2024-001-MOD"),
 *     @OA\Property(property="date_facture", type="string", format="date", example="2024-01-20"),
 *     @OA\Property(
 *         property="lines",
 *         type="array",
 *         minItems=1,
 *         @OA\Items(
 *             type="object",
 *             required={"description", "quantite", "prix_unitaire_ht", "taux_tva"},
 *             @OA\Property(property="description", type="string", maxLength=500, example="Développement application web modifié"),
 *             @OA\Property(property="quantite", type="number", format="float", minimum=0.01, example=12.0),
 *             @OA\Property(property="prix_unitaire_ht", type="number", format="float", minimum=0.01, example=100.00),
 *             @OA\Property(property="taux_tva", type="number", format="float", minimum=0, maximum=100, example=20.0)
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Opération réussie"),
 *     @OA\Property(property="data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Une erreur s'est produite"),
 *     @OA\Property(property="errors", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Erreur de validation"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         @OA\Property(
 *             property="email",
 *             type="array",
 *             @OA\Items(type="string", example="L'email est obligatoire.")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="InvoiceStats",
 *     type="object",
 *     @OA\Property(property="total_invoices", type="integer", example=150),
 *     @OA\Property(property="total_amount_ht", type="number", format="float", example=125000.00),
 *     @OA\Property(property="total_amount_tva", type="number", format="float", example=25000.00),
 *     @OA\Property(property="total_amount_ttc", type="number", format="float", example=150000.00),
 *     @OA\Property(property="average_amount_ttc", type="number", format="float", example=1000.00),
 *     @OA\Property(property="invoices_this_month", type="integer", example=12),
 *     @OA\Property(property="amount_this_month_ttc", type="number", format="float", example=15000.00)
 * )
 */
class SwaggerController extends Controller
{
    // Ce contrôleur sert uniquement à centraliser les annotations Swagger/OpenAPI
    // Les annotations ci-dessus définissent la structure complète de l'API
}