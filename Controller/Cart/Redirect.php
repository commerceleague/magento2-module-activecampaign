<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Cart;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Class Redirect
 */
class Redirect extends Action implements HttpGetActionInterface
{
    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        private readonly CustomerSession $customerSession
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute(): void
    {
        $url = $this->_url->getUrl('checkout/cart');

        if (!$this->customerSession->isLoggedIn()) {
            $this->messageManager->addSuccessMessage(__('Please sign in to see your cart.'));
            $this->customerSession->setBeforeAuthUrl($url);
            $url = $this->_url->getUrl('customer/account/login');
        }

        $this->_redirect($url);
    }
}
