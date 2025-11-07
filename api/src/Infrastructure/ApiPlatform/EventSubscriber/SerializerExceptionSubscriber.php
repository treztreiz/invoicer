<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\EventSubscriber;

use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Normalizes serializer constructor errors into ValidationException so clients
 * get the usual 422 payload instead of raw stack traces.
 */
final class SerializerExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $env = $event->getRequest()->server->get('APP_ENV');
        if ('dev' === $env) {
            return;
        }

        $throwable = $event->getThrowable();

        if (!$throwable instanceof MissingConstructorArgumentsException) {
            return;
        }

        $event->setThrowable($this->toValidationException($throwable));
    }

    private function toValidationException(MissingConstructorArgumentsException $exception): ValidationException
    {
        $violations = new ConstraintViolationList();

        foreach ($exception->getMissingConstructorArguments() as $argument) {
            $propertyPath = ltrim($argument, '$');

            $violations->add(
                new ConstraintViolation(
                    message: 'This value should not be blank.',
                    messageTemplate: 'This value should not be blank.',
                    parameters: [],
                    root: null,
                    propertyPath: $propertyPath,
                    invalidValue: null,
                    plural: null,
                    code: 'missing_field',
                    constraint: null,
                    cause: $exception,
                )
            );
        }

        return new ValidationException($violations, previous: $exception);
    }
}
