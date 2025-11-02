<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\EventListener;

use App\Infrastructure\Doctrine\CheckAware\Attribute\EnumCheck;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckOptionManager;
use App\Infrastructure\Doctrine\CheckAware\Spec\EnumCheckSpec;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

#[AsDoctrineListener(ToolEvents::postGenerateSchemaTable)]
final readonly class EnumCheckListener
{
    public function __construct(
        private CheckOptionManager $optionManager,
    ) {
    }

    /**
     * @throws MappingException
     * @throws \ReflectionException
     */
    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $args): void
    {
        $class = $args->getClassMetadata();
        $table = $args->getClassTable();

        $attributes = $class->getReflectionClass()->getAttributes(EnumCheck::class);
        if ([] === $attributes) {
            return;
        }

        foreach ($attributes as $attribute) {
            /** @var EnumCheck $config */
            $config = $attribute->newInstance();

            if ($class->hasAssociation($config->property)) {
                throw new \LogicException(
                    sprintf(
                        'EnumCheck on %s::%s targets an association; only scalar Doctrine fields are supported.',
                        $class->getName(),
                        $config->property
                    )
                );
            }

            if (!$class->hasField($config->property)) {
                throw new \LogicException(
                    sprintf(
                        'EnumCheck references unknown property "%s" on %s.',
                        $config->property,
                        $class->getName()
                    )
                );
            }

            $enumClass = $this->resolveEnumClass($class, $config);
            $enumReflection = new \ReflectionEnum($enumClass);

            if (!$enumReflection->isBacked()) {
                throw new \LogicException(sprintf('EnumCheck enum "%s" must be a backed enum.', $enumClass));
            }

            /** @var list<\BackedEnum> $enumCases */
            $enumCases = $enumClass::cases();
            $cases = array_map(
                static fn(\BackedEnum $case): string|int => $case->value,
                $enumCases
            );

            if ([] === $cases) {
                throw new \LogicException(sprintf('EnumCheck enum "%s" must declare at least one case.', $enumClass));
            }

            $column = $class->getColumnName($config->property);
            $doctrineType = $class->getTypeOfField($config->property);
            if (null === $doctrineType) {
                throw new \LogicException(
                    sprintf(
                        'EnumCheck could not determine Doctrine type for %s::%s.',
                        $class->getName(),
                        $config->property
                    )
                );
            }

            $this->assertColumnMatchesBackingType($doctrineType, $enumReflection, $class, $config->property);

            $spec = new EnumCheckSpec(
                $config->name,
                [
                    'column' => $column,
                    'values' => $cases,
                    'is_string' => 'string' === $enumReflection->getBackingType()->getName(),
                ],
            );

            $this->optionManager->appendDesired($table, $spec);
        }
    }

    /**
     * @param ClassMetadata<object> $class
     * @return class-string
     */
    private function resolveEnumClass(ClassMetadata $class, EnumCheck $config): string
    {
        $refProperty = $this->reflectionProperty($class, $config->property);
        $inferredEnum = $this->inferEnumFromProperty($refProperty);

        if (null !== $config->enumClass) {
            if (null !== $inferredEnum && $inferredEnum !== $config->enumClass) {
                throw new \LogicException(
                    sprintf(
                        'EnumCheck on %s::%s declares enumClass "%s" which does not match property type "%s".',
                        $class->getName(),
                        $config->property,
                        $config->enumClass,
                        $inferredEnum
                    )
                );
            }

            return $config->enumClass;
        }

        if (null === $inferredEnum) {
            throw new \LogicException(
                sprintf(
                    'EnumCheck on %s::%s requires enumClass because the property is not typed as a backed enum.',
                    $class->getName(),
                    $config->property
                )
            );
        }

        return $inferredEnum;
    }

    private function reflectionProperty(ClassMetadata $class, string $property): \ReflectionProperty
    {
        try {
            return $class->getReflectionClass()->getProperty($property);
        } catch (\ReflectionException $e) {
            throw new \LogicException(sprintf('EnumCheck references unknown property "%s" on %s.', $property, $class->getName()), previous: $e);
        }
    }

    private function inferEnumFromProperty(\ReflectionProperty $property): ?string
    {
        $type = $property->getType();
        if ($type instanceof \ReflectionNamedType) {
            if ($type->isBuiltin()) {
                return null;
            }

            $name = $type->getName();
            if (!enum_exists($name)) {
                return null;
            }

            $enumReflection = new \ReflectionEnum($name);
            if (!$enumReflection->isBacked()) {
                return null;
            }

            return $name;
        }

        if ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
            throw new \LogicException(
                sprintf(
                    'EnumCheck does not support union or intersection types on property "%s".',
                    $property->getName()
                )
            );
        }

        return null;
    }

    /**
     * @param ClassMetadata<object> $class
     */
    private function assertColumnMatchesBackingType(
        string $doctrineType,
        \ReflectionEnum $enum,
        ClassMetadata $class,
        string $property,
    ): void {
        $backingType = $enum->getBackingType()->getName(); // "int" or "string"

        $isStringColumn = in_array($doctrineType, ['string', 'text'], true);
        $isIntColumn = in_array($doctrineType, ['integer', 'smallint', 'bigint'], true);

        if ('string' === $backingType && !$isStringColumn) {
            throw new \LogicException(
                sprintf(
                    'EnumCheck on %s::%s expects a string-backed enum, but Doctrine column type is "%s".',
                    $class->getName(),
                    $property,
                    $doctrineType
                )
            );
        }

        if ('int' === $backingType && !$isIntColumn) {
            throw new \LogicException(
                sprintf(
                    'EnumCheck on %s::%s expects an int-backed enum, but Doctrine column type is "%s".',
                    $class->getName(),
                    $property,
                    $doctrineType
                )
            );
        }
    }
}
