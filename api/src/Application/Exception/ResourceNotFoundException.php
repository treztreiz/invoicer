<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class ResourceNotFoundException extends \RuntimeException
{
    public function __construct(string $resource, string $identifier)
    {
        parent::__construct(sprintf('%s "%s" not found.', $resource, $identifier));
    }
}
