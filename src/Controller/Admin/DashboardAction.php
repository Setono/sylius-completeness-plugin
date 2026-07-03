<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Controller\Admin;

use Setono\SyliusCompletenessPlugin\Provider\CompletenessDashboardProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * The completeness landing page: catalog-wide figures and links to the rules, contexts and preview.
 */
final class DashboardAction
{
    public function __construct(
        private readonly CompletenessDashboardProviderInterface $dashboardProvider,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(): Response
    {
        return new Response($this->twig->render('@SetonoSyliusCompletenessPlugin/Admin/Dashboard/index.html.twig', [
            'statistics' => $this->dashboardProvider->getStatistics(),
            'lowestProducts' => $this->dashboardProvider->getLowestScoringProducts(10),
        ]));
    }
}
