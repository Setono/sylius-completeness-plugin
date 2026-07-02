<?php

declare(strict_types=1);

use Setono\SyliusCompletenessPlugin\Tests\Application\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../../vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/../Application/.env', 'test');

$kernel = new Kernel('test', true);
$kernel->boot();

/** @phpstan-ignore method.notFound,method.nonObject */
return $kernel->getContainer()->get('doctrine')->getManager();
