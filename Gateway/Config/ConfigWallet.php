<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Config;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ConfigWallet - Returns form of payment configuration properties.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigWallet extends PaymentConfig
{
    /**
     * @const string
     */
    public const METHOD = 'getnet_paymentmagento_wallet';

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
    protected $scopeConfig;

    /**
     * @var File
     */
    protected $fileIo;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

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
            $fileName = 'getnet/wallet/'.$transactionId.'.svg';
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
        $filePath = $mediaDirectory->getAbsolutePath('getnet/wallet/'.$transactionId.'.svg');

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
        try {
            $stream = $writeDirectory->openFile($filePath, 'w+');
            $stream->lock();
            $renderer = new ImageRenderer(
                new RendererStyle(200),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $image = $writer->writeString($qrCode);
            $stream->write($image);
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
        $walletPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('/getnet/wallet');

        return $this->fileIo->checkAndCreateFolder($walletPath);
    }
}
