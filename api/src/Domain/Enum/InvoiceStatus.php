<?php

namespace App\Domain\Enum;

enum InvoiceStatus: string
{
    case DRAFT = 'DRAFT';
    case ISSUED = 'ISSUED';
    case OVERDUE = 'OVERDUE';
    case PAID = 'PAID';
    case VOIDED = 'VOIDED';
}
