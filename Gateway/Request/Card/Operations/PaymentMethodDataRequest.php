<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Card\Operations;

use InvalidArgumentException;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class External Payment Id - Payment Id structure.
 */
class PaymentMethodDataRequest implements BuilderInterface
{
    /**
     * @var string
     */
    public const PAYMENT_METHOD = 'payment_method';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @param SubjectReader $subjectReader
     * @param ConfigCc      $configCc
     */
    public function __construct(
        SubjectReader $subjectReader,
        ConfigCc $configCc
    ) {
        $this->subjectReader = $subjectReader;
        $this->configCc = $configCc;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
        || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();

        $method = 'CREDIT';

        if ($this->configCc->hasDelayed($storeId)) {
            $method = 'CREDIT_AUTHORIZATION';
        }

        return [
            self::PAYMENT_METHOD => $method,
        ];
    }
}
