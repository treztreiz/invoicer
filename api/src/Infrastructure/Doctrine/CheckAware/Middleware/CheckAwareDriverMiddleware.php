<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Middleware;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckAwarePlatformInterface;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\VersionAwarePlatformDriver;

final class CheckAwareDriverMiddleware extends AbstractDriverMiddleware
{
    /** @param list<CheckAwarePlatformInterface> $checkAwarePlatforms */
    public function __construct(
        private readonly Driver $wrappedDriver,
        private readonly iterable $checkAwarePlatforms,
    ) {
        parent::__construct($wrappedDriver);
    }

    public function getDatabasePlatform(): AbstractPlatform
    {
        $platform = $this->wrappedDriver->getDatabasePlatform();

        return $this->getCheckAwarePlatforms($platform);
    }

    public function createDatabasePlatformForVersion($version): AbstractPlatform
    {
        if ($this->wrappedDriver instanceof VersionAwarePlatformDriver) {
            $platform = $this->wrappedDriver->createDatabasePlatformForVersion($version);

            return $this->getCheckAwarePlatforms($platform);
        }

        return $this->getDatabasePlatform();
    }

    private function getCheckAwarePlatforms(AbstractPlatform $platform): AbstractPlatform
    {
        foreach ($this->checkAwarePlatforms as $checkAwarePlatform) {
            if (is_subclass_of($checkAwarePlatform, get_class($platform))) {
                return $checkAwarePlatform;
            }
        }

        return $platform;
    }
}
