<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\EventSubscriber;

use App\Application\Exception\ResourceNotFoundException;
use App\Domain\Exception\DomainException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApplicationExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof DomainException) {
            $event->setThrowable(new HttpException(Response::HTTP_BAD_REQUEST, $throwable->getMessage(), $throwable));

            return;
        }

        if ($throwable instanceof ResourceNotFoundException) {
            $event->setThrowable(new NotFoundHttpException($throwable->getMessage(), $throwable));
        }
    }
}
