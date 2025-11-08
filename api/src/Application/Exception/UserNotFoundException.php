<?php

declare(strict_types=1);

namespace App\Application\Exception;

class UserNotFoundException extends \RuntimeException
{
    public function __construct(string $identifier)
    {
        parent::__construct(sprintf('User "%s" could not be found.', $identifier));
    }
}
