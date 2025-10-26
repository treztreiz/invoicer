<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum InvoiceStatus: string
{
    case DRAFT = 'DRAFT';
    case ISSUED = 'ISSUED';
    case OVERDUE = 'OVERDUE';
    case PAID = 'PAID';
    case VOIDED = 'VOIDED';
}
