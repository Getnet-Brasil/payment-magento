<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client\Pix;

use Getnet\PaymentMagento\Gateway\Http\Api;
use Getnet\PaymentMagento\Gateway\Http\EndpointResolver;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Create a Biometric Pix charge (Global API only).
 */
class BiometricCreateClient implements ClientInterface
{
    public const RESULT_CODE = 'RESULT_CODE';

    public const EXT_ORD_ID = 'EXT_ORD_ID';

    public const STATUS_WAITING = 'WAITING';

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
     * A 201 response may still carry a business status (DENIED/ERROR), so only
     * WAITING with a payment_id counts as success.
     *
     * @param TransferInterface $transferObject
     *
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();

        $responseBody = $this->api->sendPostRequest(
            $transferObject,
            $this->endpointResolver->resolve(
                EndpointResolver::PIX_BIOMETRIC_CREATE,
                $request['store_id'] ?? null
            ),
            $request
        );

        $paymentId = $responseBody['payment_id'] ?? null;
        $status = $responseBody['status'] ?? null;
        $success = $paymentId
            && $status === self::STATUS_WAITING
            && !empty($responseBody['additional_data']['jsr_url']);

        if (!$success && $status && empty($responseBody['details'])) {
            // Business-level refusal: expose it the same way HTTP errors are exposed to the validator
            $responseBody['details'] = [
                [
                    'error_code'  => 'PIX-BIOMETRIC-'.$status,
                    'description' => $responseBody['description'] ?? $status,
                ],
            ];
        }

        return array_merge(
            $responseBody,
            [
                self::RESULT_CODE => $success ? 1 : 0,
                self::EXT_ORD_ID  => $paymentId,
            ]
        );
    }
}
