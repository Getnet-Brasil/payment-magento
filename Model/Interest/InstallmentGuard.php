<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\Interest;

use Getnet\PaymentMagento\Api\Data\InstallmentSelectedInterface;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

/**
 * Ensures the order is charged with the installments the stored interest was priced for.
 *
 * The interest (or the cash discount of 1x) is priced by /getnet-interest from the installment the shopper
 * selected, while the installment sent to Getnet comes from the payment data at place order. Getnet always
 * receives INSTALL_NO_INTEREST, so without this check a shopper can price 1x (cash discount) and place the
 * order in 12x, financed by the merchant.
 *
 * Interest is deterministic (store config), so the expected value is recalculated instead of stored.
 */
class InstallmentGuard
{
    public const METHOD_CC = 'getnet_paymentmagento_cc';

    public const METHOD_CC_VAULT = 'getnet_paymentmagento_cc_vault';

    public const METHOD_GOOGLEPAY = 'getnet_paymentmagento_googlepay';

    public const METHOD_TWO_CC = 'getnet_paymentmagento_two_cc';

    /**
     * Rounding tolerance between amounts.
     */
    private const TOLERANCE = 0.01;

    /**
     * @var ConfigCc
     */
    private $configCc;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ConfigCc        $configCc
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConfigCc $configCc,
        LoggerInterface $logger
    ) {
        $this->configCc = $configCc;
        $this->logger = $logger;
    }

    /**
     * Validate the quote about to be submitted.
     *
     * @param Quote $quote
     *
     * @throws LocalizedException when installments or amounts differ from what the interest was priced for
     *
     * @return void
     */
    public function validate(Quote $quote): void
    {
        $payment = $quote->getPayment();
        $method = $payment ? (string) $payment->getMethod() : '';

        if (in_array($method, [self::METHOD_CC, self::METHOD_CC_VAULT, self::METHOD_GOOGLEPAY], true)) {
            $this->validateSingleCard($quote);

            return;
        }

        if ($method === self::METHOD_TWO_CC) {
            $this->validateTwoCards($quote);
        }
    }

    /**
     * Single card (also used by the saved-card and Google Pay methods).
     *
     * @param Quote $quote
     *
     * @throws LocalizedException
     *
     * @return void
     */
    private function validateSingleCard(Quote $quote): void
    {
        $storeId = (int) $quote->getStoreId();
        $installments = $this->installments($quote->getPayment()->getAdditionalInformation('cc_installments'));
        $interest = $this->storedInterest($quote);
        $base = (float) $quote->getBaseGrandTotal() - $interest;

        $this->assertInstallmentAllowed($quote, 'single', $installments, $base);

        $expected = $this->configCc->getCalcInterest($installments, $base, $storeId);

        $this->assertInterest($quote, $expected, $interest, ['installments' => $installments]);
    }

    /**
     * Two cards: each card must respect the installment rules and the split must cover the order.
     *
     * @param Quote $quote
     *
     * @throws LocalizedException
     *
     * @return void
     */
    private function validateTwoCards(Quote $quote): void
    {
        $storeId = (int) $quote->getStoreId();
        $payment = $quote->getPayment();
        $interest = $this->storedInterest($quote);
        $base = (float) $quote->getBaseGrandTotal() - $interest;
        $firstAmount = round((float) $payment->getAdditionalInformation('cc_payment_first_amount'), 2);
        $secondAmount = round($base - $firstAmount, 2);

        if ($firstAmount <= 0 || $secondAmount <= 0) {
            $this->reject($quote, 'invalid two-card split', [
                'first_amount' => $firstAmount,
                'total'        => $base,
            ]);
        }

        $firstInstallments = $this->installments($payment->getAdditionalInformation('cc_installments'));
        $secondInstallments = $this->installments($payment->getAdditionalInformation('cc_secondary_installments'));

        $this->assertInstallmentAllowed($quote, 'card1', $firstInstallments, $firstAmount);
        $this->assertInstallmentAllowed($quote, 'card2', $secondInstallments, $secondAmount);

        $expected = $this->configCc->getCalcInterest($firstInstallments, $firstAmount, $storeId)
            + $this->configCc->getCalcInterest($secondInstallments, $secondAmount, $storeId);

        $this->assertInterest($quote, $expected, $interest, [
            'installments'           => $firstInstallments,
            'secondary_installments' => $secondInstallments,
        ]);
    }

    /**
     * The installment must be offered for the amount (same rules as the checkout installment list).
     *
     * @param Quote  $quote
     * @param string $label
     * @param int    $installments
     * @param float  $amount
     *
     * @throws LocalizedException
     *
     * @return void
     */
    private function assertInstallmentAllowed(Quote $quote, string $label, int $installments, float $amount): void
    {
        $storeId = (int) $quote->getStoreId();
        $limit = max(1, (int) $this->configCc->getMaxInstallment($storeId));
        $minInstallment = (float) $this->configCc->getMinInstallment($storeId);

        if ($minInstallment > 0) {
            $limit = min($limit, (int) floor($amount / $minInstallment));
        }

        $limit = max(1, $limit);

        if ($installments > $limit) {
            $this->reject($quote, 'installments above the allowed limit', [
                'card'         => $label,
                'installments' => $installments,
                'limit'        => $limit,
            ]);
        }
    }

    /**
     * The interest on the quote must be the one priced for the installments being placed.
     *
     * @param Quote $quote
     * @param float $expected
     * @param float $stored
     * @param array $details
     *
     * @throws LocalizedException
     *
     * @return void
     */
    private function assertInterest(Quote $quote, float $expected, float $stored, array $details): void
    {
        if (abs(round($expected, 2) - round($stored, 2)) > self::TOLERANCE) {
            $this->reject($quote, 'stored interest differs from installments', $details + [
                'interest'          => $stored,
                'expected_interest' => $expected,
            ]);
        }
    }

    /**
     * Installments as sent by the checkout (missing means 1x, as the request builders assume).
     *
     * @param mixed $value
     *
     * @return int
     */
    private function installments($value): int
    {
        return max(1, (int) $value);
    }

    /**
     * Interest currently stored on the quote.
     *
     * @param Quote $quote
     *
     * @return float
     */
    private function storedInterest(Quote $quote): float
    {
        return (float) $quote->getData(InstallmentSelectedInterface::BASE_GETNET_INTEREST_AMOUNT);
    }

    /**
     * Log the mismatch and stop the order.
     *
     * @param Quote  $quote
     * @param string $reason
     * @param array  $details
     *
     * @throws LocalizedException
     *
     * @return void
     */
    private function reject(Quote $quote, string $reason, array $details): void
    {
        $this->logger->warning('Getnet installment guard rejected order', [
            'reason'   => $reason,
            'quote_id' => $quote->getId(),
            'method'   => $quote->getPayment()->getMethod(),
            'details'  => $details,
        ]);

        throw new LocalizedException(
            __('The installment conditions have changed. Please select the installments again.')
        );
    }
}
