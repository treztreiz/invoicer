<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum InvoiceScheduleType: string
{
    case RECURRENCE = 'recurrence';
    case INSTALLMENT = 'installment';
}
