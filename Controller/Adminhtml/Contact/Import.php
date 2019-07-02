<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml\Contact;

use CommerceLeague\ActiveCampaign\Controller\Adminhtml\AbstractContact;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\ImportMessageBuilder;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect as ResultRedirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class Import
 */
class Import extends AbstractContact implements HttpPostActionInterface
{
    /**
     * @var ImportMessageBuilder
     */
    private $importMessageBuilder;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param Action\Context $context
     * @param ImportMessageBuilder $importMessageBuilder
     * @param PublisherInterface $publisher
     */
    public function __construct(
        Action\Context $context,
        ImportMessageBuilder $importMessageBuilder,
        PublisherInterface $publisher
    ) {
        $this->importMessageBuilder = $importMessageBuilder;
        $this->publisher = $publisher;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->publisher->publish(
            Topics::CONTACT_IMPORT,
            $this->importMessageBuilder->build()
        );

        $this->messageManager->addNoticeMessage(
            __('Message is added to queue, contacts will be imported soon')
        );

        /** @var ResultRedirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/');
    }
}

