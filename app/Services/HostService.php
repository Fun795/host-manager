<?php

namespace App\Services;

use App\Models\Host;
use App\Models\Operation;
use Illuminate\Pagination\CursorPaginator;
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

