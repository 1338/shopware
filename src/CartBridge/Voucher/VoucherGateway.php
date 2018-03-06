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

namespace Shopware\CartBridge\Voucher;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Cart\Price\Struct\PriceDefinition;
use Shopware\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\CartBridge\Voucher\Struct\VoucherData;
use Shopware\CartBridge\Voucher\Struct\VoucherDataCollection;
use Shopware\Context\Rule\Container\AndRule;
use Shopware\Context\Rule\CustomerGroupRule;
use Shopware\Context\Rule\DateRangeRule;
use Shopware\Context\Rule\GoodsPriceRule;
use Shopware\Context\Rule\LineItemInCartRule;
use Shopware\Context\Rule\ProductOfManufacturerRule;
use Shopware\Context\Rule\Rule;
use Shopware\Context\Rule\ShopRule;
use Shopware\Context\Struct\StorefrontContext;

class VoucherGateway implements VoucherGatewayInterface
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function get(array $codes, StorefrontContext $context): VoucherDataCollection
    {
        $query = $this->createVoucherQuery($codes);
        $query->setParameter('codes', $codes, Connection::PARAM_STR_ARRAY);

        $rows = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

        $vouchers = new VoucherDataCollection();
        foreach ($rows as $row) {
            $vouchers->add($this->hydrate($row));
        }

        return $vouchers;
    }

    private function hydrate(array $row): VoucherData
    {
        $price = (float) $row['value'];

        if ($row['percental']) {
            return new \Shopware\CartBridge\Voucher\Struct\VoucherData(
                $row['code'],
                $this->buildRule($row),
                (float) $price,
                null
            );
        }

        return new VoucherData(
            $row['code'],
            $this->buildRule($row),
            null,
            new PriceDefinition($price * -1, new TaxRuleCollection(), 1, true)
        );
    }

    private function createVoucherQuery(array $codes): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select([
            'IFNULL(codes.code, voucher.vouchercode) as code',
            'voucher.vouchercode as number',
            'voucher.modus',
            'voucher.percental',
            'voucher.value',

            //validations
            'voucher.customergroup',
            'voucher.subshopID',
            'voucher.valid_from',
            'voucher.valid_to',
            'voucher.bindtosupplier',
            'voucher.minimumcharge',
            'voucher.restrictarticles',
        ]);
        $query->from('s_emarketing_vouchers', 'voucher');
        $query->leftJoin('voucher', 's_emarketing_voucher_codes', 'codes', 'codes.voucherID = voucher.id AND codes.cashed != 1');
        $query->andWhere('voucher.vouchercode IN (:codes) OR codes.code IN (:codes)');
        $query->setParameter('codes', $codes, Connection::PARAM_STR_ARRAY);

        return $query;
    }

    private function buildRule($row): Rule
    {
        $rule = new AndRule();
        if ($row['customergroup']) {
            $rule->addRule(
                new CustomerGroupRule([(int) $row['customergroup']])
            );
        }

        if ($row['valid_from'] || $row['valid_to']) {
            $rule->addRule(
                new DateRangeRule(
                    $row['valid_from'] ? new \DateTime($row['valid_from']) : null,
                    $row['valid_to'] ? new \DateTime($row['valid_to']) : null
                )
            );
        }

        if ($row['subshopID']) {
            $rule->addRule(
                new ShopRule([(int) $row['subshopID']], Rule::OPERATOR_EQ)
            );
        }

        if ($row['bindtosupplier']) {
            $rule->addRule(
                new ProductOfManufacturerRule([(int) $row['bindtosupplier']])
            );
        }

        if ($row['minimumcharge']) {
            $rule->addRule(
                new GoodsPriceRule(
                    (float) $row['minimumcharge'],
                    Rule::OPERATOR_GTE
                )
            );
        }

        if ($row['restrictarticles']) {
            $rule->addRule(
                new LineItemInCartRule(
                    explode(';', $row['restrictarticles'])
                )
            );
        }

        return $rule;
    }
}
