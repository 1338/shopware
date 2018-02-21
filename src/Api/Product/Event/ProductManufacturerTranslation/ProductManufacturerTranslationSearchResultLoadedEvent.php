<?php declare(strict_types=1);

namespace Shopware\Api\Product\Event\ProductManufacturerTranslation;

use Shopware\Api\Product\Struct\ProductManufacturerTranslationSearchResult;
use Shopware\Context\Struct\ShopContext;
use Shopware\Framework\Event\NestedEvent;

class ProductManufacturerTranslationSearchResultLoadedEvent extends NestedEvent
{
    public const NAME = 'product_manufacturer_translation.search.result.loaded';

    /**
     * @var ProductManufacturerTranslationSearchResult
     */
    protected $result;

    public function __construct(ProductManufacturerTranslationSearchResult $result)
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
