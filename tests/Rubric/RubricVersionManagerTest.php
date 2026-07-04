<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Rubric;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusCompletenessPlugin\Model\RubricVersion;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManager;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Exercises the manager against a real database: it reads and bumps the single rubric-version row
 * through the resource repository (DQL), creating the row on the first bump
 *
 * @group functional
 */
final class RubricVersionManagerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private RubricVersionManagerInterface $manager;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;

        /** @var RubricVersionManagerInterface $manager */
        $manager = self::getContainer()->get(RubricVersionManager::class);
        $this->manager = $manager;

        // the version row is a single global row, so start every test from a known empty state
        $this->deleteVersionRow();
    }

    protected function tearDown(): void
    {
        try {
            if ($this->entityManager->isOpen()) {
                $this->deleteVersionRow();
            }
        } catch (\Throwable) {
            // best effort cleanup - never mask the actual test result
        }

        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_returns_zero_when_no_version_row_exists(): void
    {
        self::assertSame(0, $this->manager->getCurrentVersion());
    }

    /**
     * @test
     */
    public function it_creates_the_row_on_the_first_bump_and_increments_afterwards(): void
    {
        self::assertSame(1, $this->manager->bump());
        self::assertSame(2, $this->manager->bump());
        self::assertSame(3, $this->manager->bump());

        self::assertSame(3, $this->manager->getCurrentVersion());
    }

    private function deleteVersionRow(): void
    {
        $this->entityManager->createQuery(sprintf('DELETE FROM %s', RubricVersion::class))->execute();
        $this->entityManager->clear();
    }
}
