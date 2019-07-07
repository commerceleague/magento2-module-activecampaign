<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model;

use CommerceLeague\ActiveCampaign\Api\Data\AbandonedInterface;
use CommerceLeague\ActiveCampaign\Model\AbandonedRepository;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Abandoned as AbandonedResource;
use CommerceLeague\ActiveCampaign\Model\AbandonedFactory as AbandonedFactory;
use CommerceLeague\ActiveCampaign\Model\Abandoned;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbandonedRepositoryTest extends TestCase
{
    /**
     * @var MockObject|AbandonedResource
     */
    protected $abandonedResource;

    /**
     * @var MockObject|AbandonedFactory
     */
    protected $abandonedFactory;

    /**
     * @var MockObject|Abandoned
     */
    protected $abandoned;

    /**
     * @var AbandonedRepository
     */
    protected $abandonedRepository;

    protected function setUp()
    {
        $this->abandonedResource = $this->getMockBuilder(AbandonedResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->abandonedFactory = $this->getMockBuilder(AbandonedFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->abandoned = $this->getMockBuilder(Abandoned::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->abandonedFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->abandoned);

        $this->abandonedRepository = new AbandonedRepository(
            $this->abandonedResource,
            $this->abandonedFactory
        );
    }

    public function testSaveThrowsException()
    {
        $this->abandonedResource->expects($this->once())
            ->method('save')
            ->with($this->abandoned)
            ->willThrowException(new \Exception('an exception message'));

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('an exception message');

        $this->abandonedRepository->save($this->abandoned);
    }

    public function testSave()
    {
        $this->abandonedResource->expects($this->once())
            ->method('save')
            ->with($this->abandoned)
            ->willReturnSelf();

        $this->assertSame($this->abandoned, $this->abandonedRepository->save($this->abandoned));
    }

    public function testGetById()
    {
        $entityId = 123;

        $this->abandonedResource->expects($this->once())
            ->method('load')
            ->with($this->abandoned, $entityId)
            ->willReturn($this->abandoned);

        $this->assertSame($this->abandoned, $this->abandonedRepository->getById($entityId));
    }

    public function testGetByQuoteId()
    {
        $quoteId = 123;

        $this->abandonedResource->expects($this->once())
            ->method('load')
            ->with($this->abandoned, $quoteId, AbandonedInterface::QUOTE_ID)
            ->willReturn($this->abandoned);

        $this->assertSame($this->abandoned, $this->abandonedRepository->getByQuoteId($quoteId));
    }

    public function testGetOrCreateByQuoteIdWithKnownQuote()
    {
        $quoteId = 123;

        $this->abandonedResource->expects($this->once())
            ->method('load')
            ->with($this->abandoned, $quoteId, AbandonedInterface::QUOTE_ID)
            ->willReturn($this->abandoned);

        $this->abandoned->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $this->abandoned->expects($this->never())
            ->method('setQuoteId');

        $this->assertSame($this->abandoned, $this->abandonedRepository->getOrCreateByQuoteId($quoteId));
    }

    public function testGetOrCreateByQuoteId()
    {
        $quoteId = 123;

        $this->abandonedResource->expects($this->once())
            ->method('load')
            ->with($this->abandoned, $quoteId, AbandonedInterface::QUOTE_ID)
            ->willReturn($this->abandoned);

        $this->abandoned->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->abandoned->expects($this->once())
            ->method('setQuoteId')
            ->with($quoteId)
            ->willReturnSelf();

        $this->abandonedResource->expects($this->once())
            ->method('save')
            ->with($this->abandoned)
            ->willReturn($this->abandoned);

        $this->assertSame($this->abandoned, $this->abandonedRepository->getOrCreateByQuoteId($quoteId));
    }

    public function testDeleteThrowsException()
    {
        $this->abandonedResource->expects($this->once())
            ->method('delete')
            ->with($this->abandoned)
            ->willThrowException(new \Exception('an exception message'));

        $this->expectException(CouldNotDeleteException::class);
        $this->expectExceptionMessage('an exception message');

        $this->abandonedRepository->delete($this->abandoned);
    }

    public function testDelete()
    {
        $this->abandonedResource->expects($this->once())
            ->method('delete')
            ->with($this->abandoned)
            ->willReturnSelf();

        $this->assertTrue($this->abandonedRepository->delete($this->abandoned));
    }


    public function testDeleteByIdThrowsException()
    {
        $entityId = 123;

        $this->abandoned->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->abandonedResource->expects($this->once())
            ->method('load')
            ->with($this->abandoned, $entityId)
            ->willReturn($this->abandoned);

        $this->abandonedResource->expects($this->never())
            ->method('delete');

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('The Abandoned Cart with the "123" ID doesn\'t exist');

        $this->abandonedRepository->deleteById($entityId);
    }

    public function testDeleteById()
    {
        $entityId = 123;

        $this->abandoned->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $this->abandonedResource->expects($this->once())
            ->method('load')
            ->with($this->abandoned, $entityId)
            ->willReturn($this->abandoned);

        $this->abandonedResource->expects($this->once())
            ->method('delete')
            ->with($this->abandoned)
            ->willReturnSelf();

        $this->assertTrue($this->abandonedRepository->deleteById($entityId));
    }
}
