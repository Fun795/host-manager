<?php

namespace App\Http\Controllers\Api\Hosts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hosts\CreateHostRequest;
use App\Http\Requests\Hosts\ListHostRequest;
use App\Http\Requests\Hosts\RenameHostRequest;
use App\Http\Resources\HostResource;
use App\Services\HostService;
use Illuminate\Http\JsonResponse;

class HostController extends Controller
{
    public function __construct(HostService $hostService)
    {
        parent::__construct();

        $this->hostService = $hostService;
    }

    public function create(CreateHostRequest $request): JsonResponse
    {
        $result = $this->hostService->create($request->validated());

        return $this->sendSuccessCreated(new HostResource($result));
    }

    public function list(ListHostRequest $request): JsonResponse
    {
        $result = $this->hostService->list($request->validated());

        return $this->sendSuccessPaginated(HostResource::collection($result));
    }

