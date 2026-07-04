<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

interface RubricVersionInterface extends ResourceInterface
{
    public function getId(): int;

    public function getVersion(): int;

    public function setVersion(int $version): void;
}
