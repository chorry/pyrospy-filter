<?php

declare(strict_types=1);

namespace Zoon\PyroSpy\Plugins\Filtering;

use Zoon\PyroSpy\Sample;

/**
 * @phpstan-import-type TagsArray from Sample
 * @phpstan-import-type TraceStruct from Sample
 */

final class FilterRule
{
    /**
     * @param int $matchType
     * @param int $tracePart
     * @param string $value
     * @param int|null $lineNumber Represents line number, enumeration is from top-to-bottom, and starts with 1.
     */
    public function __construct(
        private int $matchType,
        private int $tracePart,
        private string $value,
        private ?int $lineNumber = null,
    ) {}

    /**
     * @param TagsArray $tags
     * @param int $lineNumber
     * @param array<int, string> $lineArray
     * @return bool
     * @throws \Exception
     */
    public function filter(array $tags, int $lineNumber, array $lineArray): bool
    {
        if ($this->lineNumber !== null && $lineNumber !== $this->lineNumber) {
            return false;
        }

        return match (true) {
            ($this->matchType & FilterMatchingType::CHK_DIRECT->value) === FilterMatchingType::CHK_DIRECT->value => $this->filterByDirectMatch(
                $tags,
                $lineArray,
            ),
            ($this->matchType & FilterMatchingType::CHK_REGEXP->value) === FilterMatchingType::CHK_REGEXP->value => $this->filterByRegexp(
                $tags,
                $lineArray,
            ),
            default => throw new \Exception('Unexpected match value'),
        };
    }

    /**
     * @param array{0:string, 1:string} $traceLine
     * @return \Generator
     */
    private function getLinePart(array $traceLine): \Generator
    {
        if (($this->tracePart & FilterMatchingType::SRC_METHOD->value) === FilterMatchingType::SRC_METHOD->value) {
            yield $traceLine[0];
        }

        if (($this->tracePart & FilterMatchingType::SRC_CALLEE->value) === FilterMatchingType::SRC_CALLEE->value) {
            yield $traceLine[1];
        }

        return null;
    }

    /**
     * @param array{0:string, 1:string} $lineArray
     * @return bool
     */
    private function filterByRegexp(array $tags, array $lineArray): bool
    {
        if (($this->tracePart & FilterMatchingType::SRC_TAG_NAME->value) === FilterMatchingType::SRC_TAG_NAME->value) {
            foreach ($tags as $tag) {
                if (preg_match($this->value, $tag)) {
                    return true;
                }
            }
        }

        foreach ($this->getLinePart($lineArray) as $lineText) {
            if (preg_match($this->value, $lineText)) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param array{0:string, 1:string} $lineArray
     * @return bool
     */
    private function filterByDirectMatch(array $tags, array $lineArray): bool
    {
        if (
            ($this->tracePart & FilterMatchingType::SRC_TAG_VALUE->value) === FilterMatchingType::SRC_TAG_VALUE->value
            && in_array($this->value, $tags, true)
        ) {
            return true;
        }
        if (
            ($this->tracePart & FilterMatchingType::SRC_TAG_NAME->value) === FilterMatchingType::SRC_TAG_NAME->value
            && isset($tags[$this->value])
        ) {
            return true;
        }

        foreach ($this->getLinePart($lineArray) as $lineText) {
            if ($this->value === $lineText) {
                return true;
            }
        }

        return false;
    }
}
