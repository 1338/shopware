<?php declare(strict_types=1);

namespace Shopware\Storefront\Page\Search;

use Shopware\Api\Entity\Search\Criteria;
use Shopware\Api\Product\Struct\ProductSearchResult;
use Shopware\Framework\Struct\Struct;

class SearchPageStruct extends Struct
{
    /**
     * @var ProductSearchResult
     */
    protected $products;

    /**
     * @var Criteria
     */
    protected $criteria;

    /**
     * @var bool
     */
    protected $showListing = true;

    /**
     * @var
     */
    protected $productBoxLayout;

    /**
     * @var int
     */
    protected $currentPage;

    /**
     * @var int
     */
    protected $pageCount;

    public function getProducts(): ProductSearchResult
    {
        return $this->products;
    }

    public function setProducts(ProductSearchResult $products): void
    {
        $this->products = $products;
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    public function setCriteria(Criteria $criteria): void
    {
        $this->criteria = $criteria;
    }

    public function showListing(): bool
    {
        return $this->showListing;
    }

    public function setShowListing(bool $showListing): void
    {
        $this->showListing = $showListing;
    }

    /**
     * @return mixed
     */
    public function getProductBoxLayout(): string
    {
        return $this->productBoxLayout;
    }

    /**
     * @param mixed $productBoxLayout
     */
    public function setProductBoxLayout($productBoxLayout): void
    {
        $this->productBoxLayout = $productBoxLayout;
    }

    public function getPageCount(): int
    {
        return $this->pageCount;
    }

    public function setPageCount(int $pageCount): void
    {
        $this->pageCount = $pageCount;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function setCurrentPage(int $currentPage): void
    {
        $this->currentPage = $currentPage;
    }
}
