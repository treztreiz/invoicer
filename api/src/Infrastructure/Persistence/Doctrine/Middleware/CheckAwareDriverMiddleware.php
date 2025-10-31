<?php

namespace App\Infrastructure\Persistence\Doctrine\Middleware;

use App\Infrastructure\Persistence\Doctrine\Contracts\CheckAwarePlatformInterface;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\VersionAwarePlatformDriver;

final class CheckAwareDriverMiddleware extends AbstractDriverMiddleware
{
    public function __construct(
        private readonly Driver $wrappedDriver,
        private readonly iterable $checkAwarePlatforms,
    ) {
        parent::__construct($wrappedDriver);
    }

    public function getDatabasePlatform(): AbstractPlatform|CheckAwarePlatformInterface
    {
        $platform = $this->wrappedDriver->getDatabasePlatform();

        return $this->getCheckAwarePlatforms($platform);
    }

    public function createDatabasePlatformForVersion($version): AbstractPlatform|CheckAwarePlatformInterface
    {
        if ($this->wrappedDriver instanceof VersionAwarePlatformDriver) {
            $platform = $this->wrappedDriver->createDatabasePlatformForVersion($version);

            return $this->getCheckAwarePlatforms($platform);
        }

        return $this->getDatabasePlatform();
    }

    private function getCheckAwarePlatforms(AbstractPlatform $platform): AbstractPlatform|CheckAwarePlatformInterface
    {
        foreach ($this->checkAwarePlatforms as $checkAwarePlatform) {
            if (is_subclass_of($checkAwarePlatform, get_class($platform))) {
                return $checkAwarePlatform;
            }
        }

        return $platform;
    }
}