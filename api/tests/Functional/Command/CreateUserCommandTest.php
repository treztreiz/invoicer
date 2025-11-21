<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @testType functional
 */
final class CreateUserCommandTest extends KernelTestCase
{
    use ResetDatabase;

    public function test_user_creation_command_rejects_second_user(): void
    {
        $application = $this->bootApplication();

        $command = $application->find('app:create-user');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'user-identifier' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $commandTester->assertCommandIsSuccessful();
        static::assertStringContainsString('User "admin@example.com" created', $commandTester->getDisplay());

        $commandTester->execute([
            'user-identifier' => 'second@example.com',
            'password' => 'ignored',
        ]);

        static::assertSame(1, $commandTester->getStatusCode());
        static::assertStringContainsString('already exists', $commandTester->getDisplay());
    }

    private function bootApplication(): Application
    {
        self::bootKernel();

        $application = new Application(self::$kernel);
        $application->setAutoExit(false);

        return $application;
    }
}
