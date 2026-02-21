<?php
namespace Database\Factories;

use App\Enums\OperationStatusEnum;
use App\Models\Host;
use App\Models\Operation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OperationFactory extends Factory
{
    protected $model = Operation::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'type' => 'rename',
            'status' => OperationStatusEnum::PENDING->value,
            'host_id' => Str::uuid()->toString(),
            'payload' => ['new_hostname' => fake()->domainName()],
            'idempotency_key' => Str::uuid()->toString(),
            'error' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
