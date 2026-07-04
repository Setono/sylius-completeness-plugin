# Setono Sylius Completeness Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]

Compute a **weighted, per-channel/per-locale enrichment completeness percentage** for your Sylius products,
persist it, roll it up to a single global score on the product and surface it across the admin: a **Completeness
dashboard** (catalog-wide figures, score distribution and the products most in need of work) reached from a
single admin menu item, a grid column (threshold color-coded, stale-aware) with a numeric range filter, a
channel × locale breakdown panel on the product show and edit pages, a rule CRUD and a "test against a product"
preview with a live expression scratchpad.

Scoring rules are **database-backed and admin-managed**, with three tiers of flexibility:

1. **Curated checkers** — discoverable built-ins (`has_image`, `has_price`, `has_minimum_images`, …).
2. **Developer checkers** — implement an interface, tag the service, done.
3. **ExpressionLanguage rules** — authored entirely in the UI, with a rich helper library.

## Installation

### 1. Require the plugin

```bash
composer require setono/sylius-completeness-plugin
```

### 2. Register the bundle

```php
# config/bundles.php

return [
    // ...
    Setono\SyliusCompletenessPlugin\SetonoSyliusCompletenessPlugin::class => ['all' => true],
];
```

### 3. Import the configuration and routing

```yaml
# config/packages/setono_sylius_completeness.yaml
imports:
    - { resource: "@SetonoSyliusCompletenessPlugin/Resources/config/app/config.yaml" }

setono_sylius_completeness: ~
```

```yaml
# config/routes/setono_sylius_completeness.yaml
setono_sylius_completeness_admin:
    resource: "@SetonoSyliusCompletenessPlugin/Resources/config/routes/admin.yaml"
    prefix: /admin
```

### 4. Make your `Product` completeness-aware

Apply the shipped interface and trait to your product entity:

```php
# src/Entity/Product/Product.php
namespace App\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareInterface;
use Setono\SyliusCompletenessPlugin\Model\ProductCompletenessAwareTrait;
use Sylius\Component\Core\Model\Product as BaseProduct;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_product')]
class Product extends BaseProduct implements ProductCompletenessAwareInterface
{
    use ProductCompletenessAwareTrait;
}
```

The plugin ships **XML** Doctrine mappings, so add the matching mapping fragment for the fields the trait
introduces (or use PHP attributes as above and only map the association + scalar columns). Example XML fragment:

```xml
<!-- config/doctrine/Product.orm.xml -->
<entity name="App\Entity\Product\Product" table="sylius_product">
    <indexes>
        <index columns="completeness_ratio"/>
        <index columns="completeness_dirty_at"/>
    </indexes>

    <field name="completenessRatio" column="completeness_ratio" type="smallint" nullable="true"/>
    <field name="completenessRubricVersion" column="completeness_rubric_version" type="integer" nullable="true"/>
    <field name="completenessDirtyAt" column="completeness_dirty_at" type="datetime_immutable" nullable="true"/>

    <one-to-many field="completenesses" target-entity="Setono\SyliusCompletenessPlugin\Model\ProductCompleteness" mapped-by="product" orphan-removal="true">
        <cascade>
            <cascade-persist/>
            <cascade-remove/>
        </cascade>
    </one-to-many>
</entity>
```

Point your host application to the completeness `Product` model:

```yaml
# config/packages/_sylius.yaml
sylius_product:
    resources:
        product:
            classes:
                model: App\Entity\Product\Product
```

### 5. Update the database

Generate and run a migration (the plugin does not ship migrations because the target `sylius_product`
table is host-owned):

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

This creates the `setono_sylius_completeness__*` tables and adds `completeness_ratio` (indexed),
`completeness_rubric_version` and `completeness_dirty_at` (indexed) to `sylius_product`.

### 6. Schedule the drain (required)

Most recalculation happens in the background: changes mark the affected products *dirty* and a drain
command recalculates them. Run it on a cron every few minutes:

```cron
*/5 * * * * cd /path/to/app && bin/console setono:completeness:process
```

It is safe to overlap (a leased lock guarantees a single run) and to run as often as you like. See
[How recalculation is triggered](#how-recalculation-is-triggered) for the full picture.

### 7. Optional: starter ruleset + initial calculation

```bash
bin/console sylius:fixtures:load setono_sylius_completeness   # a sensible starter rubric
bin/console setono:completeness:recalculate --all             # score the whole catalog
```

## Concepts

- **Rule** — a persisted, admin-managed record binding a checker `type` (+ `configuration`) to a **weight
  tier** (`low`/`medium`/`high`/`critical`), an optional **scope** (channels/locales/taxons) and an optional
  ExpressionLanguage **condition** gate. The `expression` checker's `configuration` holds the
  ExpressionLanguage **expression** that is the check itself. The set of enabled rules is the scoring rubric.
- **Weight vs score** — a rule's *weight* is "how much it matters" (from the tier); a checker's *score* is
  "how met it is" (0.0–1.0). Binary checkers return 1.0/0.0; graded checkers grant partial credit (e.g.
  `has_minimum_images` with 3 of 5 ⇒ 0.6).
- **Context** — a `(channel, locale)` pair. Each context is scored independently; translatable fields resolve
  to exactly that locale (a missing translation reads as empty, never the default-locale text).
- **N/A** — a context with no applicable rules is **not scored** (rendered as "—"), distinct from a measured 0%.
  N/A contexts are excluded from the global rollup.
- **Context settings** — an optional per-`(channel, locale)` record holding a "ready" **threshold** (for
  color-coding) and a **rollup weight** (0 = excluded from the global score). A missing row means defaults, so
  an empty table reproduces flat-average, single-threshold behavior.
- **Rollup** — the per-context ratios collapse into the single `completenessRatio` via a configurable strategy
  (`weighted_average` default, `minimum`, `default_channel`), after dropping N/A and excluded contexts.
- **Staleness** — a monotonic rubric version is bumped on every rule change and stamped on products at
  calc time. The grid and panel show a "recalculating…" marker for products whose stamped version is
  behind (a rule changed) or that are flagged `completeness_dirty_at` (their own data changed), until
  the [drain](#how-recalculation-is-triggered) catches up.

## How recalculation is triggered

Scores are kept up to date through three lanes, so an interactive edit is instant while bulk changes
never block a request:

| Change | Mechanism | When it recalculates |
|---|---|---|
| A product/variant is **saved in the admin** | Sylius resource events (`sylius.product.post_*`, `sylius.product_variant.post_*`) | **Immediately & synchronously** — the fresh score is on the page you land on |
| Any other product change — **API, imports, programmatic writes**, changes to related entities | a Doctrine `onFlush` listener sets `completeness_dirty_at` on the affected product(s) | on the next **drain** |
| A **rule** changes | the rubric version is bumped (every product becomes stale) | on the next **drain** |
| A **context** changes | a rollup-only refresh is dispatched over Messenger | when that message is handled |
| **Manual** — the dashboard/grid "Recalculate" buttons, or `setono:completeness:recalculate` | Messenger / direct | on demand |

The **drain** (`setono:completeness:process`, step 6) is the workhorse: every few minutes it recalculates
the products that are dirty or stale, in id-keyset chunks, under a leased lock so runs never overlap. It
debounces bursts (a 10k-product import is *one* drain, not 10k recalculations) and needs no message
worker. The `completeness_dirty_at` flag is cleared only if it hasn't changed since the product was picked
up, so an edit that lands mid-run is retried rather than lost.

The Doctrine `onFlush` marker never calls `flush()` or dispatches — it writes the flag as part of the same
flush via `recomputeSingleEntityChangeSet`. New products are not flagged: their null rubric version already
makes them drain candidates. To watch an additional entity, register an
[`AffectedProductsResolverInterface`](#extension-points) — both the marker and the immediate lane use it.

Set `recalculate_on_doctrine_flush: false` to disable the dirty-marking entirely (then only the manual
lane and a periodic `recalculate --all` keep scores fresh).

## Expression authoring

Conditions and expressions use the Symfony ExpressionLanguage. A **condition** decides *if* a rule applies; an
**expression** *is* the check for `expression`-type rules (a boolean means met/not met, a number between 0 and 1
grants partial credit).

The condition and expression fields (and the preview scratchpad) are enhanced with a
[CodeMirror](https://codemirror.net/) editor that adds syntax highlighting and autocompletion of the
in-scope variables and the registered functions (host-added functions included). CodeMirror is loaded
from a versioned, SRI-pinned CDN, so the plugin needs no asset build; if it is unavailable the fields
degrade gracefully to plain textareas.

Variables in scope: `product`, `channel`, `locale`, `channelCode`, `localeCode`.

Translatable fields are read through the product getters and always resolve to the scored locale:

```
word_count(product.getDescription()) >= 200
```

Use native operators — arithmetic `+ - * / %`, comparison `== != < > <= >=`, logical `and or not`, membership
`in` / `not in`, **regex `matches`**, concat `~`, ternary `?:` — plus the helper library (`word_count`,
`char_count`, `has_attribute`, `attribute_value`, `image_count`, `in_taxon`, `has_price`, `price`, `min`,
`max`, `between`, …). The full catalog is rendered inline in the rule form and on the preview screen.

**"Required-when" rules** use both slots — e.g. *"if `type` is beer, `beer_type` must be set"*:

- condition: `attribute_value(product, 'type') == 'beer'`
- expression: `has_attribute(product, 'beer_type')`

The rule then vanishes for non-beer products (counting toward neither numerator nor denominator).

Caveats:

- **Regex ReDoS**: author-supplied `matches` patterns run unsandboxed; a catastrophic pattern can hang a
  calculation. Keep rule administration to trusted users.
- **Select-attribute values are codes**: `attribute_value(product, code)` returns the stored option **code**,
  not the display label.
- The case-insensitive contains helper is named **`icontains`** (`contains` is a reserved EL operator).

## Extension points

Everything is a tagged service. All of these are supported and documented:

| Tag / interface | Purpose |
|---|---|
| `setono_sylius_completeness.checker` (`CompletenessCheckerInterface`) | Add a checker. If two share a `type`, the **last registered wins** — that's how you override a built-in. |
| `setono_sylius_completeness.checker_configuration_form_type` | Register a checker's configuration form. |
| `setono_sylius_completeness.expression_function_provider` | Add expression helper functions (a Symfony `ExpressionFunctionProviderInterface`). |
| `setono_sylius_completeness.affected_products_resolver` (`AffectedProductsResolverInterface`) | Make changes to your own entities trigger recalculation — no core change. |
| `setono_sylius_completeness.rollup_strategy` (`RollupStrategyInterface`) | Add a rollup strategy. |

The public API is `Setono\SyliusCompletenessPlugin\Calculator\CompletenessCalculatorInterface` (a pure dry-run
that returns the full breakdown) and `Setono\SyliusCompletenessPlugin\Updater\ProductCompletenessUpdaterInterface`
(calculate + persist). After each persisted calculation a `ProductCompletenessCalculated` event is dispatched
(with a `bulk` flag). Notice that context changes trigger a rollup-only refresh, which recomputes the
global ratio from existing rows and does **not** dispatch that event.

## Configuration reference

```yaml
setono_sylius_completeness:
    rollup_strategy: weighted_average   # weighted_average | minimum | default_channel | <custom>
    default_channel_code: ~             # channel used by the default_channel strategy
    default_ready_threshold: 80         # green/"ready" line when a context has no override
    amber_band: 20                      # width of the amber zone below the threshold (0 disables amber)
    weight_tiers:
        low: 1
        medium: 3
        high: 6
        critical: 10
    enable_custom_weight: false         # exposes the advanced per-rule float override
    recalculate_on_doctrine_flush: true # a flush marks affected products dirty for the drain
    recalculation_lock_ttl: 900         # lease (s) of the drain's lock; refreshed every chunk
```

There is intentionally **no** `watched_entities` key: the set of watched classes is derived from the registered
`AffectedProductsResolverInterface` services. To watch an additional entity, register a resolver.

## Console

```bash
# The background drain: recalculate dirty/stale products (schedule this on a cron, see step 6)
bin/console setono:completeness:process

# Recalculate the whole catalog synchronously (good after install, or as a periodic safety net)
bin/console setono:completeness:recalculate --all

# Recalculate specific products
bin/console setono:completeness:recalculate --product=SKU-1 --product=SKU-2
```

## Translations

`en` is the authoritative source of truth. The plugin ships admin translations for **`da`, `sv`, `no`, `fi`,
`de`, `fr`, `es`, `it`, `nl`, `pl`, `pt`, `cs`, `hu`, `ro` and `uk`**; any untranslated key falls back to
English via the Symfony translator. (Norwegian uses `no`; if your shop runs `nb`, copy the catalog under that
code.)

[ico-version]: https://poser.pugx.org/setono/sylius-completeness-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-completeness-plugin/license
[ico-github-actions]: https://github.com/Setono/SyliusCompletenessPlugin/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/SyliusCompletenessPlugin/branch/master/graph/badge.svg

[link-packagist]: https://packagist.org/packages/setono/sylius-completeness-plugin
[link-github-actions]: https://github.com/Setono/SyliusCompletenessPlugin/actions
[link-code-coverage]: https://codecov.io/gh/Setono/SyliusCompletenessPlugin
