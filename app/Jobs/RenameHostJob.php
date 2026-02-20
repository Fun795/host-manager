<?php

namespace App\Jobs;

use App\Enums\OperationStatusEnum;
use App\Models\Host;
use App\Models\Operation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class RenameHostJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 30;
    public int $backoff = 10;

    public function __construct(public string $operationId)
    {
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $operation = Operation::where('id', $this->operationId)
                ->first();

            if (!$operation) {
                return;
            }

            if (!in_array($operation->status, [OperationStatusEnum::PENDING->value, OperationStatusEnum::PROCESSING->value], true)) {
                return;
            }

            $operation->update(['status' => OperationStatusEnum::PROCESSING->value]);
            $host = Host::where('id', $operation->host_id)->first();

            if (!$host) {
                return;
            }

            $newHostname = $operation->payload['new_hostname'];
            $exists = Host::where('hostname', $newHostname)
                ->where('id', '!=', $host->id)
                ->exists();

            if ($exists) {
                return;
            }

            $host->update(['hostname' => $newHostname]);
            $operation->update([
                'status' => OperationStatusEnum::DONE->value,
                'error' => null,
            ]);
        });
    }

    public function failed(Throwable $exception): void
    {
        Operation::where('id', $this->operationId)->update([
            'status' => OperationStatusEnum::FAILED->value,
            'error' => $exception->getMessage(),
        ]);
    }
}
