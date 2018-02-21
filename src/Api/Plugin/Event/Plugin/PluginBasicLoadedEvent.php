<?php declare(strict_types=1);

namespace Shopware\Api\Plugin\Event\Plugin;

use Shopware\Api\Plugin\Collection\PluginBasicCollection;
use Shopware\Context\Struct\ShopContext;
use Shopware\Framework\Event\NestedEvent;

class PluginBasicLoadedEvent extends NestedEvent
{
    public const NAME = 'plugin.basic.loaded';

    /**
     * @var ShopContext
     */
    protected $context;

    /**
     * @var PluginBasicCollection
     */
    protected $plugins;

    public function __construct(PluginBasicCollection $plugins, ShopContext $context)
    {
        $this->context = $context;
        $this->plugins = $plugins;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): ShopContext
    {
        return $this->context;
    }

    public function getPlugins(): PluginBasicCollection
    {
        return $this->plugins;
    }
}
