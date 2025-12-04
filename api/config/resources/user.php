<?php

declare(strict_types=1);

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Application\Dto\User\Input\PasswordInput;
use App\Application\Dto\User\Input\UploadCompanyLogoInput;
use App\Application\Dto\User\Input\UserInput;
use App\Application\Dto\User\Output\UserOutput;
use App\Domain\Entity\User\User;
use App\Infrastructure\ApiPlatform\State\User\UpdatePasswordProcessor;
use App\Infrastructure\ApiPlatform\State\User\UpdateUserProcessor;
use App\Infrastructure\ApiPlatform\State\User\UploadCompanyLogoProcessor;
use App\Infrastructure\ApiPlatform\State\User\UserProvider;
use Symfony\Component\HttpFoundation\Response;

return new ApiResource(
    uriTemplate: '',
    shortName: 'Me',
    operations: [
        new Get(
            provider: UserProvider::class,
            stateOptions: new Options(User::class),
        ),
        new Put(
            input: UserInput::class,
            read: false,
            processor: UpdateUserProcessor::class
        ),
        new Post(
            uriTemplate: '/password',
            status: Response::HTTP_NO_CONTENT,
            input: PasswordInput::class,
            output: false,
            read: false,
            processor: UpdatePasswordProcessor::class,
        ),
        new Post(
            uriTemplate: '/company-logo',
            inputFormats: ['multipart' => ['multipart/form-data']],
            status: Response::HTTP_OK,
            description: 'Upload or replace the company logo (PNG/JPEG/SVG, ≤2MB).',
            input: UploadCompanyLogoInput::class,
            read: false,
            processor: UploadCompanyLogoProcessor::class
        ),
    ],
    routePrefix: '/me',
    class: UserOutput::class,
);
