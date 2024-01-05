<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model;

use Getnet\PaymentMagento\Api\Data\NumberTokenInterface;
use Getnet\PaymentMagento\Api\NumberTokenManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface as QuoteCartInterface;

/**
 * Class Number Token Management - Generate number token by card number.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NumberTokenManagement implements NumberTokenManagementInterface
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
     * NumberTokenManagement constructor.
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
     * Generate Number Token by Card Number.
     *
     * @param int                                                  $cartId
     * @param \Getnet\PaymentMagento\Api\Data\NumberTokenInterface $cardNumber
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function generateNumberToken(
        $cartId,
        NumberTokenInterface $cardNumber
    ) {
        $token = [];
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $cardNumber = $cardNumber->getCardNumber();

        $storeId = $quote->getData(QuoteCartInterface::KEY_STORE_ID);

        $numberToken = $this->getNumberToken($storeId, $cardNumber);

        $token['tokenize'] = $numberToken;

        return $token;
    }

    /**
     * Generate Number Token by Card Number for Adminhtml.
     *
     * @param int                                                  $storeId
     * @param \Getnet\PaymentMagento\Api\Data\NumberTokenInterface $cardNumber
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function generateNumberTokenForAdmin(
        $storeId,
        NumberTokenInterface $cardNumber
    ) {
        $token = [];

        $cardNumber = $cardNumber->getCardNumber();

        $numberToken = $this->getNumberToken($storeId, $cardNumber);

        $token['tokenize'] = $numberToken;

        return $token;
    }

    /**
     * Get Number Token.
     *
     * @param int    $storeId
     * @param string $cardNumber
     *
     * @return array
     */
    public function getNumberToken($storeId, $cardNumber)
    {
        $request = [
            'card_number' => $cardNumber,
            'store_id'    => $storeId,
        ];
        $path = 'v1/tokens/card';

        $response = [
            'success' => 0,
        ];

        $data = $this->api->sendPostRequest(
            $path,
            $request,
        );

        if (!empty($data['number_token'])) {
            $response = [
                'success'      => 1,
                'number_token' => $data['number_token'],
            ];
        }

        if (!isset($data['number_token'])) {
            $response = [
                'success' => 0,
                'message' => [
                    'text' => __('Error creating payment. Please, contact the store owner or try again.'),
                ],
            ];
        }

        return $response;
    }
}
