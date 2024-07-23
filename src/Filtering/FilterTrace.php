<?php

namespace Zoon\PyroSpy\Plugins\Filtering;

use Zoon\PyroSpy\Plugins\PluginInterface;

class FilterTrace implements PluginInterface
{
    /**
     * @param FilterRule[] $rules
     */
    public function __construct(private readonly array $rules) {}

    public function process(array $tags, array $trace): array
    {
        $traceDepth = count($trace);
        foreach ($trace as $lineNumber => $lineArray) {
            foreach ($this->rules as $rule) {
                if ($rule->filter($tags, $traceDepth--, $lineArray)) {
                    return [$tags, []];
                }
            }
        }
        return [$tags, $trace];
    }
}
