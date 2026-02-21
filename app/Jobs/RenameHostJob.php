<?php

namespace App\Jobs;

use App\Enums\OperationStatusEnum;
use App\Models\Host;
use App\Models\Operation;
use App\Services\LogService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RenameHostJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(public string $idempotencyKey)
    {
    }

    public function handle(LogService $logger): void
    {
        $logService = $logger->withContext(['idempotency_key' => $this->idempotencyKey]);

        DB::transaction(function () use ($logService) {
            $operation = Operation::where('idempotency_key', $this->idempotencyKey)
                ->lockForUpdate()
                ->first();

            if (!$operation) {
                return;
            }
            ($this->attempts() && $this->attempts() > 1) ?
                $logService->warning("Повторная попытка ({$this->attempts()}) переименовать host") :
                $logService->info('Старт Job переименования host');

            if (!in_array($operation->status, [OperationStatusEnum::PENDING->value, OperationStatusEnum::PROCESSING->value], true)) {
                $logService->info('Job прекращена, операция выполнена ранее');
                return;
            }

            $operation->update(['status' => OperationStatusEnum::PROCESSING->value]);
            $host = Host::where('id', $operation->host_id)
                ->lockForUpdate()
                ->first();

            if (!$host) {
                $logService->error('Не найден связанный host', ['host_id' => $operation->host_id]);
                return;
            }

            $newHostname = $operation->payload['new_hostname'];
            $exists = Host::where('hostname', $newHostname)
                ->where('id', '!=', $host->id)
                ->exists();

            if ($exists) {
                $logService->error('Такой hostname уже используется', [
                    'host_id' => $operation->host_id,
                    'new_hostname' => $newHostname
                ]);
                return;
            }

            $host->update(['hostname' => $newHostname]);
            $operation->update([
                'status' => OperationStatusEnum::DONE->value,
                'error' => null,
            ]);
        });

        DB::afterCommit(fn() => $logService->info('Переименование прошло успешно!'));
    }

    public function failed(Throwable $exception): void
    {
        $logger = app(LogService::class)->withContext(['idempotency_key' => $this->idempotencyKey]);
        $logger->error('Ошибка во время выполнения Job переименования: ' . $exception->getMessage());

        Operation::where('idempotency_key', $this->idempotencyKey)->update([
            'status' => OperationStatusEnum::FAILED->value,
            'error' => $exception->getMessage(),
        ]);
    }
}
