import { test, expect } from '@playwright/test';

// guards regression of issue #4: the product picker must render and its search must work
test('the preview product autocomplete renders and searches', async ({ page }) => {
    await page.goto('/admin/completeness/preview');

    await expect(page.getByText('Product', { exact: true })).toBeVisible();

    const autocomplete = page.locator('.sylius-autocomplete');
    await expect(autocomplete).toBeVisible();
    await expect(autocomplete).toHaveAttribute('data-url', /.+/);

    // type into the widget and expect AJAX-loaded product results
    await autocomplete.click();
    await autocomplete.locator('input.search').fill('cap');
    await expect(autocomplete.locator('.menu .item').first()).toBeVisible({ timeout: 10000 });
});
