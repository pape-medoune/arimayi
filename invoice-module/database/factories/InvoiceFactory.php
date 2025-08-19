<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalHt = $this->faker->randomFloat(2, 100, 10000);
        $totalTva = $totalHt * 0.20; // TVA à 20%
        $totalTtc = $totalHt + $totalTva;

        return [
            'client_id' => Client::factory(),
            'numero_facture' => $this->generateInvoiceNumber(),
            'date_facture' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'total_ht' => $totalHt,
            'total_tva' => $totalTva,
            'total_ttc' => $totalTtc,
        ];
    }

    /**
     * Generate a unique invoice number.
     */
    private function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $random = $this->faker->unique()->numberBetween(1, 9999);
        
        return sprintf('FAC-%s-%s-%04d', $year, $month, $random);
    }

    /**
     * Indicate that the invoice is recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_facture' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the invoice is from this month.
     */
    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_facture' => $this->faker->dateTimeBetween('first day of this month', 'now')->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the invoice is old.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_facture' => $this->faker->dateTimeBetween('-2 years', '-6 months')->format('Y-m-d'),
        ]);
    }

    /**
     * Create an invoice with a specific client.
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
        ]);
    }

    /**
     * Create an invoice with a specific total amount.
     */
    public function withAmount(float $totalHt): static
    {
        $totalTva = $totalHt * 0.20;
        $totalTtc = $totalHt + $totalTva;

        return $this->state(fn (array $attributes) => [
            'total_ht' => $totalHt,
            'total_tva' => $totalTva,
            'total_ttc' => $totalTtc,
        ]);
    }

    /**
     * Create an invoice with a specific invoice number.
     */
    public function withNumber(string $number): static
    {
        return $this->state(fn (array $attributes) => [
            'numero_facture' => $number,
        ]);
    }

    /**
     * Create an invoice with a specific date.
     */
    public function withDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date_facture' => $date,
        ]);
    }

    /**
     * Create a high-value invoice.
     */
    public function highValue(): static
    {
        return $this->withAmount($this->faker->randomFloat(2, 5000, 50000));
    }

    /**
     * Create a low-value invoice.
     */
    public function lowValue(): static
    {
        return $this->withAmount($this->faker->randomFloat(2, 50, 500));
    }
}