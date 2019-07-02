<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Contact;

/**
 * Class ImportMessage
 */
class ImportMessage
{
    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $offset;

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return ImportMessage
     */
    public function setLimit(int $limit): ImportMessage
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return ImportMessage
     */
    public function setOffset(int $offset): ImportMessage
    {
        $this->offset = $offset;
        return $this;
    }
}
