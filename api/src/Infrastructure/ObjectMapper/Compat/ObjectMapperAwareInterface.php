<?php

declare(strict_types=1);

namespace Symfony\Component\ObjectMapper;

if (!interface_exists(ObjectMapperAwareInterface::class)) {
    interface ObjectMapperAwareInterface
    {
        public function withObjectMapper(ObjectMapperInterface $objectMapper): static;
    }
}
