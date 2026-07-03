<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();
        $parent = $menu->getChild('catalog') ?? $menu;

        // a single entry point: the dashboard links out to the rules, contexts and preview
        $parent
            ->addChild('setono_sylius_completeness', [
                'route' => 'setono_sylius_completeness_admin_dashboard',
            ])
            ->setLabel('setono_sylius_completeness.ui.completeness')
            ->setLabelAttribute('icon', 'chart bar');
    }
}
