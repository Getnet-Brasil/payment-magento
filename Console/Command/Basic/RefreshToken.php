<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Console\Command\Basic;

use Getnet\PaymentMagento\Model\Console\Command\Basic\Refresh;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshToken extends Command
{
    /**
     * Store Id.
     */
    public const STORE_ID = 'store_id';

    /**
     * @var Refresh
     */
    protected $refresh;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param State   $state
     * @param Refresh $refresh
     */
    public function __construct(
        State $state,
        refresh $refresh
    ) {
        $this->state = $state;
        $this->refresh = $refresh;
        parent::__construct();
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $this->refresh->setOutput($output);

        $storeId = $input->getArgument(self::STORE_ID);
        $this->refresh->newToken($storeId);

        return 1;
    }

    /**
     * Configure.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('getnet:basic:referesh_token');
        $this->setDescription('Manually Referesh Token');
        $this->setDefinition(
            [new InputArgument(self::STORE_ID, InputArgument::OPTIONAL, 'Store Id')]
        );
        parent::configure();
    }
}
