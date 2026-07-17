<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client\Card\Operations;

use Getnet\PaymentMagento\Gateway\Http\Api;
use Getnet\PaymentMagento\Gateway\Http\EndpointResolver;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Class Accept Payment Client - Returns authorization to accept payment.
 *
 * @SuppressWarnings(PHPCPD)
 */
class AcceptPaymentClient implements ClientInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Store Id - Block name.
     */
    public const STORE_ID = 'store_id';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Approved - Value.
     */
    public const RESPONSE_STATUS_CONFIRMED = 'CONFIRMED';

    /**
     * Response Pay Status Captured - Value.
     */
    public const RESPONSE_STATUS_CAPTURED = 'CAPTURED';

    /**
     * Response Pay Status Denied - Value.
     */
    public const RESPONSE_STATUS_ERROR = 'ERROR';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var EndpointResolver
     */
    protected $endpointResolver;

    /**
     * @param Api              $api
     * @param EndpointResolver $endpointResolver
     */
    public function __construct(
        Api $api,
        EndpointResolver $endpointResolver
    ) {
        $this->api = $api;
        $this->endpointResolver = $endpointResolver;
    }

    /**
     * Places request to gateway.
     *
     * @param TransferInterface $transferObject
     *
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $success = 0;
        $request = $transferObject->getBody();

        $data = $this->api->sendPostRequest(
            $transferObject,
            $this->endpointResolver->resolve(
                EndpointResolver::CARD_CAPTURE,
                $request['store_id'] ?? null
            ),
            $request,
        );

        $state = isset($data[self::RESPONSE_STATUS]) ? $data[self::RESPONSE_STATUS] : 0;

        if ($state === self::RESPONSE_STATUS_CONFIRMED || $state === self::RESPONSE_STATUS_CAPTURED) {
            $success = 1;
        }

        $response = array_merge(
            [
                self::RESULT_CODE => $success,
            ],
            $data
        );

        return $response;
    }
}
