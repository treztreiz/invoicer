<?php

declare(strict_types=1);

namespace App\Application\Contract;

use App\Domain\ValueObject\CompanyLogo;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface CompanyLogoStorageInterface
{
    public function prepare(UploadedFile $file): CompanyLogo;

    public function commit(UploadedFile $file, CompanyLogo $logo, ?CompanyLogo $previousLogo = null): void;
}
