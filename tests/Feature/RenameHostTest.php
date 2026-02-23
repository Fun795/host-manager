<?php

use App\Enums\OperationStatusEnum;
use App\Enums\OperationTypeEnum;
use App\Jobs\RenameHostJob;
use App\Models\Host;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RenameHostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_require_authentication(): void
    {
        $response = $this
            ->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->patchJson(route('hosts.rename', ['hostId' => Str::uuid()->toString()]), [
                    'new_hostname' => 'test@example.com']
            );

        $response->assertStatus(401);
    }

    protected function happyPathRequest(
        string  $hostId,
        ?array  $payload = null,
        array   $headers = [],
        ?string $idempotencyKey = null
    ): TestResponse
    {
        $auth = $this->createAdminWithToken();
        $idempotencyKey ??= Str::uuid()->toString();
        $payload ??= ['new_hostname' => 'new-name'];


        return $this->withHeaders([
            'Idempotency-Key' => $idempotencyKey,
            ...$auth['headers'],
            ...$headers
        ])->patchJson(route('hosts.rename', ['hostId' => $hostId]), $payload);
    }

    public function test_require_ability(): void
    {
        $auth = $this->createUserWithToken();
        $response = $this
            ->withHeaders([
                'Idempotency-Key' => Str::uuid()->toString(),
                ...$auth['headers']
            ])
            ->patchJson(route('hosts.rename', ['hostId' => Str::uuid()->toString()]), [
                'new_hostname' => 'app-01',
            ]);

        $response->assertStatus(403);
    }

    public function test_require_idempotency_key(): void
    {
        $auth = $this->createAdminWithToken();
        $response = $this
            ->withHeaders([
                ...$auth['headers']
            ])
            ->patchJson(route('hosts.rename', ['hostId' => Str::uuid()->toString()]), [
                'new_hostname' => 'app-01',
            ]);

        $response->assertStatus(422);
    }

    public function test_idempotency_key_must_be_valid_uuid(): void
    {
        $auth = $this->createAdminWithToken();
        $host = Host::factory()->create();
        $response = $this->withHeaders([
            'Idempotency-Key' => 'not-a-uuid',
            ...$auth['headers']
        ])
            ->patchJson(route('hosts.rename', ['hostId' => $host->id]), [
                'new_hostname' => 'app-01',
            ]);

        $response->assertStatus(422);
    }

    public function test_fails_if_hostname_exists(): void
    {
        $auth = $this->createAdminWithToken();
        $host1 = Host::factory()->create(['hostname' => 'old-name']);
        $host2 = Host::factory()->create(['hostname' => 'existing-name']);

        $response = $this
            ->withHeaders([
                'Idempotency-Key' => Str::uuid()->toString(),
                ...$auth['headers']
            ])
            ->patchJson(route('hosts.rename', ['hostId' => $host1->id]), [
                'new_hostname' => $host2->hostname,
            ]);

        $response->assertStatus(409);
    }

    public function test_rename_host_not_found(): void
    {
        $response = $this->happyPathRequest(Str::uuid());
        $response->assertStatus(404);
    }

    public function test_same_idempotency_key_returns_same_operation(): void
    {
        $host = Host::factory()->create(['hostname' => 'old-name']);
        $idempotencyKey = Str::uuid()->toString();

        // Первый запрос
        $response1 = $this->happyPathRequest($host->id, [
            'new_hostname' => "new-name",
        ], ['idempotency_key' => $idempotencyKey,]);

        $operationId = $response1->json('operation_id');

        // Второй запрос с тем же ключом
        $response2 = $this->happyPathRequest($host->id, [
            'new_hostname' => 'different-name'
        ], ['idempotency_key' => $idempotencyKey,]);

        $response2->assertStatus(202)
            ->assertJsonPath('operation_id', $operationId); // тот же ID

        // В БД только одна операция с этим ключом
        $this->assertDatabaseCount('operations', 1);
        $this->assertDatabaseHas('operations', [
            'idempotency_key' => $idempotencyKey,
            'payload->new_hostname' => 'new-name', // первое значение
        ]);
    }

    public function test_can_request_host_rename(): void
    {
        $host = Host::factory()->create(['hostname' => 'old-name']);
        $response = $this->happyPathRequest($host->id, ['new_hostname' => 'new-name']);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'data' => ['operation_id'],
            ]);

        $this->assertDatabaseHas('operations', [
            'type' => OperationTypeEnum::RENAME->value,
            'status' => OperationStatusEnum::PENDING->value,
            'host_id' => $host->id,
        ]);

        Queue::assertPushed(RenameHostJob::class);
    }

    public function test_rate_limit_exceed(): void
    {
        $host = Host::factory()->create();
        $limit = config('rate_limit.rename_host_throttle_count');

        for ($i = 0; $i < $limit; $i++) {
            $response = $this->happyPathRequest($host->id, [
                'new_hostname' => "new-name-{$i}",
            ]);

            $response->assertStatus(202)
                ->assertJsonStructure([
                    'data' => ['operation_id'],
                ]);
        }

        // Следующий запрос должен быть заблокирован
        $response = $this->happyPathRequest($host->id);

        $response->assertStatus(429);
    }
}
