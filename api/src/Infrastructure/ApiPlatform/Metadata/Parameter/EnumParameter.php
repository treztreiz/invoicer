<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Metadata\Parameter;

use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\Validator\Constraints as Assert;

class EnumParameter
{
    private function __construct()
    {
    }

    /**
     * @param class-string<\BackedEnum> $enumFqcn
     * @param list<string|int>|callable $enumValues
     */
    public static function create(string $key, string $description, string $enumFqcn, array|callable $enumValues): QueryParameter
    {
        if (!enum_exists($enumFqcn)) {
            throw new \LogicException(sprintf('Class %s is not a backed enum.', $enumFqcn));
        }

        if (is_callable($enumValues)) {
            $enumValues = $enumValues();
        }

        return new QueryParameter(
            key: $key,
            openApi: new OpenApiParameter(
                name: $key.'[]',
                in: 'query',
                description: $description,
                schema: [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => $enumValues,
                    ],
                ],
                explode: true,
            ),
            constraints: [new Assert\Choice(callback: [$enumFqcn, 'cases'], multiple: true)],
            nativeType: new CollectionType(new EnumType($enumFqcn)),
            castToArray: true,
            castToNativeType: true,
            castFn: static fn (string $status) => $enumFqcn::tryFrom($status)
        );
    }
}
