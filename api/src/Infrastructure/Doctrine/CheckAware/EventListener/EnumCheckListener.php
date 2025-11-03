<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\EventListener;

use App\Infrastructure\Doctrine\CheckAware\Attribute\EnumCheck;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckRegistry;
use App\Infrastructure\Doctrine\CheckAware\Spec\EnumCheckSpec;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

#[AsDoctrineListener(ToolEvents::postGenerateSchemaTable)]
final readonly class EnumCheckListener
{
    public function __construct(private CheckRegistry $registry)
    {
    }

    /**
     * @throws \ReflectionException
     */
    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $args): void
    {
        $class = $args->getClassMetadata();
        $table = $args->getClassTable();

        $attributes = $class->getReflectionClass()->getAttributes(EnumCheck::class);
        if (empty($attributes)) {
            return;
        }

        foreach ($attributes as $attribute) {
            /** @var EnumCheck $config */
            $config = $attribute->newInstance();

            $spec = $this->buildSpec($class, $config);

            $this->registry->appendDeclaredSpec($table, $spec);
        }
    }

    // SPEC BUILDING ////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param ClassMetadata<object> $class
     *
     * @throws \ReflectionException
     */
    private function buildSpec(ClassMetadata $class, EnumCheck $config): EnumCheckSpec
    {
        /** @var class-string $classFqcn */
        $classFqcn = $class->getName();
        $property = $config->property;

        $this->assertScalarField($class, $property);

        $enumReflection = $this->resolveEnumReflection($class, $config);
        $cases = $this->resolveEnumCaseValues($enumReflection);

        $column = $class->getColumnName($property);
        $doctrineType = $class->getTypeOfField($property);

        if (null === $doctrineType) {
            throw new \LogicException(sprintf('EnumCheck could not determine Doctrine type for %s::%s.', $classFqcn, $property));
        }

        $this->assertColumnMatchesBackingType($enumReflection, $doctrineType, $classFqcn, $property);

        return new EnumCheckSpec(
            $config->name,
            $column,
            $cases,
            'string' === $enumReflection->getBackingType()->getName(),
            $config->timing,
        );
    }

    // ASSERT PROPERTY TYPE/EXISTENCE //////////////////////////////////////////////////////////////////////////////////

    /**
     * @param ClassMetadata<object> $class
     */
    private function assertScalarField(ClassMetadata $class, string $property): void
    {
        /** @var class-string $classFqcn */
        $classFqcn = $class->getName();

        if ($class->hasAssociation($property)) {
            throw new \LogicException(sprintf('EnumCheck on %s::%s targets an association; only scalar Doctrine fields are supported.', $classFqcn, $property));
        }

        if (!$class->hasField($property)) {
            throw new \LogicException(sprintf('EnumCheck references unknown property "%s" on %s.', $property, $classFqcn));
        }
    }

    // RETRIEVE/ASSERT ENUM ////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param ClassMetadata<object> $class
     *
     * @return \ReflectionEnum<\BackedEnum>
     *
     * @throws \ReflectionException
     */
    private function resolveEnumReflection(ClassMetadata $class, EnumCheck $config): \ReflectionEnum
    {
        /** @var class-string $classFqcn */
        $classFqcn = $class->getName();
        $enumFqcn = $config->enumFqcn;
        $property = $config->property;

        $inferredReflection = $this->inferEnumReflectionFromProperty($class, $property);

        if (null !== $enumFqcn) {
            if (!enum_exists($enumFqcn)) {
                throw new \LogicException(sprintf('EnumCheck on %s::%s references enumFqcn "%s" which is not a backed enum.', $classFqcn, $property, $enumFqcn));
            }

            $explicitReflection = new \ReflectionEnum($enumFqcn);

            if (!$explicitReflection->isBacked()) {
                throw new \LogicException(sprintf('EnumCheck enum "%s" must be a backed enum.', $enumFqcn));
            }

            if (null !== $inferredReflection && $inferredReflection->getName() !== $enumFqcn) {
                throw new \LogicException(sprintf('EnumCheck on %s::%s declares enumFqcn "%s" which does not match property type "%s".', $classFqcn, $property, $enumFqcn, $inferredReflection->getName()));
            }

            return $explicitReflection;
        }

        if (null === $inferredReflection) {
            throw new \LogicException(sprintf('EnumCheck on %s::%s requires enumFqcn because the property is not typed as a backed enum.', $classFqcn, $property));
        }

        return $inferredReflection;
    }

    /**
     * @param ClassMetadata<object> $class
     *
     * @return \ReflectionEnum<\BackedEnum>|null
     *
     * @throws \ReflectionException
     */
    private function inferEnumReflectionFromProperty(ClassMetadata $class, string $property): ?\ReflectionEnum
    {
        $type = $class->getReflectionClass()->getProperty($property)->getType();

        if ($type instanceof \ReflectionNamedType) {
            if ($type->isBuiltin()) {
                return null;
            }

            $name = $type->getName();
            if (!enum_exists($name)) {
                return null;
            }

            /** @var \ReflectionEnum<\BackedEnum> $reflection */
            $reflection = new \ReflectionEnum($name);
            if (!$reflection->isBacked()) {
                return null;
            }

            return $reflection;
        }

        if ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
            throw new \LogicException(sprintf('EnumCheck does not support union or intersection types on property "%s".', $property));
        }

        return null;
    }

    /**
     * @param \ReflectionEnum<\BackedEnum> $enumReflection
     *
     * @return list<string|int>
     */
    private function resolveEnumCaseValues(\ReflectionEnum $enumReflection): array
    {
        /** @var class-string<\BackedEnum> $enumFqcn */
        $enumFqcn = $enumReflection->getName();

        $values = array_map(
            static fn (\BackedEnum $case): string|int => $case->value,
            $enumFqcn::cases()
        );

        if (empty($values)) {
            throw new \LogicException(sprintf('EnumCheck enum "%s" must declare at least one case.', $enumFqcn));
        }

        return $values;
    }

    // COMPARE DOCTRINE COLUMN /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param \ReflectionEnum<\BackedEnum> $enumReflection
     * @param class-string                 $classFqcn
     */
    private function assertColumnMatchesBackingType(
        \ReflectionEnum $enumReflection,
        string $doctrineType,
        string $classFqcn,
        string $property,
    ): void {
        $backingType = $enumReflection->getBackingType()->getName(); // "int" or "string"

        $isStringColumn = in_array($doctrineType, ['string', 'text'], true);
        $isIntColumn = in_array($doctrineType, ['integer', 'smallint', 'bigint'], true);

        if ('string' === $backingType && !$isStringColumn) {
            throw new \LogicException(sprintf('EnumCheck on %s::%s expects a string-backed enum, but Doctrine column type is "%s".', $classFqcn, $property, $doctrineType));
        }

        if ('int' === $backingType && !$isIntColumn) {
            throw new \LogicException(sprintf('EnumCheck on %s::%s expects an int-backed enum, but Doctrine column type is "%s".', $classFqcn, $property, $doctrineType));
        }
    }
}
