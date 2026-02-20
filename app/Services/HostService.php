<?php

namespace App\Services;

use App\Enums\OperationStatusEnum;
use App\Jobs\RenameHostJob;
use App\Models\Host;
use App\Models\Operation;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class HostService
{
    public function create(array $data): Host
    {
        $existingHost = Host::where('hostname', $data['hostname'])
            ->first();

        if ($existingHost) {
            throw new ConflictHttpException("Хост с таким hostname уже существует");
        }

        return Host::create($data);
    }

    public function list(array $data): CursorPaginator
    {
        $hostQuery = Host::query()
            ->select('*');

        $this->applySearch($hostQuery, $data);

        return $hostQuery->orderBy('id')
            ->cursorPaginate(
                $data['page']['size'] ?? 15,
                'id',
                'page[after]',
                $data['page']['after'] ?? null
            );
    }

    private function applySearch(&$query, $data): void
    {
        if (!empty($data['q'])) {
            $searchString = mb_strtolower($data['q']);

            $query->whereLike('hostname', "%{$searchString}%");

            if (filter_var($searchString, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $query->orWhere('ip', $searchString);
            }
        }
    }

    public function rename(string $hostId, array $data): Operation
    {
        $existingOperation = Operation::with('host')
            ->where('idempotency_key', $data['header_idempotency_key'])
            ->first();

        if ($existingOperation) {
            return $existingOperation;
        }

        $host = Host::findOrFail($hostId);

        if ($host->hostname === $data['new_hostname']) {
            throw new ConflictHttpException('Этот hostname уже используется');
        }

        $operation = Operation::create(
            [
                'status' => OperationStatusEnum::PENDING->value,
                'type' => 'rename',
                'host_id' => $host->id,
                'payload' => Arr::except($data, 'header_idempotency_key'),
                'idempotency_key' => $data['header_idempotency_key'],
            ]
        );

        RenameHostJob::dispatch($operation->id);
        return $operation;
    }
}
