<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Controller\Admin;

use Setono\SyliusCompletenessPlugin\Calculator\CompletenessCalculatorInterface;
use Setono\SyliusCompletenessPlugin\Calculator\Result\ContextResult;
use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Setono\SyliusCompletenessPlugin\Form\Type\PreviewType;
use Setono\SyliusCompletenessPlugin\Preview\ScratchpadEvaluatorInterface;
use Setono\SyliusCompletenessPlugin\Preview\ScratchpadResult;
use Sylius\Component\Core\Model\ChannelInterface as CoreChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * The "test against a product" screen: runs the live rubric for a chosen product + channel + locale
 * and, optionally, an ad-hoc scratchpad expression. Persists nothing
 */
final class PreviewAction
{
    /**
     * @param RepositoryInterface<CoreChannelInterface> $channelRepository
     * @param RepositoryInterface<LocaleInterface> $localeRepository
     */
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly CompletenessCalculatorInterface $calculator,
        private readonly ScratchpadEvaluatorInterface $scratchpadEvaluator,
        private readonly RepositoryInterface $channelRepository,
        private readonly RepositoryInterface $localeRepository,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->formFactory->create(PreviewType::class);
        $form->handleRequest($request);

        $contextResult = null;
        $scratchpadResult = null;

        if ($form->isSubmitted() && $form->isValid()) {
            [$contextResult, $scratchpadResult] = $this->run($form);
        }

        return new Response($this->twig->render('@SetonoSyliusCompletenessPlugin/Admin/Preview/index.html.twig', [
            'form' => $form->createView(),
            'contextResult' => $contextResult,
            'scratchpadResult' => $scratchpadResult,
        ]));
    }

    /**
     * @param \Symfony\Component\Form\FormInterface<mixed> $form
     *
     * @return array{0: ?ContextResult, 1: ?ScratchpadResult}
     */
    private function run(\Symfony\Component\Form\FormInterface $form): array
    {
        $product = $form->get('product')->getData();
        $channel = $this->channelRepository->findOneBy(['code' => $form->get('channelCode')->getData()]);
        $locale = $this->localeRepository->findOneBy(['code' => $form->get('localeCode')->getData()]);

        if (!$product instanceof ProductInterface || !$channel instanceof CoreChannelInterface || !$locale instanceof LocaleInterface) {
            return [null, null];
        }

        $context = new CompletenessCheckContext($channel, $locale);
        $contextResult = $this->calculator->calculateContext($product, $context);

        $scratchpadResult = null;
        $expression = $form->get('expression')->getData();
        if (is_string($expression) && '' !== trim($expression)) {
            $scratchpadResult = $this->scratchpadEvaluator->evaluate($product, $context, $expression);
        }

        return [$contextResult, $scratchpadResult];
    }
}
