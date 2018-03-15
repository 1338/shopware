<?php declare(strict_types=1);

namespace Shopware\Payment\PaymentHandler;

use Shopware\Api\Order\Repository\OrderTransactionRepository;
use Shopware\Context\Struct\ShopContext;
use Shopware\Defaults;
use Shopware\Payment\Struct\PaymentTransaction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PrePayment implements PaymentHandlerInterface
{
    /**
     * @var OrderTransactionRepository
     */
    private $transactionRepository;

    public function __construct(OrderTransactionRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function pay(PaymentTransaction $transaction, ShopContext $context): ?RedirectResponse
    {
        $data = [
            'id' => $transaction->getTransactionId(),
            'orderTransactionStateId' => Defaults::ORDER_TRANSACTION_COMPLETED,
        ];
        $this->transactionRepository->update([$data], $context);

        return null;
    }

    public function finalize(string $transactionId, Request $request, ShopContext $context): void
    {
    }
}
