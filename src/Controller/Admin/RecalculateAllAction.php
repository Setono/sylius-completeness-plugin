<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Controller\Admin;

use Setono\SyliusCompletenessPlugin\Message\Command\RecalculateAllProductsCompleteness;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Dashboard action: dispatches a recalculation of the whole catalog.
 */
final class RecalculateAllAction
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly RouterInterface $router,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('recalculate_completeness', (string) $request->request->get('_csrf_token')))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        $this->commandBus->dispatch(new RecalculateAllProductsCompleteness());

        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('success', 'setono_sylius_completeness.recalculation_scheduled');
        }

        return new RedirectResponse($this->router->generate('setono_sylius_completeness_admin_dashboard'));
    }
}
