import { test, expect } from '@playwright/test';

test('the completeness dashboard renders the catalog figures and quick links', async ({ page }) => {
    await page.goto('/admin/completeness/dashboard');

    await expect(page.getByRole('heading', { name: 'Completeness' })).toBeVisible();

    // four headline stat cards
    await expect(page.locator('.statistic')).toHaveCount(4);
    await expect(page.getByText('Products scored')).toBeVisible();
    await expect(page.getByText('Average completeness')).toBeVisible();

    // quick-link cards out to the rules, contexts and preview
    await expect(page.getByRole('link', { name: /Completeness rules/ })).toBeVisible();
    await expect(page.getByRole('link', { name: /Completeness contexts/ })).toBeVisible();
    await expect(page.getByRole('link', { name: /Test against a product/ })).toBeVisible();
});

test('the single admin menu item links to the dashboard', async ({ page }) => {
    await page.goto('/admin/');
    await expect(page.locator('.sidebar a[href$="/admin/completeness/dashboard"], .menu a[href$="/admin/completeness/dashboard"]')).toBeVisible();
});
