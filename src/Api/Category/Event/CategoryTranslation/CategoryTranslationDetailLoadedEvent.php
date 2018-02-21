<?php declare(strict_types=1);

namespace Shopware\Api\Category\Event\CategoryTranslation;

use Shopware\Api\Category\Collection\CategoryTranslationDetailCollection;
use Shopware\Api\Category\Event\Category\CategoryBasicLoadedEvent;
use Shopware\Api\Shop\Event\Shop\ShopBasicLoadedEvent;
use Shopware\Context\Struct\ShopContext;
use Shopware\Framework\Event\NestedEvent;
use Shopware\Framework\Event\NestedEventCollection;

class CategoryTranslationDetailLoadedEvent extends NestedEvent
{
    public const NAME = 'category_translation.detail.loaded';

    /**
     * @var ShopContext
     */
    protected $context;

    /**
     * @var CategoryTranslationDetailCollection
     */
    protected $categoryTranslations;

    public function __construct(CategoryTranslationDetailCollection $categoryTranslations, ShopContext $context)
    {
        $this->context = $context;
        $this->categoryTranslations = $categoryTranslations;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): ShopContext
    {
        return $this->context;
    }

    public function getCategoryTranslations(): CategoryTranslationDetailCollection
    {
        return $this->categoryTranslations;
    }

    public function getEvents(): ?NestedEventCollection
    {
        $events = [];
        if ($this->categoryTranslations->getCategories()->count() > 0) {
            $events[] = new CategoryBasicLoadedEvent($this->categoryTranslations->getCategories(), $this->context);
        }
        if ($this->categoryTranslations->getLanguages()->count() > 0) {
            $events[] = new ShopBasicLoadedEvent($this->categoryTranslations->getLanguages(), $this->context);
        }

        return new NestedEventCollection($events);
    }
}
