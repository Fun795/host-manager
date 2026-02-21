<?php

namespace App\Services;

use App\Models\Operation;

class OperationService
{
    public function getById(string $id): Operation
    {
        return Operation::with('host')->findOrFail($id);
    }
}
