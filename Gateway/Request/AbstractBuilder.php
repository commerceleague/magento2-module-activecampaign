<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway\Request;

/**
 * Class AbstractBuilder
 */
abstract class AbstractBuilder
{
    /**
     * @return int
     */
    protected function convertToCent(float $amount): int
    {
        return (int)($amount * 100);
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected  function formatDateTime(string $date): string
    {
        return (new \DateTime($date))->format(\DateTime::W3C);
    }
}
