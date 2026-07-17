<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\Api\TwoCc;

use Getnet\PaymentMagento\Api\TwoCc\Data\AmountForCalcInterface;
use Getnet\PaymentMagento\Api\TwoCc\Data\IdxInTwoCcInterface;
use Getnet\PaymentMagento\Api\TwoCc\Data\TwoCcInstallmentInterface;
use Getnet\PaymentMagento\Api\TwoCc\TwoCcInterestManagementInterface;
use Getnet\PaymentMagento\Gateway\Config\Config as ConfigBase;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface as QuoteCartInterface;

/**
 * Class Two Cc Interest Management - Generate Interest in order.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TwoCcInterestManagement implements TwoCcInterestManagementInterface
{
    /**
     * Credit Card - Block Name.
     */
    public const CREDIT_CARD = 'CREDIT_CARD';

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var CartTotalRepositoryInterface
     */
    protected $quoteTotalRepository;

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var ConsultPSInstallments
     */
    protected $consultInstallments;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * ListInstallmentsManagement constructor.
     *
     * @param CartRepositoryInterface      $quoteRepository
     * @param CartTotalRepositoryInterface $quoteTotalRepository
     * @param ConfigBase                   $configBase
     * @param ConfigCc                     $configCc
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        CartTotalRepositoryInterface $quoteTotalRepository,
        ConfigBase $configBase,
        ConfigCc $configCc
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteTotalRepository = $quoteTotalRepository;
        $this->configBase = $configBase;
        $this->configCc = $configCc;
    }

    /**
     * Generate List Installments.
     *
     * @param int                                                             $cartId
     * @param \Getnet\PaymentMagento\Api\TwoCc\Data\AmountForCalcInterface    $amountForCalc
     * @param \Getnet\PaymentMagento\Api\TwoCc\Data\IdxInTwoCcInterface       $idxInTwoCc
     * @param \Getnet\PaymentMagento\Api\TwoCc\Data\TwoCcInstallmentInterface $twoCcInstallment
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function generateGetnetTwoInterest(
        $cartId,
        AmountForCalcInterface $amountForCalc,
        IdxInTwoCcInterface $idxInTwoCc,
        TwoCcInstallmentInterface $twoCcInstallment
    ) {
        $interest = 0;
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $amountInRequest = $amountForCalc->getGetnetAmountForCalc();
        $idxInTwoCc = $idxInTwoCc->getGetnetIdxInTwoCc();
        $installment = $twoCcInstallment->getGetnetTwoCcInstallment();

        $storeId = $quote->getData(QuoteCartInterface::KEY_STORE_ID);

        $amount = $amountInRequest;
        $currentInterest = $quote->getData(TwoCcInstallmentInterface::GETNET_INTEREST_AMOUNT);

        if (!$installment) {
            $amount -= $currentInterest;
        }

        $interest = $this->configCc->getCalcInterest($installment, $amount, $storeId);

        if ($idxInTwoCc) {
            $interest += $currentInterest;
        }

        try {
            $quote->setData(TwoCcInstallmentInterface::GETNET_INTEREST_AMOUNT, $interest);
            $quote->setData(TwoCcInstallmentInterface::BASE_GETNET_INTEREST_AMOUNT, $interest);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Unable to save interest.'));
        }

        return $this->quoteTotalRepository->get($cartId);
    }
}
