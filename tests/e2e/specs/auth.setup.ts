import { test as setup, expect } from '@playwright/test';

const authFile = '.auth/admin.json';

// log in once and persist the session; every spec reuses it via storageState
setup('authenticate as admin', async ({ page }) => {
    await page.goto('/admin/login');

    await page.locator('input[name="_username"]').fill('sylius');
    await page.locator('input[name="_password"]').fill('sylius');
    await page.locator('form button[type="submit"], form [type="submit"]').first().click();

    // Sylius redirects to the admin dashboard on success
    await page.waitForURL(/\/admin\/?($|\?)/, { timeout: 15000 });
    await expect(page).not.toHaveURL(/\/admin\/login/);

    await page.context().storageState({ path: authFile });
});
