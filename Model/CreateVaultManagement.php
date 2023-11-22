<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model;

use Getnet\PaymentMagento\Api\CreateVaultManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface as QuoteCartInterface;

/**
 * Class Create Vault Management - Generate number token by card number in API Cofre.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateVaultManagement implements CreateVaultManagementInterface
{
    /**
     * @var ApiManagement
     */
    private $api;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * CreateVaultManagement constructor.
     *
     * @param ApiManagement           $api
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        ApiManagement $api,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->api = $api;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Create Vault Card Id.
     *
     * @param int   $cartId
     * @param array $vaultData
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function createVault(
        $cartId,
        $vaultData
    ) {
        $token = [];
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $storeId = $quote->getData(QuoteCartInterface::KEY_STORE_ID);

        $numberToken = $this->getTokenCcNumber($storeId, $vaultData);
        $token['tokenize'] = [
            'error' => __('Error creating payment, please try again later. COD: 401'),
        ];
        if ($numberToken) {
            unset($token);
            $vaultDetails = $this->getVaultDetails($storeId, $numberToken, $vaultData);
            $token['tokenize'] = $vaultDetails;
        }

        return $token;
    }

    /**
     * Get Token Cc Number.
     *
     * @param int   $storeId
     * @param array $vaultData
     *
     * @return null|string
     */
    public function getTokenCcNumber($storeId, $vaultData)
    {
        $request = [
            'card_number' => $vaultData['card_number'],
            'store_id'    => $storeId,
        ];
        $path = '/v1/tokens/card';
        $response = null;

        $data = $this->api->sendPostRequest(
            $path,
            $request,
        );

        if (isset($data['number_token'])) {
            $response = $data['number_token'];
        }

        return $response;
    }

    /**
     * Get Vault Details.
     *
     * @param int    $storeId
     * @param string $numberToken
     * @param array  $vaultData
     *
     * @return array
     */
    public function getVaultDetails($storeId, $numberToken, $vaultData)
    {
        $response = [];

        $month = $vaultData['expiration_month'];
        if (strlen($month) === 1) {
            $month = '0'.$month;
        }

        $request = [
            'number_token'      => $numberToken,
            'expiration_month'  => $month,
            'expiration_year'   => $vaultData['expiration_year'],
            'customer_id'       => $vaultData['customer_email'],
            'cardholder_name'   => $vaultData['cardholder_name'],
            'verify_card'       => false,
            'store_id'          => $storeId,
        ];

        $path = 'v1/cards';

        $data = $this->api->sendPostRequest(
            $path,
            $request,
        );

        if (isset($data['card_id'])) {
            $response = [
                'success'      => 1,
                'card_id'      => $data['card_id'],
                'number_token' => $data['number_token'],
            ];
        }

        if (isset($data['details'])) {
            $response = [
                'success' => 0,
                'message' => [
                    'code' => $data['details'][0]['error_code'],
                    'text' => $data['details'][0]['description_detail'],
                ],
            ];
        }

        return $response;
    }
}
