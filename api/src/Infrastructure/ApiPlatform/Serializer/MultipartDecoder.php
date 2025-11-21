<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Serializer;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

final class MultipartDecoder implements DecoderInterface
{
    public const string FORMAT = 'multipart';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function decode(string $data, string $format, array $context = []): ?array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        return array_map([self::class, 'decodeRequestParameter'], $request->request->all()) + $request->files->all();
    }

    /**
     * @throws \JsonException
     */
    private static function decodeRequestParameter(mixed $element): mixed
    {
        return is_string($element)
            ? json_decode($element, true, flags: \JSON_THROW_ON_ERROR)
            : $element;
    }

    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }
}
