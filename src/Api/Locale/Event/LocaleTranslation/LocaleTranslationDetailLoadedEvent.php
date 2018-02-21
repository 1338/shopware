<?php declare(strict_types=1);

namespace Shopware\Api\Locale\Event\LocaleTranslation;

use Shopware\Api\Locale\Collection\LocaleTranslationDetailCollection;
use Shopware\Api\Locale\Event\Locale\LocaleBasicLoadedEvent;
use Shopware\Api\Shop\Event\Shop\ShopBasicLoadedEvent;
use Shopware\Context\Struct\ShopContext;
use Shopware\Framework\Event\NestedEvent;
use Shopware\Framework\Event\NestedEventCollection;

class LocaleTranslationDetailLoadedEvent extends NestedEvent
{
    public const NAME = 'locale_translation.detail.loaded';

    /**
     * @var ShopContext
     */
    protected $context;

    /**
     * @var LocaleTranslationDetailCollection
     */
    protected $localeTranslations;

    public function __construct(LocaleTranslationDetailCollection $localeTranslations, ShopContext $context)
    {
        $this->context = $context;
        $this->localeTranslations = $localeTranslations;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): ShopContext
    {
        return $this->context;
    }

    public function getLocaleTranslations(): LocaleTranslationDetailCollection
    {
        return $this->localeTranslations;
    }

    public function getEvents(): ?NestedEventCollection
    {
        $events = [];
        if ($this->localeTranslations->getLocales()->count() > 0) {
            $events[] = new LocaleBasicLoadedEvent($this->localeTranslations->getLocales(), $this->context);
        }
        if ($this->localeTranslations->getLanguages()->count() > 0) {
            $events[] = new ShopBasicLoadedEvent($this->localeTranslations->getLanguages(), $this->context);
        }

        return new NestedEventCollection($events);
    }
}
