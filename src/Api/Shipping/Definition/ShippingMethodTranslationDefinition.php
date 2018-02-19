<?php declare(strict_types=1);

namespace Shopware\Api\Shipping\Definition;

use Shopware\Api\Entity\EntityDefinition;
use Shopware\Api\Entity\EntityExtensionInterface;
use Shopware\Api\Entity\Field\FkField;
use Shopware\Api\Entity\Field\LongTextField;
use Shopware\Api\Entity\Field\ManyToOneAssociationField;
use Shopware\Api\Entity\Field\ReferenceVersionField;
use Shopware\Api\Entity\Field\StringField;
use Shopware\Api\Entity\Field\VersionField;
use Shopware\Api\Entity\FieldCollection;
use Shopware\Api\Entity\Write\Flag\PrimaryKey;
use Shopware\Api\Entity\Write\Flag\Required;
use Shopware\Api\Shipping\Collection\ShippingMethodTranslationBasicCollection;
use Shopware\Api\Shipping\Collection\ShippingMethodTranslationDetailCollection;
use Shopware\Api\Shipping\Event\ShippingMethodTranslation\ShippingMethodTranslationDeletedEvent;
use Shopware\Api\Shipping\Event\ShippingMethodTranslation\ShippingMethodTranslationWrittenEvent;
use Shopware\Api\Shipping\Repository\ShippingMethodTranslationRepository;
use Shopware\Api\Shipping\Struct\ShippingMethodTranslationBasicStruct;
use Shopware\Api\Shipping\Struct\ShippingMethodTranslationDetailStruct;
use Shopware\Api\Shop\Definition\ShopDefinition;

class ShippingMethodTranslationDefinition extends EntityDefinition
{
    /**
     * @var FieldCollection
     */
    protected static $primaryKeys;

    /**
     * @var FieldCollection
     */
    protected static $fields;

    /**
     * @var EntityExtensionInterface[]
     */
    protected static $extensions = [];

    public static function getEntityName(): string
    {
        return 'shipping_method_translation';
    }

    public static function getFields(): FieldCollection
    {
        if (self::$fields) {
            return self::$fields;
        }

        self::$fields = new FieldCollection([
            (new FkField('shipping_method_id', 'shippingMethodId', ShippingMethodDefinition::class))->setFlags(new PrimaryKey(), new Required()),
            new VersionField(),
            (new FkField('language_id', 'languageId', ShopDefinition::class))->setFlags(new PrimaryKey(), new Required()),
            (new ReferenceVersionField(ShopDefinition::class, 'language_version_id'))->setFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->setFlags(new Required()),
            new LongTextField('description', 'description'),
            new StringField('comment', 'comment'),
            new ManyToOneAssociationField('shippingMethod', 'shipping_method_id', ShippingMethodDefinition::class, false),
            new ManyToOneAssociationField('language', 'language_id', ShopDefinition::class, false),
        ]);

        foreach (self::$extensions as $extension) {
            $extension->extendFields(self::$fields);
        }

        return self::$fields;
    }

    public static function getRepositoryClass(): string
    {
        return ShippingMethodTranslationRepository::class;
    }

    public static function getBasicCollectionClass(): string
    {
        return ShippingMethodTranslationBasicCollection::class;
    }

    public static function getDeletedEventClass(): string
    {
        return ShippingMethodTranslationDeletedEvent::class;
    }

    public static function getWrittenEventClass(): string
    {
        return ShippingMethodTranslationWrittenEvent::class;
    }

    public static function getBasicStructClass(): string
    {
        return ShippingMethodTranslationBasicStruct::class;
    }

    public static function getTranslationDefinitionClass(): ?string
    {
        return null;
    }

    public static function getDetailStructClass(): string
    {
        return ShippingMethodTranslationDetailStruct::class;
    }

    public static function getDetailCollectionClass(): string
    {
        return ShippingMethodTranslationDetailCollection::class;
    }
}
