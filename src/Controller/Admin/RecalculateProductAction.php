<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Controller\Admin;

use Setono\SyliusCompletenessPlugin\Provider\ProductProviderInterface;
use Setono\SyliusCompletenessPlugin\Updater\ProductCompletenessUpdaterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Recalculates a single product's completeness synchronously (in-request), so the score and
 * matrix reflect the new values immediately - distinct from the async bulk path
 */
final class RecalculateProductAction
{
    public function __construct(
        private readonly ProductProviderInterface $productProvider,
        private readonly ProductCompletenessUpdaterInterface $updater,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly RouterInterface $router,
    ) {
    }

    public function __invoke(Request $request, int $id): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('recalculate_completeness', (string) $request->request->get('_csrf_token')))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        $product = $this->productProvider->findById($id);
        if (null === $product) {
            throw new NotFoundHttpException(sprintf('Product with id %d not found.', $id));
        }

        $this->updater->update($product);

        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('success', 'setono_sylius_completeness.product_recalculated');
        }

        return new RedirectResponse($this->resolveRedirectUrl($request, $id));
    }

    private function resolveRedirectUrl(Request $request, int $id): string
    {
        $referer = $request->headers->get('referer');
        if (null !== $referer && '' !== $referer) {
            return $referer;
        }

        return $this->router->generate('sylius_admin_product_update', ['id' => $id]);
    }
}
