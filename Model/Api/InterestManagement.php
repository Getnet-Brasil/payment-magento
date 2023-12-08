<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface as QuoteCartInterface;
use Getnet\PaymentMagento\Api\Data\InstallmentSelectedInterface;
use Getnet\PaymentMagento\Api\InterestManagementInterface;
use Getnet\PaymentMagento\Gateway\Config\Config as ConfigBase;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;

/**
 * Class List Interest Management - Generate Interest in order.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InterestManagement implements InterestManagementInterface
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
     * @param int                                                          $cartId
     * @param \Getnet\PaymentMagento\Api\Data\InstallmentSelectedInterface $installmentSelected
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function generateGetnetInterest(
        $cartId,
        InstallmentSelectedInterface $installmentSelected
    ) {
        $interest = 0;
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $quoteTotal = $this->quoteTotalRepository->get($cartId);

        $installmentSelected = $installmentSelected->getInstallmentSelected();

        $storeId = $quote->getData(QuoteCartInterface::KEY_STORE_ID);

        $amount = $quoteTotal->getBaseGrandTotal();
        $currentInterest = $quote->getData(InstallmentSelectedInterface::GETNET_INTEREST_AMOUNT);
        $amount -= $currentInterest;

        $interest = $this->configCc->getCalcInterest($installmentSelected, $amount, $storeId);

        try {
            $quote->setData(InstallmentSelectedInterface::GETNET_INTEREST_AMOUNT, $interest);
            $quote->setData(InstallmentSelectedInterface::BASE_GETNET_INTEREST_AMOUNT, $interest);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Unable to save interest.'));
        }

        return $this->quoteTotalRepository->get($cartId);
    }
}
