<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Metadata\Parameter;

use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class UuidParameter
{
    private function __construct()
    {
    }

    public static function create(string $key, string $description): QueryParameter
    {
        $example = Uuid::v7()->toRfc4122();

        return new QueryParameter(
            key: $key,
            openApi: new OpenApiParameter(
                name: $key,
                in: 'query',
                description: $description,
                schema: [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
                example: $example
            ),
            constraints: [new Assert\Uuid()],
            nativeType: new ObjectType(Uuid::class),
            castToNativeType: true,
            castFn: static fn (string $id) => Uuid::isValid($id) ? Uuid::fromString($id) : $id,
        );
    }
}
