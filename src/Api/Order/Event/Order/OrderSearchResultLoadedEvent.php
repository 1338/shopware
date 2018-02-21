<?php declare(strict_types=1);

namespace Shopware\Api\Order\Event\Order;

use Shopware\Api\Order\Struct\OrderSearchResult;
use Shopware\Context\Struct\ShopContext;
use Shopware\Framework\Event\NestedEvent;

class OrderSearchResultLoadedEvent extends NestedEvent
{
    public const NAME = 'order.search.result.loaded';

    /**
     * @var OrderSearchResult
     */
    protected $result;

    public function __construct(OrderSearchResult $result)
    {
        $this->result = $result;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): ShopContext
    {
        return $this->result->getContext();
    }
}
