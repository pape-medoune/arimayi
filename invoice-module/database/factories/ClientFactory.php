<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'siret' => $this->faker->unique()->numerify('##############'), // 14 chiffres
            'date_creation' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
        ];
    }

    /**
     * Indicate that the client is a recent client.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_creation' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the client is an old client.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_creation' => $this->faker->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the client has no SIRET.
     */
    public function withoutSiret(): static
    {
        return $this->state(fn (array $attributes) => [
            'siret' => null,
        ]);
    }

    /**
     * Create a client with a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'nom' => $name,
        ]);
    }

    /**
     * Create a client with a specific email.
     */
    public function withEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }
}