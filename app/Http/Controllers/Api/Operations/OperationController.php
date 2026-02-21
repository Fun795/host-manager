<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hosts\CreateHostRequest;
use App\Http\Requests\Hosts\ListHostRequest;
use App\Http\Requests\Hosts\RenameHostRequest;
use App\Http\Resources\HostResource;
use App\Http\Resources\OperationResource;
use App\Services\HostService;
use App\Services\OperationService;
use Illuminate\Http\JsonResponse;

class OperationController extends Controller
{
    public function __construct(OperationService $operationService)
    {
        $this->operationService = $operationService;
    }

    public function get(string $id): JsonResponse
    {
        $result = $this->operationService->getById($id);

        return $this->sendSuccess(new OperationResource($result));
    }
}
