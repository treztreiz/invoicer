<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Functional\Api\User;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\Common\ApiClientHelperTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CompanyLogoApiTest extends ApiTestCase
{
    use ApiClientHelperTrait;

    protected static ?bool $alwaysBootKernel = true;

    private static string $uploadDir;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::bootKernel();
        self::$uploadDir = self::getContainer()->getParameter('app.company_logo_upload_dir');
        self::ensureKernelShutdown();
    }

    public static function tearDownAfterClass(): void
    {
        $fs = new Filesystem();
        if (isset(self::$uploadDir) && $fs->exists(self::$uploadDir)) {
            if ($files = glob(rtrim(self::$uploadDir, '/').'/*')) {
                $fs->remove($files);
            }
        }

        parent::tearDownAfterClass();
    }

    public function test_upload_company_logo_updates_logo_url(): void
    {
        $client = $this->createAuthenticatedClient();
        $filePath = sys_get_temp_dir().'/logo-upload-test.svg';
        file_put_contents($filePath, '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>');

        $response = $this->apiRequest($client, 'POST', '/api/me/company-logo', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => [
                    'logo' => $this->createUploadedFile($filePath, 'logo.svg', 'image/svg+xml'),
                ],
            ],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertArrayHasKey('logoUrl', $data);
        static::assertNotNull($data['logoUrl']);
        static::assertFileExists(self::uploadedFilePath($data['logoUrl']));
    }

    public function test_upload_company_logo_with_invalid_type_is_rejected(): void
    {
        $client = $this->createAuthenticatedClient();
        $filePath = sys_get_temp_dir().'/logo-upload-test.txt';
        file_put_contents($filePath, 'plain text');

        $response = $this->apiRequest($client, 'POST', '/api/me/company-logo', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => [
                    'logo' => $this->createUploadedFile($filePath, 'logo.txt', 'text/plain'),
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        $data = $response->toArray(false);

        static::assertArrayHasKey('violations', $data);
        static::assertSame('logo', $data['violations'][0]['propertyPath']);
    }

    private static function uploadedFilePath(string $url): string
    {
        return self::$uploadDir.'/'.pathinfo($url, PATHINFO_BASENAME);
    }

    private function createUploadedFile(string $path, string $originalName, string $mimeType): UploadedFile
    {
        return new UploadedFile(
            path: $path,
            originalName: $originalName,
            mimeType: $mimeType,
            test: true
        );
    }
}
