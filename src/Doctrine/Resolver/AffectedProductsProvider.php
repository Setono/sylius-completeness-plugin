<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Doctrine\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\ProductInterface;

final class AffectedProductsProvider implements AffectedProductsProviderInterface
{
    /** @var array<class-string, AffectedProductsResolverInterface|null>|null lazily built class => resolver map (null = known non-match) */
    private ?array $resolverMap = null;

    /**
     * @param iterable<AffectedProductsResolverInterface> $resolvers
     */
    public function __construct(
        private readonly iterable $resolvers,
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * @return iterable<ProductInterface>
     */
    public function getProducts(object $entity): iterable
    {
        $resolver = $this->resolveResolver($this->resolveClass($entity));
        if (null === $resolver) {
            return [];
        }

        return $resolver->getProducts($entity);
    }

    /**
     * @return class-string
     */
    private function resolveClass(object $entity): string
    {
        // entities may be Doctrine proxies, so resolve the real class through the metadata
        $manager = $this->managerRegistry->getManagerForClass($entity::class);
        if ($manager instanceof EntityManagerInterface) {
            return $manager->getClassMetadata($entity::class)->getName();
        }

        return $entity::class;
    }

    /**
     * @param class-string $class
     */
    private function resolveResolver(string $class): ?AffectedProductsResolverInterface
    {
        if (null === $this->resolverMap) {
            $this->resolverMap = [];
        }

        if (array_key_exists($class, $this->resolverMap)) {
            return $this->resolverMap[$class];
        }

        $match = null;
        foreach ($this->resolvers as $resolver) {
            foreach ($resolver->getSupportedClasses() as $supportedClass) {
                if (is_a($class, $supportedClass, true)) {
                    // keep iterating: the LAST registered resolver wins, mirroring checker semantics
                    $match = $resolver;
                }
            }
        }

        return $this->resolverMap[$class] = $match;
    }
}
