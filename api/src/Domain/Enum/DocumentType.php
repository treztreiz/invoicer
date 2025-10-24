<?php

namespace App\Domain\Enum;

enum DocumentType: string
{
    case QUOTE = 'QUOTE';
    case INVOICE = 'INVOICE';
}
