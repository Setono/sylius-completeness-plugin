<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Message;

/**
 * Marker interface for the plugin's messages. Route this interface to an async transport in
 * your Messenger configuration to process recalculations asynchronously (recommended)
 */
interface CommandInterface
{
}
