<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Response;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigBoleto;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Txn Id Boleto Handler - Handles reading responses for Boleto payment.
 */
class TxnIdBoletoHandler implements HandlerInterface
{
    /**
     * Boleto Line Code - Payment Addtional Information.
     */
    public const PAYMENT_INFO_BOLETO_LINE_CODE = 'boleto_line_code';

    /**
     * Boleto PDF Href - Payment Addtional Information.
     */
    public const PAYMENT_INFO_BOLETO_PDF_HREF = 'boleto_pdf_href';

    /**
     * Boleto Expiration Date - Payment Addtional Information.
     */
    public const PAYMENT_INFO_BOLETO_EXPIRATION_DATE = 'boleto_expiration_date';

    /**
     * Boleto Our Number - Payment Addtional Information.
     */
    public const PAYMENT_INFO_BOLETO_OUR_NUMBER = 'boleto_our_number';

    /**
     * Boleto Document Number - Payment Addtional Information.
     */
    public const PAYMENT_INFO_BOLETO_DOCUMENT_NUMBER = 'boleto_document_number';

    /**
     * Response Pay Boleto - Block name.
     */
    public const RESPONSE_BOLETO = 'boleto';

    /**
     * Response Pay Boleto Id - Block name.
     */
    public const RESPONSE_BOLETO_ID = 'boleto_id';

    /**
     * Response Pay Boleto Typeful Line - Block name.
     */
    public const RESPONSE_BOLETO_TYPEFUL_LINE = 'typeful_line';

    /**
     * Response Pay Boleto Expiration Date - Block name.
     */
    public const RESPONSE_BOLETO_EXPIRATION_DATE = 'expiration_date';

    /**
     * Response Pay Boleto Our Number - Block name.
     */
    public const RESPONSE_BOLETO_OUR_NUMBER = 'our_number';

    /**
     * Response Pay Boleto Document Number - Block name.
     */
    public const RESPONSE_BOLETO_DOCUMENT_NUMBER = 'document_number';

    /**
     * Response Pay Boleto Links Number - Block name.
     */
    public const RESPONSE_BOLETO_LINKS = '_links';

    /**
     * Response Pay Boleto Links HREF - Block name.
     */
    public const RESPONSE_BOLETO_LINKS_HREF = 'href';

    /**
     * Response Pay Boleto Links Rel - Block name.
     */
    public const RESPONSE_BOLETO_LINKS_REL = 'rel';

    /**
     * Response Pay Boleto Links Rel PDF - Block name.
     */
    public const RESPONSE_BOLETO_LINKS_REL_PDF = 'boleto_pdf';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConfigBoleto
     */
    private $configBoleto;

    /**
     * @param Config       $config
     * @param ConfigBoleto $configBoleto
     */
    public function __construct(
        Config $config,
        ConfigBoleto $configBoleto
    ) {
        $this->config = $config;
        $this->configBoleto = $configBoleto;
    }

    /**
     * Handles.
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();
        $payBoleto = $response[self::RESPONSE_BOLETO];

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_BOLETO_LINE_CODE,
            $payBoleto[self::RESPONSE_BOLETO_TYPEFUL_LINE]
        );

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_BOLETO_EXPIRATION_DATE,
            $payBoleto[self::RESPONSE_BOLETO_EXPIRATION_DATE]
        );

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_BOLETO_OUR_NUMBER,
            $payBoleto[self::RESPONSE_BOLETO_DOCUMENT_NUMBER]
        );

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_BOLETO_DOCUMENT_NUMBER,
            $payBoleto[self::RESPONSE_BOLETO_OUR_NUMBER]
        );

        $links = $payBoleto[self::RESPONSE_BOLETO_LINKS];
        foreach ($links as $link) {
            if ($link[self::RESPONSE_BOLETO_LINKS_REL] === self::RESPONSE_BOLETO_LINKS_REL_PDF) {
                $relativeLinkToPDF = $link[self::RESPONSE_BOLETO_LINKS_HREF];
            }
        }

        $linkToPDF = $this->configBoleto->getFormattedLinkBoleto($relativeLinkToPDF);
        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_BOLETO_PDF_HREF,
            $linkToPDF
        );
        $transactionId = $payBoleto[self::RESPONSE_BOLETO_ID];
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionPending(1);
        $payment->setIsTransactionClosed(false);
        $payment->setAuthorizationTransaction($transactionId);
        $payment->addTransaction(Transaction::TYPE_AUTH);

        $order = $payment->getOrder();
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $order->setStatus('pending');
        $comment = __('Awaiting payment of the boleto.');
        $order->addStatusHistoryComment($comment, $payment->getOrder()->getStatus());
    }
}
