<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model;

use Getnet\PaymentMagento\Api\CardIdManagementInterface;
use Getnet\PaymentMagento\Api\Data\CardIdInterface;
use InvalidArgumentException;
use Getnet\PaymentMagento\Model\ApiManagement;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface as QuoteCartInterface;
use Magento\Vault\Model\PaymentTokenManagement;

/**
 * Class Card Id Management - Get Details Card Id.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CardIdManagement implements CardIdManagementInterface
{
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var ApiManagement
     */
    private $api;

    /**
     * @var PaymentTokenManagement
     */
    private $tokenManagement;

    /**
     * CardIdManagement constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param ApiManagement           $api
     * @param PaymentTokenManagement  $tokenManagement
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        ApiManagement $api,
        PaymentTokenManagement $tokenManagement
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->api = $api;
        $this->tokenManagement = $tokenManagement;
    }

    /**
     * Generate Number Token by Card Number.
     *
     * @param int                                             $cartId
     * @param \Getnet\PaymentMagento\Api\Data\CardIdInterface $cardId
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function getDetailsCardId(
        $cartId,
        CardIdInterface $cardId
    ) {
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }
        $data = [];

        $hash = $cardId->getCardId();

        $storeId = $quote->getData(QuoteCartInterface::KEY_STORE_ID);

        $customerId = $quote->getCustomer()->getId();

        /** @var PaymentTokenManagement $paymentToken */
        $paymentToken = $this->tokenManagement->getByPublicHash($hash, $customerId);

        $gatawayToken = $paymentToken->getGatewayToken();

        $data['details'] = $this->getCardId($storeId, $gatawayToken);

        return $data;
    }

    /**
     * Get Number Token.
     *
     * @param int    $storeId
     * @param string $cardId
     *
     * @return array|\Magento\Framework\Phrase
     */
    public function getCardId($storeId, $cardId)
    {
        $request = ['store_id' => $storeId];
        $path = 'v1/cards/'.$cardId;

        $data = $this->api->sendGetRequest($path, $request);

        $response = [
            'success' => 0,
        ];

        if (isset($data['number_token'])) {
            $response = [
                'success' => 1,
                'card'    => $data,
            ];
        }

        return $response;
    }
}
