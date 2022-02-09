<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigGetpay;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Getpay Payment Data Request - Payment data structure for Getpay.
 */
class GetpayPaymentDataRequest implements BuilderInterface
{
    /**
     * Payment - Block Name.
     */
    public const PAYMENT = 'payment';

    /**
     * Payment Credit - Block Name.
     */
    public const PAYMENT_CREDIT = 'credit';

    /**
     * Payment Credit Enable - Block Name.
     */
    public const PAYMENT_CREDIT_ENABLE = 'enable';

    /**
     * Payment Credit Installments - Block Name.
     */
    public const PAYMENT_CREDIT_INSTALLMENTS = 'max_installments';

    /**
     * Payment Debit - Block Name.
     */
    public const PAYMENT_DEBIT = 'debit';

    /**
     * Payment Debit Enable - Block Name.
     */
    public const PAYMENT_DEBIT_ENABLE = 'enable';

    /**
     * Payment Debit Caixa Virtual Card - Block Name.
     */
    public const PAYMENT_DEBIT_CAIXA = 'caixa_virtual_card';

    /**
     * Payment Pix - Block Name.
     */
    public const PAYMENT_PIX = 'pix';

    /**
     * Payment Debit Enable - Block Name.
     */
    public const PAYMENT_PIX_ENABLE = 'enable';

    /**
     * Payment Qr Code - Block Name.
     */
    public const PAYMENT_QR_CODE = 'qr_code';

    /**
     * Payment Qr Code Enable - Block Name.
     */
    public const PAYMENT_QR_CODE_ENABLE = 'enable';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigGetpay
     */
    protected $configGetpay;

    /**
     * @param SubjectReader $subjectReader
     * @param Config        $config
     * @param ConfigGetpay  $configGetpay
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        ConfigGetpay $configGetpay
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->configGetpay = $configGetpay;
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

        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();
        $result = $this->getDataPaymetGetpay($storeId);

        return $result;
    }

    /**
     * Data for Getpay.
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getDataPaymetGetpay($storeId)
    {
        $instruction = [];

        $methods = $this->configGetpay->getAllowedMethods($storeId);

        $maxInstallments = $this->configGetpay->getMaxInstallments($storeId);

        if (in_array(self::PAYMENT_CREDIT, $methods)) {
            $instruction[self::PAYMENT][self::PAYMENT_CREDIT] = [
                self::PAYMENT_CREDIT_ENABLE       => true,
                self::PAYMENT_CREDIT_INSTALLMENTS => $maxInstallments,
            ];
        }

        if (in_array(self::PAYMENT_DEBIT, $methods)) {
            $instruction[self::PAYMENT][self::PAYMENT_DEBIT] = [
                self::PAYMENT_DEBIT_ENABLE => true,
            ];
        }

        if (in_array(self::PAYMENT_PIX, $methods)) {
            $instruction[self::PAYMENT][self::PAYMENT_PIX] = [
                self::PAYMENT_PIX_ENABLE => true,
            ];
        }

        if (in_array(self::PAYMENT_QR_CODE, $methods)) {
            $instruction[self::PAYMENT][self::PAYMENT_QR_CODE] = [
                self::PAYMENT_QR_CODE_ENABLE => true,
            ];
        }

        return $instruction;
    }
}
