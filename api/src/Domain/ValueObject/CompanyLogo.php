<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Guard\DomainGuard;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class CompanyLogo
{
    private const int MAX_FILE_SIZE = 2_000_000; // 2 MiB

    /** @param array{width?: int, height?: int}|null $dimensions */
    public function __construct(
        #[ORM\Column(length: 255, nullable: true)]
        private(set) ?string $name = null {
            set => DomainGuard::optionalNonEmpty($value, 'Logo filename');
        },

        #[ORM\Column(length: 255, nullable: true)]
        private(set) ?string $originalName = null {
            set => DomainGuard::optionalNonEmpty($value, 'Logo original name');
        },

        #[ORM\Column(type: Types::INTEGER, nullable: true)]
        private(set) ?int $size = null {
            set => self::guardSize($value);
        },

        #[ORM\Column(length: 255, nullable: true)]
        private(set) ?string $mimeType = null {
            set => DomainGuard::optionalNonEmpty($value, 'Logo mime type');
        },

        #[ORM\Column(type: Types::JSON, nullable: true)]
        private(set) ?array $dimensions = null,
    ) {
        $this->dimensions = self::guardDimensions($this->dimensions);
    }

    public static function empty(): self
    {
        return new self();
    }

    /** @param array{width?: int, height?: int}|null $dimensions */
    public static function fromUpload(
        string $storedName,
        ?string $originalName,
        ?int $size,
        ?string $mimeType,
        ?array $dimensions,
    ): self {
        return new self($storedName, $originalName, $size, $mimeType, $dimensions);
    }

    public function hasFile(): bool
    {
        return null !== $this->name;
    }

    public function url(string $basePath = '/uploads/logos'): ?string
    {
        if (!$this->hasFile()) {
            return null;
        }

        return rtrim($basePath, '/').'/'.$this->name;
    }

    private static function guardSize(?int $size): ?int
    {
        if (null === $size) {
            return null;
        }

        $validated = DomainGuard::optionalNonNegativeInt($size, 'Logo size');

        if ($validated > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('Logo size cannot exceed 2MB.');
        }

        return $validated;
    }

    /**
     * @param array{width?: int, height?: int}|null $dimensions
     *
     * @return array{width: int, height: int}|null
     */
    private static function guardDimensions(?array $dimensions): ?array
    {
        if (null === $dimensions) {
            return null;
        }

        if (!array_key_exists('width', $dimensions) || !array_key_exists('height', $dimensions)) {
            throw new \InvalidArgumentException('Logo dimensions must contain "width" and "height" keys.');
        }

        $width = (int) $dimensions['width'];
        $height = (int) $dimensions['height'];

        if ($width < 0 || $height < 0) {
            throw new \InvalidArgumentException('Logo dimensions must be positive integers.');
        }

        return ['width' => $width, 'height' => $height];
    }
}
