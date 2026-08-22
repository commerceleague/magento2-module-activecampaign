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
    protected function convertToCent(float $amount): int
    {
        return (int)($amount * 100);
    }

    /**
     * @throws \Exception
     */
    protected function formatDateTime(string $date): string
    {
        return (new \DateTime($date))->format(\DateTime::W3C);
    }

    protected function buildCategoryNames(\Magento\Catalog\Model\Product $product): string
    {
        try {
            /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection */
            $categoryCollection = $product->getCategoryCollection();

            $names = [];
            foreach ($categoryCollection->addAttributeToSelect('name') as $category) {
                $names[] = (string)$category->getName();
            }

            return implode(', ', array_filter($names, static fn (string $name): bool => $name !== ''));
        } catch (\Throwable $e) {
            return '';
        }
    }
}
