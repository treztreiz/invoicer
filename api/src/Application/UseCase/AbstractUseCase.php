<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Exception\ResourceNotFoundException;
use App\Application\Service\Trait\ObjectMapperAwareTrait;
use Symfony\Component\ObjectMapper\Exception\MappingException;
use Symfony\Component\ObjectMapper\Exception\MappingTransformException;
use Symfony\Component\ObjectMapper\Exception\NoSuchPropertyException;
use Symfony\Component\Uid\Uuid;

abstract class AbstractUseCase
{
    use ObjectMapperAwareTrait;

    /**
     * @template T of object
     *
     * @param object                 $source The object to map from
     * @param T|class-string<T>|null $target The object or class to map to
     *
     * @return T
     *
     * @throws MappingException          When the mapping configuration is wrong
     * @throws MappingTransformException When a transformation on an object does not return an object
     * @throws NoSuchPropertyException   When a property does not exist
     */
    public function map(object $source, object|string|null $target = null): object
    {
        return $this->objectMapper->map($source, $target);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    protected function findOneById(
        object $repository,
        string $id,
        string $class,
    ): object {
        if (!method_exists($repository, $method = 'findOneById')) {
            throw new \InvalidArgumentException(sprintf('Method '.$method.' does not exist in "%s".', get_class($repository)));
        }

        return $repository->findOneById(Uuid::fromString($id)) ?? throw new ResourceNotFoundException($class, $id);
    }
}
