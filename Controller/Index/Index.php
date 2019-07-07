<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\MessageQueue\ConsumerFactory;

/**
 * @codeCoverageIgnore
 */
class Index extends Action
{
    /**
     * @var ConsumerFactory
     */
    private $consumerFactory;

    /**
     * @param Context $context
     * @param ConsumerFactory $consumerFactory
     */
    public function __construct(
        Context $context,
        ConsumerFactory $consumerFactory
    ) {
        parent::__construct($context);
        $this->consumerFactory = $consumerFactory;
    }


    /**
     * @inheritDoc
     */
    public function execute()
    {
        $consumer = $this->consumerFactory->get('activeCampaignSalesExportOrder');
        $consumer->process();

        die('now in here');
        // TODO: Implement execute() method.
    }

}
