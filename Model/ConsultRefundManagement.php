<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model;

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
     * NumberTokenManagement constructor.
     *
     * @param ApiManagement $api
     */
    public function __construct(
        ApiManagement $api
    ) {
        $this->api = $api;
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
        $path = 'v1/payments/cancel/request';
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
