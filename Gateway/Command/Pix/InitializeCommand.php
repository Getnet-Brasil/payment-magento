<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Command\Pix;

use Getnet\PaymentMagento\Gateway\Config\ConfigPix;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use Magento\Payment\Gateway\CommandInterface;

/**
 * Route the Pix "initialize" to the QR code or the biometric command.
 *
 * The flow chosen at checkout is re-validated here: if biometrics is disabled or the store
 * is not on the Global API, the order always falls back to the QR code flow.
 */
class InitializeCommand implements CommandInterface
{
    /**
     * @var CommandInterface
     */
    protected $qrCodeCommand;

    /**
     * @var CommandInterface
     */
    protected $biometricCommand;

    /**
     * @var ConfigPix
     */
    protected $configPix;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @param CommandInterface $qrCodeCommand
     * @param CommandInterface $biometricCommand
     * @param ConfigPix        $configPix
     * @param SubjectReader    $subjectReader
     */
    public function __construct(
        CommandInterface $qrCodeCommand,
        CommandInterface $biometricCommand,
        ConfigPix $configPix,
        SubjectReader $subjectReader
    ) {
        $this->qrCodeCommand = $qrCodeCommand;
        $this->biometricCommand = $biometricCommand;
        $this->configPix = $configPix;
        $this->subjectReader = $subjectReader;
    }

    /**
     * Execute.
     *
     * @param array $commandSubject
     *
     * @return \Magento\Payment\Gateway\Command\ResultInterface|null
     */
    public function execute(array $commandSubject)
    {
        $payment = $this->subjectReader->readPayment($commandSubject)->getPayment();
        $storeId = $payment->getOrder()->getStoreId();

        $useBiometric = $payment->getAdditionalInformation(ConfigPix::MODE) === ConfigPix::MODE_BIOMETRIC
            && $this->configPix->isBiometricActive($storeId);

        $payment->setAdditionalInformation(
            ConfigPix::MODE,
            $useBiometric ? ConfigPix::MODE_BIOMETRIC : ConfigPix::MODE_QRCODE
        );

        return $useBiometric
            ? $this->biometricCommand->execute($commandSubject)
            : $this->qrCodeCommand->execute($commandSubject);
    }
}
