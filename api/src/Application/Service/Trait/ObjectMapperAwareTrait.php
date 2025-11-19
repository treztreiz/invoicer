<?php

declare(strict_types=1);

namespace App\Application\Service\Trait;

use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait ObjectMapperAwareTrait
{
    protected ?ObjectMapperInterface $objectMapper = null;

    #[Required]
    public function setObjectMapper(ObjectMapperInterface $objectMapper): void
    {
        $this->objectMapper = $objectMapper;
    }
}
