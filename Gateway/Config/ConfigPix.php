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
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Phrase;
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
    public const EXPIRATION = 'expiration';

    /**
     * @const string
     */
    public const INSTRUCTION_CHECKOUT = 'instruction_checkout';

    /**
     * @const string
     */
    public const BIOMETRIC_ACTIVE = 'biometric_active';

    /**
     * @const string
     */
    public const BIOMETRIC_DEFAULT = 'biometric_default';

    /**
     * @const string
     */
    public const BIOMETRIC_EXPIRATION = 'biometric_expiration';

    /**
     * @const string
     */
    public const BIOMETRIC_INSTRUCTION = 'biometric_instruction';

    /**
     * @const string
     */
    public const BIOMETRIC_RETURN_URL = 'biometric_return_url';

    /**
     * @const string
     */
    public const BIOMETRIC_ALLOWED_HOSTS = 'biometric_allowed_hosts';

    /**
     * Payment additional information key holding the chosen Pix flow.
     */
    public const MODE = 'pix_mode';

    /**
     * @const string
     */
    public const MODE_QRCODE = 'qrcode';

    /**
     * @const string
     */
    public const MODE_BIOMETRIC = 'biometric';

    /**
     * Biometric expiration limits (seconds) accepted by the Global API.
     */
    public const BIOMETRIC_EXPIRATION_MIN = 60;

    public const BIOMETRIC_EXPIRATION_MAX = 1800;

    /**
     * Frontend route that receives the payer back from the biometric journey.
     */
    public const BIOMETRIC_RETURN_ROUTE = 'getnet/pixbiometric/return';

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
     * Get Expiration.
     *
     * @param int|null $storeId
     *
     * @return int|null
     */
    public function getExpiration($storeId = null): ?int
    {
        $pathPattern = 'payment/%s/%s';

        return (int) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is Pix with biometrics available (flag enabled and store on the Global API).
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isBiometricActive($storeId = null): bool
    {
        return $this->getPixValue(self::BIOMETRIC_ACTIVE, $storeId)
            && $this->config->getApiType($storeId) === Config::API_TYPE_GLOBAL;
    }

    /**
     * Pix flow preselected at checkout when biometrics is available.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getBiometricDefault($storeId = null): string
    {
        $mode = (string) $this->getPixValue(self::BIOMETRIC_DEFAULT, $storeId);

        return $mode === self::MODE_QRCODE ? self::MODE_QRCODE : self::MODE_BIOMETRIC;
    }

    /**
     * Biometric charge expiration in seconds, clamped to the API limits.
     *
     * @param int|null $storeId
     *
     * @return int
     */
    public function getBiometricExpiration($storeId = null): int
    {
        $expiration = (int) $this->getPixValue(self::BIOMETRIC_EXPIRATION, $storeId);

        if (!$expiration) {
            return 600;
        }

        return max(self::BIOMETRIC_EXPIRATION_MIN, min(self::BIOMETRIC_EXPIRATION_MAX, $expiration));
    }

    /**
     * Instruction displayed at checkout for the biometric flow.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getBiometricInstruction($storeId = null): ?string
    {
        $value = $this->getPixValue(self::BIOMETRIC_INSTRUCTION, $storeId);

        return $value === null ? null : (string) $value;
    }

    /**
     * Return URL override registered on the Open Finance merchant setup.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getBiometricReturnUrl($storeId = null): ?string
    {
        $value = trim((string) $this->getPixValue(self::BIOMETRIC_RETURN_URL, $storeId));

        return $value === '' ? null : $value;
    }

    /**
     * Hosts (domain suffixes) accepted for the biometric journey redirect.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getBiometricAllowedHosts($storeId = null): array
    {
        $value = (string) $this->getPixValue(self::BIOMETRIC_ALLOWED_HOSTS, $storeId);

        return array_values(array_filter(array_map('trim', explode(',', strtolower($value)))));
    }

    /**
     * Is the url an https url on one of the allowed journey hosts.
     *
     * @param string   $url
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isAllowedBiometricUrl(string $url, $storeId = null): bool
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $parts = parse_url($url);

        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return false;
        }

        $host = strtolower($parts['host']);

        foreach ($this->getBiometricAllowedHosts($storeId) as $allowed) {
            $allowed = ltrim($allowed, '.');
            if ($host === $allowed || substr($host, -strlen('.'.$allowed)) === '.'.$allowed) {
                return true;
            }
        }

        return false;
    }

    /**
     * Read a value from the Pix method config.
     *
     * @param string   $field
     * @param int|null $storeId
     *
     * @return mixed
     */
    private function getPixValue(string $field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            sprintf('payment/%s/%s', self::METHOD, $field),
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
     * @return string|false|null
     */
    public function generateImageQrCode($qrCode, $transactionId)
    {
        $fileName = null;
        if ($this->hasPathDir()) {
            $fileName = 'getnet/pix/'.$transactionId.'.svg';
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
        $filePath = $mediaDirectory->getAbsolutePath('getnet/pix/'.$transactionId.'.svg');

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
        $pixPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('/getnet/pix');

        return $this->fileIo->checkAndCreateFolder($pixPath);
    }
}
