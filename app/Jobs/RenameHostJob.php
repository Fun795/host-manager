<?php

namespace App\Jobs;

use App\Enums\OperationStatusEnum;
use App\Exceptions\UnrecoverableJobException;
use App\Models\Host;
use App\Models\Operation;
use App\Services\LogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
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

        try {
            DB::transaction(function () use ($logService) {
                ($this->attempts() && $this->attempts() > 1) ?
                    $logService->warning("Повторная попытка ({$this->attempts()}) переименовать host") :
                    $logService->info('Старт Job переименования host');

                $operation = Operation::where('idempotency_key', $this->idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if (!$operation) {
                    throw new UnrecoverableJobException('Операция переименования не найдена');
                }

                if (!in_array($operation->status, [OperationStatusEnum::PENDING->value, OperationStatusEnum::PROCESSING->value], true)) {
                    $logService->info('Операция выполнена ранее', ['idempotency_key' => $this->idempotencyKey]);
                    return;
                }

                $operation->update(['status' => OperationStatusEnum::PROCESSING->value]);
                $host = Host::where('id', $operation->host_id)
                    ->lockForUpdate()
                    ->first();

                $newHostname = $operation->payload['new_hostname'];
                $exists = Host::where('hostname', $newHostname)
                    ->where('id', '!=', $host->id)
                    ->exists();

                if ($exists) {
                    throw new UnrecoverableJobException('Такой hostname уже используется');
                }

                $host->update(['hostname' => $newHostname]);
                $operation->update([
                    'status' => OperationStatusEnum::DONE->value,
                    'error' => null,
                ]);

                $logService->info('Переименование прошло успешно!');
            });
        } catch (UnrecoverableJobException $e) {
            $this->failed($e);
            return;
        }
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
