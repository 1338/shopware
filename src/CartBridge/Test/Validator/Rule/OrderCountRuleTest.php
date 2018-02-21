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

namespace Shopware\CartBridge\Test\Validator\Rule;

use PHPUnit\Framework\TestCase;
use Shopware\Cart\Cart\Struct\CalculatedCart;
use Shopware\CartBridge\Rule\Data\OrderCountRuleData;
use Shopware\CartBridge\Rule\OrderCountRule;
use Shopware\Context\Struct\StorefrontContext;
use Shopware\Framework\Struct\StructCollection;

class OrderCountRuleTest extends TestCase
{
    public function testRuleWithExactMatch(): void
    {
        $rule = new OrderCountRule(1);

        $cart = $this->createMock(CalculatedCart::class);

        $context = $this->createMock(StorefrontContext::class);

        $this->assertTrue(
            $rule->match($cart, $context, new StructCollection([
                OrderCountRuleData::class => new OrderCountRuleData(1),
            ]))->matches()
        );
    }

    public function testRuleNotMatch(): void
    {
        $rule = new OrderCountRule(10);

        $cart = $this->createMock(CalculatedCart::class);

        $context = $this->createMock(StorefrontContext::class);

        $this->assertFalse(
            $rule->match($cart, $context, new StructCollection([
                OrderCountRuleData::class => new OrderCountRuleData(1),
            ]))->matches()
        );
    }

    public function testRuleWithoutDataObject(): void
    {
        $rule = new OrderCountRule(10);

        $cart = $this->createMock(CalculatedCart::class);

        $context = $this->createMock(StorefrontContext::class);

        $this->assertFalse(
            $rule->match($cart, $context, new StructCollection())->matches()
        );
    }

    public function testRuleWithGreaterMatch(): void
    {
        $rule = new OrderCountRule(1);

        $cart = $this->createMock(CalculatedCart::class);

        $context = $this->createMock(StorefrontContext::class);

        $this->assertTrue(
            $rule->match($cart, $context, new StructCollection([
                OrderCountRuleData::class => new OrderCountRuleData(10),
            ]))->matches()
        );
    }
}
