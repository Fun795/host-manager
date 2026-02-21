<?php

namespace Database\Factories;

use App\Models\Host;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Host>
 */
class HostFactory extends Factory
{
    protected $model = Host::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'hostname' => fake()->unique()->domainName(),
            'ip' => fake()->ipv4(),
            'tags' => fake()->randomElements(['web', 'prod', 'dev', 'db', 'cache'], random_int(1, 3)),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
