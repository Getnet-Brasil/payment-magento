<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model;

use Getnet\PaymentMagento\Gateway\Http\EndpointResolver;

/**
 * Class Consult Refund Management - refund data.
 */
class ConsultRefundManagement
{
    /**
     * @var ApiManagement
     */
    private $api;

    /**
     * @var EndpointResolver
     */
    private $endpointResolver;

    /**
     * NumberTokenManagement constructor.
     *
     * @param ApiManagement    $api
     * @param EndpointResolver $endpointResolver
     */
    public function __construct(
        ApiManagement $api,
        EndpointResolver $endpointResolver
    ) {
        $this->api = $api;
        $this->endpointResolver = $endpointResolver;
    }

    /**
     * Get Refund Data.
     *
     * @param int    $storeId
     * @param string $transactionId
     *
     * @return array
     */
    public function getRefundData($storeId, $transactionId)
    {
        $path = $this->endpointResolver->resolve(EndpointResolver::REFUND_CONSULT, $storeId);
        $request = [
            'store_id'          => $storeId,
            'cancel_custom_key' => $transactionId,
        ];

        $data = $this->api->sendGetByParam($path, $request);

        $response = [];
        if (!empty($data['status_processing_cancel_code'])) {
            $response = [
                'status_processing_cancel_code'     => $data['status_processing_cancel_code'],
                'status_processing_cancel_message'  => $data['status_processing_cancel_message'],
            ];
        }

        return $response;
    }
}
