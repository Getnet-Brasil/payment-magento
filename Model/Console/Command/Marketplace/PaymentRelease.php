<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\Console\Command\Marketplace;

use Exception;
use Getnet\PaymentMagento\Gateway\Config\Config as GetnetConfig;
use Getnet\PaymentMagento\Model\Console\Command\AbstractModel;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory as TransactionSearch;
use Magento\Payment\Model\Method\Logger;

/**
 * Payment Release - release the payment amount to the sub seller.
 */
class PaymentRelease extends AbstractModel
{
    public const RELEASE_PAYMENT_DATE = 'release_payment_date';
    public const SUBSELLER_ID = 'subseller_id';
    public const ORDER_ITEM_RELEASE = 'order_item_release';
    public const ORDER_ITEM_RELEASE_ID = 'id';
    public const ORDER_ITEM_RELEASE_AMOUNT = 'amount';

    /**
     * @var State
     */
    protected $state;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var GetnetConfig
     */
    protected $getnetConfig;

    /**
     * @var TransactionSearch
     */
    protected $transactionSearch;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @param State                        $state
     * @param Logger                       $logger
     * @param GetnetConfig                 $getnetConfig
     * @param TransactionSearch            $transactionSearch
     * @param Json                         $json
     * @param ZendClientFactory            $httpClientFactory
     */
    public function __construct(
        State $state,
        Logger $logger,
        GetnetConfig $getnetConfig,
        TransactionSearch $transactionSearch,
        Json $json,
        ZendClientFactory $httpClientFactory
    ) {
        parent::__construct(
            $logger
        );
        $this->state = $state;
        $this->getnetConfig = $getnetConfig;
        $this->transactionSearch = $transactionSearch;
        $this->json = $json;
        $this->httpClientFactory = $httpClientFactory;
    }

    /**
     * Command Preference.
     *
     * @param int $orderId
     * @param int|null $subSellerId
     * @param string $date
     *
     * @return void
     */
    public function create(
        int $orderId,
        string $date,
        int $subSellerId = null
    ) {
        $this->writeln('Init Payment Release');
        $this->createPaymentRelease($orderId, $date, $subSellerId);
        $this->writeln(__('Finished'));
    }

    /**
     * Create Sub Seller.
     *
     * @param int $orderId
     * @param int|null $subSellerId
     * @param string $date
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * phpcs:disable Generic.Metrics.NestingLevel
     */
    protected function createPaymentRelease(
        int $orderId,
        string $date,
        int $subSellerId = null
    ) {
        try {
            $transaction = $this->transactionSearch->create()->addOrderIdFilter($orderId)->getFirstItem();
        } catch (LocalizedException $exc) {
            $this->writeln('<error>'.$exc->getMessage().'</error>');
            return;
        }
        $transactionId = $transaction->getTxnId();

        if (!$transactionId) {
            $messageInfo = __(
                'Não foi possível obter a transação do pedido'
            );
            $this->writeln(sprintf('<error>%s</error>', $messageInfo));
            return;
        }

        $sellersItems = $transaction->getOrder()->getPayment()->getAdditionalInformation('marketplace');
        $sellersItems = $this->json->unserialize($sellersItems);

        foreach ($sellersItems as $sellerId => $items) {
            $subSellersInPayment[] = $sellerId;
            foreach ($items as $item) {
                if ((int)$item['amount']) {
                    $orderItems[$sellerId][] = [
                        'id'     => $item['id'],
                        'amount' => $item['amount'],
                    ];
                }
            }
        }

        if ($subSellerId) {
            $data = [
                self::RELEASE_PAYMENT_DATE => $date,
                self::SUBSELLER_ID => $subSellerId,
                self::ORDER_ITEM_RELEASE => $orderItems[$subSellerId],
            ];
            $this->writeln($transactionId);
            $messageInfo = __(
                'Fazendo a liberação para o vendedor %1, na data %2',
                $subSellerId,
                $date
            );
            $this->writeln(sprintf('<info>%s</info>', $messageInfo));
            $response = $this->sendData($transactionId, $data);
            if ($response->getSuccess()) {
                $messageInfo = __(
                    'Liberação solicitada com sucesso'
                );
                $this->writeln(sprintf('<info>%s</info>', $messageInfo));
                return;
            }
    
            if ($response->getMessage()) {
                $this->writeln(sprintf('<error>%s</error>', $response->getMessage()));
                return;
            }
        }

        if (!$subSellerId) {
            foreach ($subSellersInPayment as $subSellerId) {
                $data = [
                    self::RELEASE_PAYMENT_DATE => $date,
                    self::SUBSELLER_ID => $subSellerId,
                    self::ORDER_ITEM_RELEASE => $orderItems[$subSellerId],
                ];
                $this->writeln($transactionId);
                $messageInfo = __(
                    'Fazendo a liberação para o vendedor %1, na data %2',
                    $subSellerId,
                    $date
                );
                $this->writeln(sprintf('<info>%s</info>', $messageInfo));
                $response = $this->sendData($transactionId, $data);
                if ($response->getSuccess()) {
                    $messageInfo = __(
                        'Liberação solicitada com sucesso'
                    );
                    $this->writeln(sprintf('<info>%s</info>', $messageInfo));
                    return;
                }
        
                if ($response->getMessage()) {
                    $this->writeln(sprintf('<error>%s</error>', $response->getMessage()));
                    return;
                }
            }
        }

    }

    /**
     * Send Data.
     *
     * @param string $transactionId
     * @param string $data
     *
     * @return \Magento\Framework\DataObject
     */
    protected function sendData(
        string $transactionId,
        array $data
    ): \Magento\Framework\DataObject {
        $uri = $this->getnetConfig->getApiUrl();
        $bearer = $this->getnetConfig->getMerchantGatewayOauth();
        $client = $this->httpClientFactory->create();
        $uri = $uri.'/v1/marketplace/payments/'.$transactionId.'/release';
        $client->setUri($uri);
        $client->setHeaders('Authorization', 'Bearer '.$bearer);
        $client->setConfig(['maxredirects' => 0, 'timeout' => 40]);
        $client->setRawData($this->json->serialize($data), 'application/json');
        $client->setMethod(ZendClient::POST);
        $getnetData = new \Magento\Framework\DataObject();

        try {
            $result = $client->request()->getBody();
            $response = $this->json->unserialize($result);
            
            $this->logger->debug([
                'url'      => $uri,
                'send'     => $this->json->serialize($data),
                'response' => $this->json->serialize($response),
            ]);
            
            $getnetData->setData($response);

            return $getnetData;
        } catch (Exception $e) {
            $this->logger->debug([
                'error' => $e->getMessage()
            ]);

            return $getnetData->setDetails($e->getMessage());
        }
    }
}
