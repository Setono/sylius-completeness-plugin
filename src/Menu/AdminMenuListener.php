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

        $parent
            ->addChild('setono_sylius_completeness_rules', [
                'route' => 'setono_sylius_completeness_admin_completeness_rule_index',
            ])
            ->setLabel('setono_sylius_completeness.ui.completeness_rules')
            ->setLabelAttribute('icon', 'tasks');

        $parent
            ->addChild('setono_sylius_completeness_context_settings', [
                'route' => 'setono_sylius_completeness_admin_context_setting_index',
            ])
            ->setLabel('setono_sylius_completeness.ui.context_settings')
            ->setLabelAttribute('icon', 'sliders horizontal');

        $parent
            ->addChild('setono_sylius_completeness_preview', [
                'route' => 'setono_sylius_completeness_admin_preview',
            ])
            ->setLabel('setono_sylius_completeness.ui.preview')
            ->setLabelAttribute('icon', 'flask');
    }
}
