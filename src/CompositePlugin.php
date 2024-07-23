<?php

declare(strict_types=1);

namespace src;

use Zoon\PyroSpy\Plugins\PluginInterface;

class CompositePlugin implements PluginInterface
{
    /**
     * @param PluginInterface[] $plugins
     */
    public function __construct(private readonly array $plugins) {}
    public function process(array $tags, array $trace): array
    {
        foreach ($this->plugins as $plugin) {
            [$tags, $trace] = $plugin->process($tags, $trace);
        }

        return [$tags, $trace];
    }
}
