import { test, expect } from '@playwright/test';

test('the weight field is disabled while the context does not count toward the overall score', async ({ page }) => {
    await page.goto('/admin/completeness/contexts/new');

    const counts = page.locator('#setono_sylius_completeness_context_countsTowardOverall');
    const weight = page.locator('#setono_sylius_completeness_context_rollupWeight');

    // there is no Advanced accordion any more - the weight is shown inline and enabled by default
    await expect(weight).toBeVisible();
    await expect(weight).toBeEnabled();

    // unchecking the toggle disables the weight (it only has an effect when the context counts). The
    // checkbox is a Semantic-UI toggle whose real input is visually hidden, so toggle it with force
    await counts.uncheck({ force: true });
    await expect(weight).toBeDisabled();

    await counts.check({ force: true });
    await expect(weight).toBeEnabled();
});

test('the context grid shows the explanatory intro box', async ({ page }) => {
    await page.goto('/admin/completeness/contexts/');
    await expect(page.getByText(/A completeness context is a single channel/)).toBeVisible();
});
