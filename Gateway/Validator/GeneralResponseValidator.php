<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Validator;

use Getnet\PaymentMagento\Gateway\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

/**
 * Class GeneralResponseValidator - Handles return from gateway.
 */
class GeneralResponseValidator extends AbstractValidator
{
    /**
     * The result code.
     */
    public const RESULT_CODE_SUCCESS = '1';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param SubjectReader          $subjectReader
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
    }

    /**
     * Validate.
     *
     * @param array $validationSubject
     *
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = $this->subjectReader->readResponse($validationSubject);
        $isValid = $response['RESULT_CODE'];
        $errorCodes = [];
        $errorMessages = [];
        if (!$isValid) {
            if (isset($response['details'])) {
                foreach ($response['details'] as $message) {
                    if (isset($message['antifraud'])) {
                        $errorCodes[] = $message['antifraud']['status_code'];
                        $errorMessages[] = $message['antifraud']['description'];
                    }
                    if (isset($message['error_code'])) {
                        $errorCodes[] = $message['error_code'];
                        $errorMessages[] = $message['description_detail'];
                        if ($message['error_code'] === 'PAYMENTS-402') {
                            $detailCode = preg_replace('/[^0-9]/', '', $message['description_detail']);
                            $errorCodes[] = $message['error_code'].'-'.$detailCode;
                            $errorMessages[] = $message['description_detail'];
                        }
                    }
                }
            }
        }

        return $this->createResult($isValid, $errorMessages, $errorCodes);
    }
}
