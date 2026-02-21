<?php

namespace Tests\Feature;

use App\Enums\OperationStatusEnum;
use App\Models\Host;
use App\Models\Operation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GetOperationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_with_host(): void
    {
        $host = Host::factory()->create();
        $operation = Operation::factory()->create([
            'host_id' => $host->id,
            'status' => OperationStatusEnum::DONE->value,
        ]);

        $response = $this->getJson(route('operations.get', ['operationId' => $operation->id]));
        $response->assertStatus(200)
            ->assertJsonStructure(
                [
                    'data' => [
                        'id', 'type', 'status', 'payload', 'error',
                        'created_at', 'updated_at', 'host',
                    ]
                ])
            ->assertJsonPath('data.host.id', (string)$host->id);
    }

    public function test_requires_uuid_format(): void
    {
        $response = $this->getJson(route('operations.get', ['operationId' => 15]));
        $response->assertStatus(404);
    }

    public function test_not_found(): void
    {
        $response = $this->getJson('/api/operations/' . Str::uuid());

        $response->assertStatus(404);
    }
}
