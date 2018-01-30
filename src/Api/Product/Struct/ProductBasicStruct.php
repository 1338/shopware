<?php declare(strict_types=1);

namespace Shopware\Api\Product\Struct;

use Shopware\Api\Entity\Entity;
use Shopware\Api\Tax\Struct\TaxBasicStruct;
use Shopware\Api\Unit\Struct\UnitBasicStruct;

class ProductBasicStruct extends Entity
{
    /**
     * @var string|null
     */
    protected $taxId;

    /**
     * @var string|null
     */
    protected $manufacturerId;

    /**
     * @var string|null
     */
    protected $unitId;

    /**
     * @var float
     */
    protected $price;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $isMain;

    /**
     * @var bool
     */
    protected $active;

    /**
     * @var string|null
     */
    protected $supplierNumber;

    /**
     * @var string|null
     */
    protected $ean;

    /**
     * @var int
     */
    protected $stock;

    /**
     * @var bool
     */
    protected $isCloseout;

    /**
     * @var int|null
     */
    protected $minStock;

    /**
     * @var int|null
     */
    protected $purchaseSteps;

    /**
     * @var int|null
     */
    protected $maxPurchase;

    /**
     * @var int
     */
    protected $minPurchase;

    /**
     * @var float|null
     */
    protected $purchaseUnit;

    /**
     * @var float|null
     */
    protected $referenceUnit;

    /**
     * @var bool
     */
    protected $shippingFree;

    /**
     * @var float
     */
    protected $purchasePrice;

    /**
     * @var int
     */
    protected $pseudoSales;

    /**
     * @var bool
     */
    protected $markAsTopseller;

    /**
     * @var int
     */
    protected $sales;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var float
     */
    protected $weight;

    /**
     * @var float
     */
    protected $width;

    /**
     * @var float
     */
    protected $height;

    /**
     * @var float
     */
    protected $length;

    /**
     * @var string|null
     */
    protected $template;

    /**
     * @var bool
     */
    protected $allowNotification;

    /**
     * @var \DateTime|null
     */
    protected $releaseDate;

    /**
     * @var \DateTime|null
     */
    protected $createdAt;

    /**
     * @var \DateTime|null
     */
    protected $updatedAt;

    /**
     * @var string|null
     */
    protected $containerId;

    /**
     * @var string|null
     */
    protected $priceGroupId;

    /**
     * @var array|null
     */
    protected $categoryTree;

    /**
     * @var array|null
     */
    protected $prices;

    /**
     * @var string|null
     */
    protected $additionalText;

    /**
     * @var string|null
     */
    protected $keywords;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var string|null
     */
    protected $descriptionLong;

    /**
     * @var string|null
     */
    protected $metaTitle;

    /**
     * @var string|null
     */
    protected $packUnit;

    /**
     * @var TaxBasicStruct|null
     */
    protected $tax;

    /**
     * @var ProductManufacturerBasicStruct|null
     */
    protected $manufacturer;

    /**
     * @var UnitBasicStruct|null
     */
    protected $unit;

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function setTaxId(?string $taxId): void
    {
        $this->taxId = $taxId;
    }

    public function getManufacturerId(): ?string
    {
        return $this->manufacturerId;
    }

    public function setManufacturerId(?string $manufacturerId): void
    {
        $this->manufacturerId = $manufacturerId;
    }

    public function getUnitId(): ?string
    {
        return $this->unitId;
    }

    public function setUnitId(?string $unitId): void
    {
        $this->unitId = $unitId;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getIsMain(): bool
    {
        return $this->isMain;
    }

    public function setIsMain(bool $isMain): void
    {
        $this->isMain = $isMain;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getSupplierNumber(): ?string
    {
        return $this->supplierNumber;
    }

    public function setSupplierNumber(?string $supplierNumber): void
    {
        $this->supplierNumber = $supplierNumber;
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function setEan(?string $ean): void
    {
        $this->ean = $ean;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): void
    {
        $this->stock = $stock;
    }

    public function getIsCloseout(): bool
    {
        return $this->isCloseout;
    }

    public function setIsCloseout(bool $isCloseout): void
    {
        $this->isCloseout = $isCloseout;
    }

    public function getMinStock(): ?int
    {
        return $this->minStock;
    }

    public function setMinStock(?int $minStock): void
    {
        $this->minStock = $minStock;
    }

    public function getPurchaseSteps(): ?int
    {
        return $this->purchaseSteps;
    }

    public function setPurchaseSteps(?int $purchaseSteps): void
    {
        $this->purchaseSteps = $purchaseSteps;
    }

    public function getMaxPurchase(): ?int
    {
        return $this->maxPurchase;
    }

    public function setMaxPurchase(?int $maxPurchase): void
    {
        $this->maxPurchase = $maxPurchase;
    }

    public function getMinPurchase(): int
    {
        return $this->minPurchase;
    }

    public function setMinPurchase(int $minPurchase): void
    {
        $this->minPurchase = $minPurchase;
    }

    public function getPurchaseUnit(): ?float
    {
        return $this->purchaseUnit;
    }

    public function setPurchaseUnit(?float $purchaseUnit): void
    {
        $this->purchaseUnit = $purchaseUnit;
    }

    public function getReferenceUnit(): ?float
    {
        return $this->referenceUnit;
    }

    public function setReferenceUnit(?float $referenceUnit): void
    {
        $this->referenceUnit = $referenceUnit;
    }

    public function getShippingFree(): bool
    {
        return $this->shippingFree;
    }

    public function setShippingFree(bool $shippingFree): void
    {
        $this->shippingFree = $shippingFree;
    }

    public function getPurchasePrice(): float
    {
        return $this->purchasePrice;
    }

    public function setPurchasePrice(float $purchasePrice): void
    {
        $this->purchasePrice = $purchasePrice;
    }

    public function getPseudoSales(): int
    {
        return $this->pseudoSales;
    }

    public function setPseudoSales(int $pseudoSales): void
    {
        $this->pseudoSales = $pseudoSales;
    }

    public function getMarkAsTopseller(): bool
    {
        return $this->markAsTopseller;
    }

    public function setMarkAsTopseller(bool $markAsTopseller): void
    {
        $this->markAsTopseller = $markAsTopseller;
    }

    public function getSales(): int
    {
        return $this->sales;
    }

    public function setSales(int $sales): void
    {
        $this->sales = $sales;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): void
    {
        $this->weight = $weight;
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function setWidth(float $width): void
    {
        $this->width = $width;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function setHeight(float $height): void
    {
        $this->height = $height;
    }

    public function getLength(): float
    {
        return $this->length;
    }

    public function setLength(float $length): void
    {
        $this->length = $length;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(?string $template): void
    {
        $this->template = $template;
    }

    public function getAllowNotification(): bool
    {
        return $this->allowNotification;
    }

    public function setAllowNotification(bool $allowNotification): void
    {
        $this->allowNotification = $allowNotification;
    }

    public function getReleaseDate(): ?\DateTime
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTime $releaseDate): void
    {
        $this->releaseDate = $releaseDate;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getContainerId(): ?string
    {
        return $this->containerId;
    }

    public function setContainerId(?string $containerId): void
    {
        $this->containerId = $containerId;
    }

    public function getPriceGroupId(): ?string
    {
        return $this->priceGroupId;
    }

    public function setPriceGroupId(?string $priceGroupId): void
    {
        $this->priceGroupId = $priceGroupId;
    }

    public function getCategoryTree(): ?array
    {
        return $this->categoryTree;
    }

    public function setCategoryTree(?array $categoryTree): void
    {
        $this->categoryTree = $categoryTree;
    }

    public function getPrices(): ?array
    {
        return $this->prices;
    }

    public function setPrices(?array $prices): void
    {
        $this->prices = $prices;
    }

    public function getAdditionalText(): ?string
    {
        return $this->additionalText;
    }

    public function setAdditionalText(?string $additionalText): void
    {
        $this->additionalText = $additionalText;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): void
    {
        $this->keywords = $keywords;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDescriptionLong(): ?string
    {
        return $this->descriptionLong;
    }

    public function setDescriptionLong(?string $descriptionLong): void
    {
        $this->descriptionLong = $descriptionLong;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): void
    {
        $this->metaTitle = $metaTitle;
    }

    public function getPackUnit(): ?string
    {
        return $this->packUnit;
    }

    public function setPackUnit(?string $packUnit): void
    {
        $this->packUnit = $packUnit;
    }

    public function getTax(): ?TaxBasicStruct
    {
        return $this->tax;
    }

    public function setTax(?TaxBasicStruct $tax): void
    {
        $this->tax = $tax;
    }

    public function getManufacturer(): ?ProductManufacturerBasicStruct
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?ProductManufacturerBasicStruct $manufacturer): void
    {
        $this->manufacturer = $manufacturer;
    }

    public function getUnit(): ?UnitBasicStruct
    {
        return $this->unit;
    }

    public function setUnit(?UnitBasicStruct $unit): void
    {
        $this->unit = $unit;
    }
}
