<?php

declare(strict_types=1);

namespace App\Application\Dto\User\Input;

use App\Domain\ValueObject\CompanyLogo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UploadCompanyLogoInput
{
    public function __construct(
        #[Assert\NotNull(message: 'A logo file is required.')]
        #[Assert\File(
            maxSize: CompanyLogo::MAX_FILE_SIZE,
            mimeTypes: ['image/png', 'image/jpeg', 'image/svg+xml'],
            mimeTypesMessage: 'Only PNG, JPEG or SVG files are allowed.'
        )]
        public UploadedFile $logo,
    ) {
    }
}
