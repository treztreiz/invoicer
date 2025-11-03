<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final readonly class EnumCheck
{
    /**
     * @param non-empty-string  $property
     * @param non-empty-string  $name
     * @param class-string|null $enumFqcn backed enum class providing allowed values
     */
    public function __construct(
        public string $property,
        public string $name = 'ENUM_CHECK',
        public ?string $enumFqcn = null,
    ) {
        if ('' === trim($this->property)) {
            throw new \LogicException('EnumCheck requires a non-empty property name.');
        }

        if ('' === trim($this->name)) {
            throw new \LogicException('EnumCheck requires a non-empty constraint name.');
        }

        if (null !== $this->enumFqcn) {
            if (!enum_exists($this->enumFqcn)) {
                throw new \LogicException(sprintf('EnumCheck expects enumFqcn `%s` to be an enum.', $this->enumFqcn));
            }

            $ref = new \ReflectionEnum($this->enumFqcn);

            if (!$ref->isBacked()) {
                throw new \LogicException(sprintf('EnumCheck enumFqcn `%s` must be a backed enum.', $this->enumFqcn));
            }
        }
    }
}
