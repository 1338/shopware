<?php declare(strict_types=1);

namespace Shopware\Traceable\Cart;

use Shopware\Cart\Cart\CartCalculator;
use Shopware\Cart\Cart\Struct\CalculatedCart;
use Shopware\Cart\Cart\Struct\Cart;
use Shopware\Context\Struct\ShopContext;

class CartCalculatorTracer extends CartCalculator
{
    /**
     * @var CartCalculator
     */
    private $calculator;

    /**
     * @var TracedCartActions
     */
    private $actions;

    public function __construct(
        CartCalculator $calculator,
        TracedCartActions $actions
    ) {
        $this->calculator = $calculator;
        $this->actions = $actions;
    }

    public function calculate(Cart $cart, ShopContext $context): CalculatedCart
    {
        $time = microtime(true);
        $cart = $this->calculator->calculate($cart, $context);

        $required = microtime(true) - $time;
        $this->actions->calculationTime = $required;
        $this->actions->calculatedCart = $cart;
        $this->actions->cart = $cart;
        $this->actions->context = $context;

        return $cart;
    }
}
