<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Setono\SyliusCompletenessPlugin\Message\Command\RefreshCompletenessRollups;
use Setono\SyliusCompletenessPlugin\Model\CompletenessContextInterface;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRuleInterface;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/**
 * Watches rule and context changes. A rule change invalidates every product's score, so it bumps
 * the rubric version - which makes every product stale and lets the background drain re-evaluate the
 * catalog (no message dispatched, so several rule edits debounce into a single drain). A context
 * change only affects the global rollup, so it dispatches a rollup-only refresh over Messenger
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
            } elseif (is_a($class, CompletenessContextInterface::class, true)) {
                $this->settingChanged = true;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        try {
            if ($this->ruleChanged) {
                // bumping the version marks every product stale; the drain re-evaluates them
                $this->rubricVersionManager->bump();
            } elseif ($this->settingChanged) {
                // rollup-only: recompute the global ratio from the existing per-context rows
                $this->commandBus->dispatch(new Envelope(new RefreshCompletenessRollups(), [new DispatchAfterCurrentBusStamp()]));
            }
        } finally {
            $this->ruleChanged = false;
            $this->settingChanged = false;
        }
    }
}
