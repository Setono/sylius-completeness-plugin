<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusCompletenessPlugin\EventListener\Doctrine\RubricChangeListener;
use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateAllProductsCompleteness;
use Setono\SyliusCompletenessPlugin\Message\Command\RefreshCompletenessRollups;
use Setono\SyliusCompletenessPlugin\Model\CompletenessContextSetting;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRule;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final class RubricChangeListenerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $entityManager;

    /** @var ObjectProphecy<UnitOfWork> */
    private ObjectProphecy $unitOfWork;

    /** @var ObjectProphecy<MessageBusInterface> */
    private ObjectProphecy $commandBus;

    /** @var ObjectProphecy<RubricVersionManagerInterface> */
    private ObjectProphecy $rubricVersionManager;

    protected function setUp(): void
    {
        $this->unitOfWork = $this->prophesize(UnitOfWork::class);
        $this->unitOfWork->getScheduledEntityInsertions()->willReturn([]);
        $this->unitOfWork->getScheduledEntityUpdates()->willReturn([]);
        $this->unitOfWork->getScheduledEntityDeletions()->willReturn([]);

        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->entityManager->getUnitOfWork()->willReturn($this->unitOfWork->reveal());
        $this->entityManager->getClassMetadata(Argument::type('string'))->will(
            static function (array $args): ClassMetadata {
                $class = $args[0];
                if (!is_string($class) || (!class_exists($class) && !interface_exists($class))) {
                    throw new \InvalidArgumentException('Expected a class name');
                }

                return new ClassMetadata($class);
            },
        );

        $this->commandBus = $this->prophesize(MessageBusInterface::class);
        $this->rubricVersionManager = $this->prophesize(RubricVersionManagerInterface::class);
    }

    private function flush(): void
    {
        $listener = new RubricChangeListener($this->commandBus->reveal(), $this->rubricVersionManager->reveal());

        $listener->onFlush(new OnFlushEventArgs($this->entityManager->reveal()));
        $listener->postFlush(new PostFlushEventArgs($this->entityManager->reveal()));
    }

    /**
     * @param class-string $messageClass
     */
    private function expectDispatch(string $messageClass): void
    {
        $this->commandBus->dispatch(Argument::that(
            static fn (Envelope $envelope): bool => is_a($envelope->getMessage(), $messageClass) &&
                [] !== $envelope->all(DispatchAfterCurrentBusStamp::class),
        ))->willReturnArgument(0)->shouldBeCalledTimes(1);
    }

    /**
     * @test
     */
    public function a_rule_change_bumps_the_rubric_version_and_recalculates_the_catalog(): void
    {
        $this->unitOfWork->getScheduledEntityInsertions()->willReturn([new CompletenessRule()]);

        $this->rubricVersionManager->bump()->willReturn(2)->shouldBeCalledTimes(1);
        $this->expectDispatch(RecalculateAllProductsCompleteness::class);

        $this->flush();
    }

    /**
     * @test
     */
    public function a_context_setting_change_bumps_the_version_and_refreshes_rollups_only(): void
    {
        $this->unitOfWork->getScheduledEntityUpdates()->willReturn([new CompletenessContextSetting()]);

        $this->rubricVersionManager->bump()->willReturn(2)->shouldBeCalledTimes(1);
        $this->expectDispatch(RefreshCompletenessRollups::class);

        $this->flush();
    }

    /**
     * @test
     */
    public function a_combined_change_triggers_the_full_recalculation(): void
    {
        $this->unitOfWork->getScheduledEntityDeletions()->willReturn([new CompletenessRule(), new CompletenessContextSetting()]);

        $this->rubricVersionManager->bump()->willReturn(2)->shouldBeCalledTimes(1);
        $this->expectDispatch(RecalculateAllProductsCompleteness::class);

        $this->flush();
    }

    /**
     * @test
     */
    public function an_unrelated_flush_does_nothing(): void
    {
        $this->unitOfWork->getScheduledEntityUpdates()->willReturn([new \stdClass()]);

        $this->rubricVersionManager->bump()->shouldNotBeCalled();
        $this->commandBus->dispatch(Argument::cetera())->shouldNotBeCalled();

        $this->flush();
    }
}
