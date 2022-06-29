<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Config;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ConfigPix - Returns form of payment configuration properties.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigPix extends PaymentConfig
{
    /**
     * @const string
     */
    public const METHOD = 'getnet_paymentmagento_pix';

    /**
     * @const string
     */
    public const ACTIVE = 'active';

    /**
     * @const string
     */
    public const TITLE = 'title';

    /**
     * @const string
     */
    public const INSTRUCTION_CHECKOUT = 'instruction_checkout';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var File
     */
    private $fileIo;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param File                 $fileIo
     * @param Filesystem           $filesystem
     * @param DirectoryList        $directoryList
     * @param Config               $config
     * @param string|null          $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        File $fileIo,
        Filesystem $filesystem,
        DirectoryList $directoryList,
        Config $config,
        $methodCode = null
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->fileIo = $fileIo;
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->config = $config;
    }

    /**
     * Get Payment configuration status.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isActive($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::ACTIVE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get title of payment.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getTitle($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::TITLE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Instruction - Checkoout.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getInstructionCheckout($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::INSTRUCTION_CHECKOUT),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Generate Image Qr Code.
     *
     * @param string $qrCode
     * @param string $transactionId
     *
     * @return string
     */
    public function generateImageQrCode($qrCode, $transactionId)
    {
        $fileName = null;
        if ($this->hasPathDir()) {
            $fileName = 'getnet/pix/'.$transactionId.'.png';
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $filePath = $this->getPathFile($mediaDirectory, $transactionId);
            $generate = $this->createImageQrCode($mediaDirectory, $filePath, $qrCode);
            if (!$generate) {
                return false;
            }
        }

        return $fileName;
    }

    /**
     * Get Path File.
     *
     * @param string $mediaDirectory
     * @param string $transactionId
     *
     * @return string
     */
    public function getPathFile($mediaDirectory, $transactionId): string
    {
        $filePath = $mediaDirectory->getAbsolutePath('getnet/pix/'.$transactionId.'.png');

        return $filePath;
    }

    /**
     * Create Image Qr Code.
     *
     * @param WriteInterface $writeDirectory
     * @param string         $filePath
     * @param string         $qrCode
     *
     * @throws FileSystemException
     *
     * @return bool
     */
    public function createImageQrCode(WriteInterface $writeDirectory, $filePath, $qrCode): bool
    {
        $qrCode = new QrCode($qrCode);
        $qrCode->setSize(150);
        $qrCode->setErrorCorrectionLevel('high');
        $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
        $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
        $qrCode->setLabelFontSize(16);
        $qrCode->setEncoding('UTF-8');
        $writer = new PngWriter();
        $pngData = $writer->writeString($qrCode);

        try {
            $stream = $writeDirectory->openFile($filePath, 'w+');
            $stream->lock();
            $stream->write($pngData);
            $stream->unlock();
            $stream->close();
        } catch (FileSystemException $ex) {
            new Phrase($ex->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Check or Create Dir.
     *
     * @return bool
     */
    public function hasPathDir(): bool
    {
        $pixPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('/getnet/pix');

        return $this->fileIo->checkAndCreateFolder($pixPath);
    }
}
