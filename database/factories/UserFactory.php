<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $contrasena;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'correo' => fake()->unique()->safeEmail(),
            'contrasena' => static::$contrasena ??= Str::random(24),
            'rol' => 'inquilino',
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'dni' => fake()->unique()->numerify('########'),
            'estado' => true,
            'token_recordar' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'correo_verificado_en' => null,
        ]);
    }
}
