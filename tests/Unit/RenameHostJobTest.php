<?php

namespace Tests\Unit;

use App\Enums\OperationStatusEnum;
use App\Jobs\RenameHostJob;
use App\Models\Host;
use App\Models\Operation;
use App\Services\LogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenameHostJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->instance(LogService::class, new LogService(null));
    }

    private function jobHandle($idempotencyKey, $failed = false): void
    {
        $job = (new RenameHostJob($idempotencyKey));

        if ($failed) {
            $job->failed(new \Exception('Test error'));
        } else {
            try {
                $job->handle(app(LogService::class));
            } catch (\Throwable $e) {
                $job->failed($e);
            }
        }
    }

    public function test_job_renames_host_successfully(): void
    {
        $host = Host::factory()->create(['hostname' => 'old-name']);
        $operation = Operation::factory()->create([
            'host_id' => $host->id,
            'payload' => ['new_hostname' => 'new-name'],
        ]);

        $this->jobHandle($operation->idempotency_key);

        $this->assertDatabaseHas('hosts', [
            'id' => $host->id,
            'hostname' => 'new-name',
        ]);

        $this->assertDatabaseHas('operations', [
            'id' => $operation->id,
            'status' => OperationStatusEnum::DONE->value,
            'error' => null,
        ]);
    }

    public function test_job_fails_if_hostname_exists(): void
    {
        Host::factory()->create(['hostname' => 'existing-name']);

        $hostMustChanged = Host::factory()->create(['hostname' => 'old-name']);
        $operation = Operation::factory()->create([
            'host_id' => $hostMustChanged->id,
            'payload' => ['new_hostname' => 'existing-name'],
        ]);

        $this->jobHandle($operation->idempotency_key);

        // Хост не изменился
        $this->assertDatabaseHas('hosts', [
            'id' => $hostMustChanged->id,
            'hostname' => 'old-name',
        ]);

        // Статус операции стал FAILED
        $this->assertDatabaseHas('operations', [
            'id' => $operation->id,
            'status' => OperationStatusEnum::FAILED->value,
            'idempotency_key' => $operation->idempotency_key,
        ]);
    }

    public function test_job_skips_if_operation_already_done(): void
    {
        $host = Host::factory()->create(['hostname' => 'old-name']);
        $operation = Operation::factory()->create([
            'host_id' => $host->id,
            'status' => OperationStatusEnum::DONE->value,
            'payload' => ['new_hostname' => 'new-name'],
        ]);

        $this->jobHandle($operation->idempotency_key);
        $this->assertDatabaseHas('hosts', [
            'id' => $host->id,
            'hostname' => 'old-name', // не изменился
        ]);

        // Статус остался DONE
        $this->assertDatabaseHas('operations', [
            'id' => $operation->id,
            'status' => OperationStatusEnum::DONE->value,
        ]);
    }

    public function test_job_failed_method_updates_operation(): void
    {
        $operation = Operation::factory()->create([
            'host_id' => Host::factory()->create()->id,
            'status' => OperationStatusEnum::PROCESSING->value,
        ]);

        $this->jobHandle($operation->idempotency_key, true);

        $this->assertDatabaseHas('operations', [
            'id' => $operation->id,
            'status' => OperationStatusEnum::FAILED->value,
            'error' => 'Test error',
        ]);
    }
}
