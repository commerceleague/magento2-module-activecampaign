<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

/**
 * Class ImportMessageBuilder
 */
class ImportMessageBuilder
{
    /**
     * @var ImportMessageFactory
     */
    private $importMessageFactory;

    /**
     * @param ImportMessageFactory $importMessageFactory
     */
    public function __construct(ImportMessageFactory $importMessageFactory)
    {
        $this->importMessageFactory = $importMessageFactory;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return ImportMessage
     */
    public function build(int $limit = 100, int $offset = 0): ImportMessage
    {
        /** @var ImportMessage $message */
        $message = $this->importMessageFactory->create();

        $message->setLimit($limit)
            ->setOffset($offset);

        return $message;
    }

    /**
     * @param ImportMessage $lastMessage
     * @return ImportMessage
     */
    public function buildNextMessage(ImportMessage $lastMessage): ImportMessage
    {
        return $this->build(
            $lastMessage->getLimit(),
            $lastMessage->getLimit() + $lastMessage->getOffset()
        );
    }
}
