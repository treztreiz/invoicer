<?php

namespace App\Tools\PhpCsFixer;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Removes redundant "public" before asymmetric set-visibility on properties,
 * e.g. `public private(set) string $x;`  =>  `private(set) string $x;`
 * Works for normal properties and promoted constructor properties.
 *
 * Safe because PHP 8.4 allows omitting main visibility when it's effectively public
 * if a `*(set)` is present. See PHP manual on asymmetric property visibility.
 */
final class AsymmetricPublicOmissionFixer implements FixerInterface
{
    public function getName(): string
    {
        return 'App/asymmetric_public_omission';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Remove redundant "public" before asymmetric set-visibility on properties.',
            [
                new CodeSample(
                    "<?php\nclass C {\n    public private(set) string \$name;\n}\n"
                ),
                new CodeSample(
                    "<?php\nclass C {\n    public function __construct(\n        public private(set) string \$name,\n    ) {}\n}\n"
                ),
            ]
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_PUBLIC);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function supports(\SplFileInfo $file): bool
    {
        return true;
    }

    /** Run late so we can clean up after core fixers if needed. */
    public function getPriority(): int
    {
        return -10;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($i = 0; $i < $tokens->count(); ++$i) {
            // Match both normal `public` and promoted-ctor `public`
            if (!$tokens[$i]->isGivenKind([T_PUBLIC, CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC])) {
                continue;
            }

            $next = $tokens->getNextMeaningfulToken($i);
            if (null === $next) {
                continue;
            }

            // In your stream these are SINGLE tokens: `private(set)` or `protected(set)`
            $nextContent = strtolower($tokens[$next]->getContent());
            if ('private(set)' !== $nextContent && 'protected(set)' !== $nextContent) {
                continue;
            }

            // Guard: ensure this is a (promoted) property — find $variable before a stopper
            $varIdx = $this->findNextBefore($tokens, [T_VARIABLE], ['{', ';', ')', ','], $next, 120);
            if (null === $varIdx) {
                continue; // not a property-like declaration
            }

            // Remove the `public` token + its immediate trailing whitespace
            $tokens->clearAt($i);
            $after = $i + 1;
            if (isset($tokens[$after]) && $tokens[$after]->isGivenKind(T_WHITESPACE)) {
                $tokens->clearAt($after);
            }

            $tokens->clearEmptyTokens();
            // step back after mutation so we don't skip tokens
            $i = max(-1, $i - 1);
        }
    }

    /**
     * Find the next token of any $wantedKinds before hitting any $stoppers,
     * starting from $from (exclusive), within a bounded lookahead.
     *
     * @param array<int|string> $wantedKinds
     * @param array<int|string> $stoppers
     */
    private function findNextBefore(Tokens $tokens, array $wantedKinds, array $stoppers, int $from, int $limit = 200): ?int
    {
        $steps = 0;
        $i = $from;
        while (null !== ($i = $tokens->getNextMeaningfulToken($i))) {
            ++$steps;
            $t = $tokens[$i];

            if ($t->isGivenKind($wantedKinds) || in_array($t->getContent(), $wantedKinds, true)) {
                return $i;
            }
            if (in_array($t->getId() ?? $t->getContent(), $stoppers, true)) {
                return null;
            }
            if ($steps > $limit) {
                return null;
            }
        }

        return null;
    }
}
