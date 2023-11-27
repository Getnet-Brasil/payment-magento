<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request;

use Getnet\PaymentMagento\Gateway\Config\ConfigPix;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Pix Expiration Data Request - Time of expiration.
 */
class PixExpirationDataRequest implements BuilderInterface
{
    /**
     * Store Id block name.
     */
    public const PIX_EXPIRATION = 'pix_expiration';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var ConfigPix
     */
    protected $config;

    /**
     * @param SubjectReader $subjectReader
     * @param ConfigPix     $config
     */
    public function __construct(
        SubjectReader $subjectReader,
        ConfigPix $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject): array
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }
        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();
        $expiration = $this->config->getExpiration($storeId);

        return [
            self::PIX_EXPIRATION  => $expiration ?: 180,
        ];
    }
}
