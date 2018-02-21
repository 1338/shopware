<?php declare(strict_types=1);

namespace Shopware\Api\Shipping\Event\ShippingMethodPrice;

use Shopware\Api\Shipping\Collection\ShippingMethodPriceBasicCollection;
use Shopware\Context\Struct\ShopContext;
use Shopware\Framework\Event\NestedEvent;

class ShippingMethodPriceBasicLoadedEvent extends NestedEvent
{
    public const NAME = 'shipping_method_price.basic.loaded';

    /**
     * @var ShopContext
     */
    protected $context;

    /**
     * @var ShippingMethodPriceBasicCollection
     */
    protected $shippingMethodPrices;

    public function __construct(ShippingMethodPriceBasicCollection $shippingMethodPrices, ShopContext $context)
    {
        $this->context = $context;
        $this->shippingMethodPrices = $shippingMethodPrices;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): ShopContext
    {
        return $this->context;
    }

    public function getShippingMethodPrices(): ShippingMethodPriceBasicCollection
    {
        return $this->shippingMethodPrices;
    }
}
