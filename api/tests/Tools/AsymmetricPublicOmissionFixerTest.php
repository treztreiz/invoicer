<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use App\Tools\PhpCsFixer\AsymmetricPublicOmissionFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AsymmetricPublicOmissionFixerTest extends TestCase
{
    private AsymmetricPublicOmissionFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = new AsymmetricPublicOmissionFixer();
    }

    #[DataProvider('provideFixCases')]
    public function test_code_is_fixed(string $input, string $expected): void
    {
        $actual = $this->applyFixToCode($input);
        static::assertSame($expected, $actual, 'Single pass should produce expected output.');

        // Idempotency: running again should keep code unchanged
        $again = $this->applyFixToCode($actual);
        static::assertSame($expected, $again, 'Fixer must be idempotent.');
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function provideFixCases(): iterable
    {
        yield 'simple property private(set)' => [
            <<<'PHP'
<?php
class A {
    public private(set) string $name;
}
PHP,
            <<<'PHP'
<?php
class A {
    private(set) string $name;
}
PHP,
        ];

        yield 'simple property protected(set)' => [
            <<<'PHP'
<?php
class A {
    public protected(set) int $age;
}
PHP,
            <<<'PHP'
<?php
class A {
    protected(set) int $age;
}
PHP,
        ];

        yield 'constructor promotion' => [
            <<<'PHP'
<?php
class B {
    public function __construct(
        public private(set) string $id,
        public protected(set) int $score,
    ) {}
}
PHP,
            <<<'PHP'
<?php
class B {
    public function __construct(
        private(set) string $id,
        protected(set) int $score,
    ) {}
}
PHP,
        ];

        yield 'already correct (no-op)' => [
            <<<'PHP'
<?php
class A {
    private(set) string $name;
    protected(set) int $age;
}
PHP,
            <<<'PHP'
<?php
class A {
    private(set) string $name;
    protected(set) int $age;
}
PHP,
        ];

        yield 'non-property context (method) not touched' => [
            <<<'PHP'
<?php
class A {
    public function privateSetterLikeName() {}
}
PHP,
            <<<'PHP'
<?php
class A {
    public function privateSetterLikeName() {}
}
PHP,
        ];

        yield 'attributes + docblock around property' => [
            <<<'PHP'
<?php
class A {
    /** @var string */
    #[ORM\Column(type: 'string')]
    public private(set) string $name;
}
PHP,
            <<<'PHP'
<?php
class A {
    /** @var string */
    #[ORM\Column(type: 'string')]
    private(set) string $name;
}
PHP,
        ];
        yield 'ct-tokenization guard' => [
            <<<'PHP'
<?php
class C {
    #[ORM\Column(type: 'string')]
    public private(set) string $name;
}
PHP,
            <<<'PHP'
<?php
class C {
    #[ORM\Column(type: 'string')]
    private(set) string $name;
}
PHP,
        ];
    }

    private function applyFixToCode(string $code): string
    {
        $tokens = Tokens::fromCode($code);

        // sanity: candidate check does not guarantee fix, but should be callable
        $this->fixer->isCandidate($tokens);

        $this->fixer->fix(new \SplFileInfo('test.php'), $tokens);
        $tokens->clearEmptyTokens();

        return $tokens->generateCode();
    }
}
