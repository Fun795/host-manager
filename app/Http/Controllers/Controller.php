<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    public function sendSuccessCreated($data = [], $message = null): JsonResponse
    {
        $responseBody = array_merge(
            array_filter(['message' => $message]),
            ['data' => $data]
        );

        return response()->json($responseBody, 201);
    }

    public function sendSuccessAccepted($data = [], $message = null): JsonResponse
    {
        $responseBody = array_merge(
            array_filter(['message' => $message]),
            ['data' => $data]
        );

        return response()->json($responseBody, 202);
    }

    public function sendSuccessPaginated($paginatedData = []): JsonResponse
    {
        return response()->json([
            'data' => [
                'items' => $paginatedData->items(),
                'page' => [
                    'size' => $paginatedData->perPage(),
                    'previous_url' => $paginatedData->previousPageUrl(),
                    'next_url' => $paginatedData->nextPageUrl(),
                ]
            ],
        ]);
    }
}
