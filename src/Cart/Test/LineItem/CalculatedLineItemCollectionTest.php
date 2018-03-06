<?php declare(strict_types=1);
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Cart\Test\LineItem;

use PHPUnit\Framework\TestCase;
use Shopware\Cart\LineItem\CalculatedLineItem;
use Shopware\Cart\LineItem\CalculatedLineItemCollection;
use Shopware\Cart\LineItem\GoodsInterface;
use Shopware\Cart\LineItem\LineItem;
use Shopware\Cart\Price\Struct\Price;
use Shopware\Cart\Price\Struct\PriceCollection;
use Shopware\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Cart\Test\Common\ConfiguredLineItem;
use Shopware\CartBridge\Product\ProductProcessor;
use Shopware\CartBridge\Voucher\Struct\CalculatedVoucher;
use Shopware\CartBridge\Voucher\VoucherProcessor;
use Shopware\Context\Rule\Container\AndRule;

class CalculatedLineItemCollectionTest extends TestCase
{
    public const DUMMY_TAX_NAME = 'dummy-tax';

    public function testCollectionIsCountable(): void
    {
        $collection = new CalculatedLineItemCollection();
        static::assertCount(0, $collection);
    }

    public function testCountReturnsCorrectValue(): void
    {
        $collection = new CalculatedLineItemCollection([
            self::createLineItem('A'),
            self::createLineItem('B'),
            self::createLineItem('C'),
        ]);
        static::assertCount(3, $collection);
    }

    public function testCollectionOverwriteExistingIdentifierWithLastItem(): void
    {
        $collection = new CalculatedLineItemCollection([
            self::createLineItem('A'),
            self::createLineItem('A', 2),
            self::createLineItem('A', 3),
        ]);

        static::assertEquals(
            new CalculatedLineItemCollection([
                self::createLineItem('A', 3),
            ]),
            $collection
        );
    }

    public function testFilterReturnsNewCollectionWithCorrectItems(): void
    {
        $collection = new CalculatedLineItemCollection([
            new CalculatedVoucher(
                'Code1',
                new LineItem('1', ProductProcessor::TYPE_PRODUCT, 1),
                new Price(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()),
                'voucher',
                new AndRule()
            ),
            new CalculatedVoucher(
                'Code1',
                new LineItem('2', ProductProcessor::TYPE_PRODUCT, 1),
                new Price(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()),
                'voucher',
                new AndRule()
            ),
            self::createLineItem('A', 3),
            self::createLineItem('B', 3),
            self::createLineItem('C', 3),
            self::createLineItem('D', 3),
        ]);

        static::assertEquals(
            new CalculatedLineItemCollection([
                self::createLineItem('A', 3),
                self::createLineItem('B', 3),
                self::createLineItem('C', 3),
                self::createLineItem('D', 3),
            ]),
            $collection->filterInstance(CalculatedLineItem::class)
        );

        static::assertEquals(
            new CalculatedLineItemCollection([
                new CalculatedVoucher(
                    'Code1',
                    new LineItem('1', ProductProcessor::TYPE_PRODUCT, 1),
                    new Price(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()),
                    'voucher',
                    new AndRule()
                ),
                new CalculatedVoucher(
                    'Code1',
                    new LineItem('2', ProductProcessor::TYPE_PRODUCT, 1),
                    new Price(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()),
                    'voucher',
                    new AndRule()
                ),
            ]),
            $collection->filterInstance(CalculatedVoucher::class)
        );
    }

    public function testFilterReturnsNewCollection(): void
    {
        $collection = new CalculatedLineItemCollection([
            self::createLineItem('A', 3),
            self::createLineItem('B', 3),
            self::createLineItem('C', 3),
            self::createLineItem('D', 3),
        ]);

        static::assertNotSame(
            new CalculatedLineItemCollection([
                self::createLineItem('A', 3),
                self::createLineItem('B', 3),
                self::createLineItem('C', 3),
                self::createLineItem('D', 3),
            ]),
            $collection->filterInstance(ConfiguredLineItem::class)
        );
    }

    public function testLineItemsCanBeCleared(): void
    {
        $collection = new CalculatedLineItemCollection([
            self::createLineItem('A', 3),
            self::createLineItem('B', 3),
            self::createLineItem('C', 3),
            self::createLineItem('D', 3),
        ]);

        $collection->clear();
        static::assertEquals(new CalculatedLineItemCollection(), $collection);
    }

    public function testLineItemsCanBeRemovedByIdentifier(): void
    {
        $collection = new CalculatedLineItemCollection([
            self::createLineItem('A', 3),
            self::createLineItem('B', 3),
            self::createLineItem('C', 3),
            self::createLineItem('D', 3),
        ]);

        $collection->remove('A');

        static::assertEquals(
            new CalculatedLineItemCollection([
                self::createLineItem('B', 3),
                self::createLineItem('C', 3),
                self::createLineItem('D', 3),
            ]),
            $collection
        );
    }

    public function testIdentifiersCanEasyAccessed(): void
    {
        $collection = new CalculatedLineItemCollection([
            self::createLineItem('A', 3),
            self::createLineItem('B', 3),
            self::createLineItem('C', 3),
            self::createLineItem('D', 3),
        ]);

        static::assertSame(
            ['A', 'B', 'C', 'D'],
            $collection->getIdentifiers()
        );
    }

    public function testFillCollectionWithItems(): void
    {
        $collection = new CalculatedLineItemCollection();
        $collection->fill([
            self::createLineItem('A', 3),
            self::createLineItem('B', 3),
            self::createLineItem('C', 3),
            self::createLineItem('D', 3),
        ]);

        static::assertEquals(
            new CalculatedLineItemCollection([
                self::createLineItem('A', 3),
                self::createLineItem('B', 3),
                self::createLineItem('C', 3),
                self::createLineItem('D', 3),
            ]),
            $collection
        );
    }

    public function testGetLineItemByIdentifier(): void
    {
        $collection = new CalculatedLineItemCollection([
            self::createLineItem('A', 3),
            self::createLineItem('B', 3),
            self::createLineItem('C', 3),
            self::createLineItem('D', 3),
        ]);

        static::assertEquals(
            self::createLineItem('C', 3),
            $collection->get('C')
        );
    }

    public function testGetOnEmptyCollection(): void
    {
        $collection = new CalculatedLineItemCollection();
        static::assertNull($collection->get('not found'));
    }

    public function testFilterGoodsReturnsOnlyGoods(): void
    {
        $collection = new CalculatedLineItemCollection([
            new ConfiguredGoodsItem('A', 3),
            self::createLineItem('B', 3),
            self::createLineItem('C', 3),
            new ConfiguredGoodsItem('D', 3),
        ]);

        static::assertEquals(
            new CalculatedLineItemCollection([
                new ConfiguredGoodsItem('A', 3),
                new ConfiguredGoodsItem('D', 3),
            ]),
            $collection->filterGoods()
        );
    }

    public function testFilterGoodsReturnsNewCollection(): void
    {
        $collection = new CalculatedLineItemCollection([
            new ConfiguredGoodsItem('A', 3),
            self::createLineItem('B', 3),
            self::createLineItem('C', 3),
            new ConfiguredGoodsItem('D', 3),
        ]);

        static::assertNotSame(
            new CalculatedLineItemCollection([
                new ConfiguredGoodsItem('A', 3),
                new ConfiguredGoodsItem('D', 3),
            ]),
            $collection->filterGoods()
        );
    }

    public function testGetPricesCollectionOfMultipleItems(): void
    {
        $collection = new CalculatedLineItemCollection([
            new CalculatedVoucher(
                'Code1',
                new LineItem('1', VoucherProcessor::TYPE_VOUCHER, 1),
                new Price(200, 200, new CalculatedTaxCollection(), new TaxRuleCollection()),
                'voucher',
                new AndRule()
            ),
            new CalculatedVoucher(
                'Code1',
                new LineItem('2', VoucherProcessor::TYPE_VOUCHER, 1),
                new Price(300, 300, new CalculatedTaxCollection(), new TaxRuleCollection()),
                'voucher',
                new AndRule()
            ),
        ]);

        static::assertEquals(
            new PriceCollection([
                new Price(200, 200, new CalculatedTaxCollection(), new TaxRuleCollection()),
                new Price(300, 300, new CalculatedTaxCollection(), new TaxRuleCollection()),
            ]),
            $collection->getPrices()
        );
    }

    public function testRemoveWithNoneExistingIdentifier(): void
    {
        $collection = new CalculatedLineItemCollection([
            self::createLineItem('A', 3),
            self::createLineItem('B', 3),
            self::createLineItem('C', 3),
            self::createLineItem('D', 3),
        ]);

        $collection->remove('X');
        static::assertEquals(
            new CalculatedLineItemCollection([
                self::createLineItem('A', 3),
                self::createLineItem('B', 3),
                self::createLineItem('C', 3),
                self::createLineItem('D', 3),
            ]),
            $collection
        );
    }

    public function testRemoveElement(): void
    {
        $c = self::createLineItem('C', 3);

        $collection = new CalculatedLineItemCollection([
            self::createLineItem('A', 3),
            self::createLineItem('B', 3),
            $c,
            self::createLineItem('D', 3),
        ]);

        $collection->removeElement($c);

        $this->assertEquals(
            new CalculatedLineItemCollection([
                self::createLineItem('A', 3),
                self::createLineItem('B', 3),
                self::createLineItem('D', 3),
            ]),
            $collection
        );
    }

    public function testExists(): void
    {
        $c = self::createLineItem('C', 3);

        $collection = new CalculatedLineItemCollection([
            self::createLineItem('A', 3),
            self::createLineItem('B', 3),
            $c,
            self::createLineItem('D', 3),
        ]);

        $this->assertTrue($collection->exists($c));
        $collection->removeElement($c);
        $this->assertFalse($collection->exists($c));
    }

    public function testRemoveWithNotExisting(): void
    {
        $c = self::createLineItem('C', 3);

        $collection = new CalculatedLineItemCollection([
            self::createLineItem('A', 3),
            self::createLineItem('B', 3),
            self::createLineItem('D', 3),
        ]);

        $collection->removeElement($c);

        $this->assertEquals(
            new CalculatedLineItemCollection([
                self::createLineItem('A', 3),
                self::createLineItem('B', 3),
                self::createLineItem('D', 3),
            ]),
            $collection
        );
    }

    private static function createLineItem(string $identifier, int $quantity = 1)
    {
        return new CalculatedLineItem(
            $identifier,
            new Price(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection()),
            $quantity,
            $identifier,
            $identifier
        );
    }
}

class ConfiguredGoodsItem extends CalculatedLineItem implements GoodsInterface
{
    public function __construct($identifier, $quantity = 1)
    {
        parent::__construct(
            $identifier,
            new Price(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection()),
            $quantity,
            $identifier,
            $identifier
        );
    }
}
