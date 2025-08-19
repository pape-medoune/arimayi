<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceLine>
 */
class InvoiceLineFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = InvoiceLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $descriptions = [
            'Prestation de développement web',
            'Consultation technique',
            'Formation utilisateur',
            'Maintenance applicative',
            'Audit de sécurité',
            'Intégration API',
            'Développement mobile',
            'Optimisation performance',
            'Support technique',
            'Analyse fonctionnelle',
            'Tests et recette',
            'Documentation technique',
            'Hébergement mensuel',
            'Licence logicielle',
            'Sauvegarde et restauration'
        ];

        $quantite = $this->faker->randomFloat(2, 0.5, 100);
        $prixUnitaireHt = $this->faker->randomFloat(2, 50, 1000);
        $tauxTva = $this->faker->randomElement([0, 5.5, 10, 20]); // Taux de TVA français courants
        
        $montantHt = $quantite * $prixUnitaireHt;
        $montantTva = $montantHt * ($tauxTva / 100);
        $montantTtc = $montantHt + $montantTva;

        return [
            'invoice_id' => Invoice::factory(),
            'description' => $this->faker->randomElement($descriptions),
            'quantite' => $quantite,
            'prix_unitaire_ht' => $prixUnitaireHt,
            'taux_tva' => $tauxTva,
            'montant_ht' => $montantHt,
            'montant_tva' => $montantTva,
            'montant_ttc' => $montantTtc,
        ];
    }

    /**
     * Create a line for a specific invoice.
     */
    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoice->id,
        ]);
    }

    /**
     * Create a development service line.
     */
    public function developmentService(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'Prestation de développement web',
            'quantite' => $this->faker->randomFloat(2, 10, 100),
            'prix_unitaire_ht' => $this->faker->randomFloat(2, 400, 800),
            'taux_tva' => 20,
        ])->recalculateAmounts();
    }

    /**
     * Create a consulting service line.
     */
    public function consultingService(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'Consultation technique',
            'quantite' => $this->faker->randomFloat(2, 1, 20),
            'prix_unitaire_ht' => $this->faker->randomFloat(2, 600, 1200),
            'taux_tva' => 20,
        ])->recalculateAmounts();
    }

    /**
     * Create a training service line.
     */
    public function trainingService(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'Formation utilisateur',
            'quantite' => $this->faker->randomFloat(2, 1, 5),
            'prix_unitaire_ht' => $this->faker->randomFloat(2, 800, 1500),
            'taux_tva' => 20,
        ])->recalculateAmounts();
    }

    /**
     * Create a line with specific quantity and unit price.
     */
    public function withQuantityAndPrice(float $quantite, float $prixUnitaireHt, float $tauxTva = 20): static
    {
        return $this->state(fn (array $attributes) => [
            'quantite' => $quantite,
            'prix_unitaire_ht' => $prixUnitaireHt,
            'taux_tva' => $tauxTva,
        ])->recalculateAmounts();
    }

    /**
     * Create a line with no VAT.
     */
    public function withoutVat(): static
    {
        return $this->state(fn (array $attributes) => [
            'taux_tva' => 0,
        ])->recalculateAmounts();
    }

    /**
     * Create a line with reduced VAT rate.
     */
    public function withReducedVat(): static
    {
        return $this->state(fn (array $attributes) => [
            'taux_tva' => 5.5,
        ])->recalculateAmounts();
    }

    /**
     * Create a line with specific description.
     */
    public function withDescription(string $description): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description,
        ]);
    }

    /**
     * Recalculate amounts based on quantity, unit price and VAT rate.
     */
    private function recalculateAmounts(): static
    {
        return $this->state(function (array $attributes) {
            $quantite = $attributes['quantite'] ?? 1;
            $prixUnitaireHt = $attributes['prix_unitaire_ht'] ?? 0;
            $tauxTva = $attributes['taux_tva'] ?? 20;
            
            $montantHt = $quantite * $prixUnitaireHt;
            $montantTva = $montantHt * ($tauxTva / 100);
            $montantTtc = $montantHt + $montantTva;

            return [
                'montant_ht' => round($montantHt, 2),
                'montant_tva' => round($montantTva, 2),
                'montant_ttc' => round($montantTtc, 2),
            ];
        });
    }

    /**
     * Create a high-value line.
     */
    public function highValue(): static
    {
        return $this->withQuantityAndPrice(
            $this->faker->randomFloat(2, 10, 50),
            $this->faker->randomFloat(2, 500, 2000)
        );
    }

    /**
     * Create a low-value line.
     */
    public function lowValue(): static
    {
        return $this->withQuantityAndPrice(
            $this->faker->randomFloat(2, 0.5, 5),
            $this->faker->randomFloat(2, 20, 200)
        );
    }
}