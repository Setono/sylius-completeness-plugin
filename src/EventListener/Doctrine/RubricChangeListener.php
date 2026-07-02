<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateAllProductsCompleteness;
use Setono\SyliusCompletenessPlugin\Message\Command\RefreshCompletenessRollups;
use Setono\SyliusCompletenessPlugin\Model\CompletenessContextSettingInterface;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/**
 * Watches rule and context setting changes: bumps the rubric version (staleness signalling)
 * and dispatches the appropriate recalculation. A rule change requires a full catalog
 * re-evaluation; a context setting change only requires refreshing the global rollups from
 * the existing per context rows
 */
final class RubricChangeListener
{
    private bool $ruleChanged = false;

    private bool $settingChanged = false;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly RubricVersionManagerInterface $rubricVersionManager,
    ) {
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $manager = $eventArgs->getObjectManager();
        $unitOfWork = $manager->getUnitOfWork();

        $entities = [
            ...$unitOfWork->getScheduledEntityInsertions(),
            ...$unitOfWork->getScheduledEntityUpdates(),
            ...$unitOfWork->getScheduledEntityDeletions(),
        ];

        foreach ($entities as $entity) {
            $class = $manager->getClassMetadata($entity::class)->getName();

            if (is_a($class, CompletenessRuleInterface::class, true)) {
                $this->ruleChanged = true;
            } elseif (is_a($class, CompletenessContextSettingInterface::class, true)) {
                $this->settingChanged = true;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        try {
            if (!$this->ruleChanged && !$this->settingChanged) {
                return;
            }

            $this->rubricVersionManager->bump();

            $message = $this->ruleChanged ? new RecalculateAllProductsCompleteness() : new RefreshCompletenessRollups();

            $this->commandBus->dispatch(new Envelope($message, [new DispatchAfterCurrentBusStamp()]));
        } finally {
            $this->ruleChanged = false;
            $this->settingChanged = false;
        }
    }
}
