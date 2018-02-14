<?php declare(strict_types=1);

namespace Shopware\Api\Shop\Definition;

use Shopware\Api\Currency\Definition\CurrencyDefinition;
use Shopware\Api\Entity\Field\DateField;
use Shopware\Api\Entity\Field\FkField;
use Shopware\Api\Entity\Field\ManyToOneAssociationField;
use Shopware\Api\Entity\FieldCollection;
use Shopware\Api\Entity\MappingEntityDefinition;
use Shopware\Api\Entity\Write\Flag\PrimaryKey;
use Shopware\Api\Entity\Write\Flag\Required;
use Shopware\Api\Shop\Event\ShopCurrency\ShopCurrencyDeletedEvent;
use Shopware\Api\Shop\Event\ShopCurrency\ShopCurrencyWrittenEvent;
use Shopware\Api\Entity\Field\VersionField;
class ShopCurrencyDefinition extends MappingEntityDefinition
{
    /**
     * @var FieldCollection
     */
    protected static $fields;

    /**
     * @var FieldCollection
     */
    protected static $primaryKeys;

    public static function getEntityName(): string
    {
        return 'shop_currency';
    }

    public static function getFields(): FieldCollection
    {
        if (self::$fields) {
            return self::$fields;
        }

        return self::$fields = new FieldCollection([ 
            new VersionField(),
            (new FkField('shop_id', 'shopId', ShopDefinition::class))->setFlags(new PrimaryKey(), new Required()),
            (new FkField('currency_id', 'currencyId', CurrencyDefinition::class))->setFlags(new PrimaryKey(), new Required()),
            new DateField('created_at', 'createdAt'),
            new DateField('updated_at', 'updatedAt'),
            new ManyToOneAssociationField('shop', 'shop_id', ShopDefinition::class, false),
            new ManyToOneAssociationField('currency', 'currency_id', CurrencyDefinition::class, false),
        ]);
    }

    public static function getWrittenEventClass(): string
    {
        return ShopCurrencyWrittenEvent::class;
    }

    public static function getDeletedEventClass(): string
    {
        return ShopCurrencyDeletedEvent::class;
    }
}
