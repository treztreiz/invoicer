<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Contracts;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;

interface CheckAwareSchemaManagerInterface
{
    public function createComparator(): Comparator;

    public function introspectSchema(): Schema;
}
