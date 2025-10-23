<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Contracts\CustomerRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/api/health', name: 'health')]
    public function health(): Response
    {
        return $this->json('ok');
    }

    #[Route('/api/test')]
    public function test(CustomerRepositoryInterface $repo): Response
    {
        dd($repo);

        return $this->json('ok');
    }
}
