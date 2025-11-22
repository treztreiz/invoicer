<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Contract\CompanyLogoStorageInterface;
use App\Domain\ValueObject\CompanyLogo;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

final readonly class CompanyLogoStorage implements CompanyLogoStorageInterface
{
    public function __construct(
        #[Autowire(param: 'app.company_logo_upload_dir')]
        private string $uploadDir,
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function prepare(UploadedFile $file): CompanyLogo
    {
        $storedName = sprintf('%s.%s', Uuid::v7()->toRfc4122(), $this->guessExtension($file));

        return CompanyLogo::fromUpload(
            storedName: $storedName,
            originalName: $file->getClientOriginalName(),
            size: $file->getSize(),
            mimeType: $file->getMimeType(),
            dimensions: $this->extractDimensions($file),
        );
    }

    public function commit(UploadedFile $file, CompanyLogo $logo, ?CompanyLogo $previousLogo = null): void
    {
        $this->filesystem->mkdir($this->uploadDir);

        $file->move($this->uploadDir, $logo->name ?: '');

        if (null !== $previousLogo && $previousLogo->hasFile()) {
            $previousPath = rtrim($this->uploadDir, '/').'/'.$previousLogo->name;
            if ($this->filesystem->exists($previousPath)) {
                $this->filesystem->remove($previousPath);
            }
        }
    }

    private function guessExtension(UploadedFile $file): string
    {
        return $file->guessExtension() ?? $file->getClientOriginalExtension() ?: 'bin';
    }

    /**
     * @return array{width: int, height: int}|null
     */
    private function extractDimensions(UploadedFile $file): ?array
    {
        $path = $file->getRealPath();
        if (false === $path) {
            return null;
        }

        $info = @getimagesize($path);
        if (false === $info) {
            return null;
        }

        return ['width' => (int) $info[0], 'height' => (int) $info[1]];
    }
}
