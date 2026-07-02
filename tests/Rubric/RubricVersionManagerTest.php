<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Rubric;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusCompletenessPlugin\Model\RubricVersion;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManager;

final class RubricVersionManagerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<Connection> */
    private ObjectProphecy $connection;

    private RubricVersionManager $manager;

    protected function setUp(): void
    {
        $this->connection = $this->prophesize(Connection::class);

        /** @var ClassMetadata<RubricVersion> $classMetadata */
        $classMetadata = new ClassMetadata(RubricVersion::class);
        $classMetadata->setPrimaryTable(['name' => 'setono_sylius_completeness__rubric_version']);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->getConnection()->willReturn($this->connection->reveal());
        $entityManager->getClassMetadata(RubricVersion::class)->willReturn($classMetadata);

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(RubricVersion::class)->willReturn($entityManager->reveal());

        $this->manager = new RubricVersionManager($managerRegistry->reveal());
    }

    /**
     * @test
     */
    public function it_returns_zero_when_no_version_row_exists(): void
    {
        $this->connection->fetchOne('SELECT version FROM setono_sylius_completeness__rubric_version WHERE id = 1')->willReturn(false);

        self::assertSame(0, $this->manager->getCurrentVersion());
    }

    /**
     * @test
     */
    public function it_returns_the_current_version(): void
    {
        $this->connection->fetchOne('SELECT version FROM setono_sylius_completeness__rubric_version WHERE id = 1')->willReturn('7');

        self::assertSame(7, $this->manager->getCurrentVersion());
    }

    /**
     * @test
     */
    public function it_bumps_an_existing_version(): void
    {
        $this->connection->executeStatement('UPDATE setono_sylius_completeness__rubric_version SET version = version + 1 WHERE id = 1')->willReturn(1);
        $this->connection->fetchOne('SELECT version FROM setono_sylius_completeness__rubric_version WHERE id = 1')->willReturn('8');

        self::assertSame(8, $this->manager->bump());
    }

    /**
     * @test
     */
    public function it_inserts_the_version_row_on_first_bump(): void
    {
        $this->connection->executeStatement('UPDATE setono_sylius_completeness__rubric_version SET version = version + 1 WHERE id = 1')->willReturn(0);
        $this->connection->executeStatement('INSERT INTO setono_sylius_completeness__rubric_version (id, version) VALUES (1, 1)')->willReturn(1);

        self::assertSame(1, $this->manager->bump());
    }
}
