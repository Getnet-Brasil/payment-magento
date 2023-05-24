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
use Laminas\Http\ClientFactory;
use Laminas\Http\Request;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory as TransactionSearch;
use Magento\Sales\Model\Service\OrderService;

/**
 * Payment Release - release the payment amount to the sub seller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var ClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var OrderInterfaceFactory
     */
    protected $orderFactory;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @param State                 $state
     * @param Logger                $logger
     * @param GetnetConfig          $getnetConfig
     * @param TransactionSearch     $transactionSearch
     * @param Json                  $json
     * @param ClientFactory         $httpClientFactory
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderService          $orderService
     */
    public function __construct(
        State $state,
        Logger $logger,
        GetnetConfig $getnetConfig,
        TransactionSearch $transactionSearch,
        Json $json,
        ClientFactory $httpClientFactory,
        OrderInterfaceFactory $orderFactory,
        OrderService $orderService
    ) {
        parent::__construct(
            $logger
        );
        $this->state = $state;
        $this->getnetConfig = $getnetConfig;
        $this->transactionSearch = $transactionSearch;
        $this->json = $json;
        $this->httpClientFactory = $httpClientFactory;
        $this->orderFactory = $orderFactory;
        $this->orderService = $orderService;
    }

    /**
     * Command Preference.
     *
     * @param int         $orderId
     * @param string      $date
     * @param string|null $subSellerId
     *
     * @return void
     */
    public function create(
        int $orderId,
        string $date,
        string $subSellerId = null
    ) {
        $this->writeln('Init Payment Release');
        $this->createPaymentRelease($orderId, $date, $subSellerId);
        $this->writeln(__('Finished'));
    }

    /**
     * Create Sub Seller.
     *
     * @param int         $orderId
     * @param string      $date
     * @param string|null $subSellerId
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function createPaymentRelease(
        int $orderId,
        string $date,
        string $subSellerId = null
    ) {
        try {
            $transaction = $this->transactionSearch->create()->addOrderIdFilter($orderId)->getFirstItem();
            $transactionId = $transaction->getTxnId();
        } catch (LocalizedException $exc) {
            $this->writeln('<error>'.$exc->getMessage().'</error>');

            return;
        }

        $orderItems = [];
        $subSellersInPayment = [];

        if (!$transactionId) {
            $messageInfo = __(
                'Unable to get order transaction'
            );
            $this->writeln(sprintf('<error>%s</error>', $messageInfo));

            return;
        }

        $sellersItems = $transaction->getOrder()->getPayment()->getAdditionalInformation('marketplace');

        if (!$sellersItems) {
            $messageInfo = __(
                'Unable to get order transaction marketplace'
            );
            $this->writeln(sprintf('<error>%s</error>', $messageInfo));

            return;
        }

        $sellersItems = $this->json->unserialize($sellersItems);

        foreach ($sellersItems as $sellerId => $items) {
            $subSellersInPayment[] = $sellerId;
            foreach ($items as $item) {
                if ((int) $item['amount']) {
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
                self::SUBSELLER_ID         => $subSellerId,
                self::ORDER_ITEM_RELEASE   => $orderItems[$subSellerId],
            ];
            $messageInfo = __(
                'Releasing payment from seller %1, for date of %2',
                $subSellerId,
                $date
            );
            $response = $this->sendData($transactionId, $data);
            $this->setMessages($response, $messageInfo, $orderId);

            return $response;
        }

        if (!$subSellerId) {
            foreach ($subSellersInPayment as $subSellerId) {
                $data = [
                    self::RELEASE_PAYMENT_DATE => $date,
                    self::SUBSELLER_ID         => $subSellerId,
                    self::ORDER_ITEM_RELEASE   => $orderItems[$subSellerId],
                ];
                $messageInfo = __(
                    'Releasing payment from seller %1, for date of %2',
                    $subSellerId,
                    $date
                );
                $response = $this->sendData($transactionId, $data);
                $this->setMessages($response, $messageInfo, $orderId);

                return $response;
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
     *
     * @SuppressWarnings(PHPMD)
     */
    public function sendData(
        string $transactionId,
        array $data
    ): \Magento\Framework\DataObject {
        $uri = $this->getnetConfig->getApiUrl();
        $bearer = $this->getnetConfig->getMerchantGatewayOauth(0);
        $client = $this->httpClientFactory->create();
        $uri = $uri.'v1/marketplace/payments/'.$transactionId.'/release';
        $client->setUri($uri);
        $client->setHeaders('Authorization', 'Bearer '.$bearer);
        $client->setOptions(['maxredirects' => 0, 'timeout' => 40]);
        $client->setRawBody($this->json->serialize($data), 'application/json');
        $client->setMethod(Request::METHOD_POST);
        $getnetData = new \Magento\Framework\DataObject();

        try {
            $result = $client->send()->getBody();
            $response = $this->json->unserialize($result);

            $this->logger->debug([
                'url'      => $uri,
                'send'     => $this->json->serialize($data),
                'response' => $this->json->serialize($response),
            ]);

            $getnetData->setData($response);
        } catch (Exception $e) {
            $this->logger->debug([
                'error' => $e->getMessage(),
            ]);

            $getnetData->getMessage('Connection Error');
            $getnetData->setDetails(
                [
                    'error_code'  => 401,
                    'description' => $e->getMessage(),
                ]
            );
        }

        return $getnetData;
    }

    /**
     * Set Messages.
     *
     * @param \Magento\Framework\DataObject $response
     * @param Phrase                        $messageInfo
     * @param int                           $orderId
     *
     * @return void;
     */
    public function setMessages(
        \Magento\Framework\DataObject $response,
        Phrase $messageInfo,
        int $orderId
    ) {
        $this->writeln(sprintf('<info>%s</info>', $messageInfo));
        if ($response->getSuccess()) {
            $messageDone = __(
                'Payment release requested successfully'
            );
            $this->writeln(sprintf('<info>%s</info>', $messageDone));
            $this->addReleaseComment($messageInfo, $orderId);

            return $messageDone;
        }

        if ($response->getMessage()) {
            $this->writeln(sprintf('<error>%s</error>', $response->getMessage()));
            foreach ($response->getDetails() as $message) {
                $messageInfo = __(
                    'Error: %1, description: %2',
                    $message['error_code'],
                    $message['description']
                );
                $this->writeln(sprintf('<error>%s</error>', $messageInfo));
            }

            return $messageInfo;
        }
    }

    /**
     * Add Release Comment.
     *
     * @param Phrase $messageInfo
     * @param int    $orderId
     *
     * @return void
     */
    public function addReleaseComment(
        Phrase $messageInfo,
        int $orderId
    ) {
        /** @var OrderInterfaceFactory $order */
        $order = $this->orderFactory->create()->load($orderId);
        $history = $order->addStatusHistoryComment($messageInfo, $order->getStatus());
        $history->setIsVisibleOnFront(false);
        $history->setIsCustomerNotified(false);
        $this->orderService->addComment($orderId, $history);
    }
}
