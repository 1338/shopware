<?php declare(strict_types=1);

namespace Shopware\Api\Product\Definition;

use Shopware\Api\Entity\EntityDefinition;
use Shopware\Api\Entity\EntityExtensionInterface;
use Shopware\Api\Entity\Field\FkField;
use Shopware\Api\Entity\Field\LongTextField;
use Shopware\Api\Entity\Field\ManyToOneAssociationField;
use Shopware\Api\Entity\Field\StringField;
use Shopware\Api\Entity\FieldCollection;
use Shopware\Api\Entity\Write\Flag\PrimaryKey;
use Shopware\Api\Entity\Write\Flag\Required;
use Shopware\Api\Product\Collection\ProductManufacturerTranslationBasicCollection;
use Shopware\Api\Product\Collection\ProductManufacturerTranslationDetailCollection;
use Shopware\Api\Product\Event\ProductManufacturerTranslation\ProductManufacturerTranslationDeletedEvent;
use Shopware\Api\Product\Event\ProductManufacturerTranslation\ProductManufacturerTranslationWrittenEvent;
use Shopware\Api\Product\Repository\ProductManufacturerTranslationRepository;
use Shopware\Api\Product\Struct\ProductManufacturerTranslationBasicStruct;
use Shopware\Api\Product\Struct\ProductManufacturerTranslationDetailStruct;
use Shopware\Api\Shop\Definition\ShopDefinition;
use Shopware\Api\Entity\Field\VersionField;
class ProductManufacturerTranslationDefinition extends EntityDefinition
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
        return 'product_manufacturer_translation';
    }

    public static function getFields(): FieldCollection
    {
        if (self::$fields) {
            return self::$fields;
        }

        self::$fields = new FieldCollection([ 
            new VersionField(),
            (new FkField('product_manufacturer_id', 'productManufacturerId', ProductManufacturerDefinition::class))->setFlags(new PrimaryKey(), new Required()),
            (new FkField('language_id', 'languageId', ShopDefinition::class))->setFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->setFlags(new Required()),
            new LongTextField('description', 'description'),
            new StringField('meta_title', 'metaTitle'),
            new StringField('meta_description', 'metaDescription'),
            new StringField('meta_keywords', 'metaKeywords'),
            new ManyToOneAssociationField('productManufacturer', 'product_manufacturer_id', ProductManufacturerDefinition::class, false),
            new ManyToOneAssociationField('language', 'language_id', ShopDefinition::class, false),
        ]);

        foreach (self::$extensions as $extension) {
            $extension->extendFields(self::$fields);
        }

        return self::$fields;
    }

    public static function getRepositoryClass(): string
    {
        return ProductManufacturerTranslationRepository::class;
    }

    public static function getBasicCollectionClass(): string
    {
        return ProductManufacturerTranslationBasicCollection::class;
    }

    public static function getDeletedEventClass(): string
    {
        return ProductManufacturerTranslationDeletedEvent::class;
    }

    public static function getWrittenEventClass(): string
    {
        return ProductManufacturerTranslationWrittenEvent::class;
    }

    public static function getBasicStructClass(): string
    {
        return ProductManufacturerTranslationBasicStruct::class;
    }

    public static function getTranslationDefinitionClass(): ?string
    {
        return null;
    }

    public static function getDetailStructClass(): string
    {
        return ProductManufacturerTranslationDetailStruct::class;
    }

    public static function getDetailCollectionClass(): string
    {
        return ProductManufacturerTranslationDetailCollection::class;
    }
}
