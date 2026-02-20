<?php

namespace App\Enums;

enum OperationStatusEnum: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case DONE = 'done';
    case FAILED = 'failed';
}
