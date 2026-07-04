import { test, expect } from '@playwright/test';

const LABEL = '#setono_sylius_completeness_completeness_rule_label';
const TYPE = '#setono_sylius_completeness_completeness_rule_type';
const CODE = 'input[data-ssc-code]';

test('the checker configuration swaps in when the checker changes', async ({ page }) => {
    await page.goto('/admin/completeness/rules/new');

    // "Has a minimum number of images" carries a configuration field; picking it swaps that field in
    await page.selectOption(TYPE, 'has_minimum_images');
    await expect(page.locator('#ssc-checker-configuration input[type="number"]')).toBeVisible();

    // the "Custom expression" checker swaps in the expression editor instead (the textarea itself is
    // hidden once CodeMirror enhances it, so assert it is present rather than visible)
    await page.selectOption(TYPE, 'expression');
    await expect(page.locator('#ssc-checker-configuration [data-ssc-expression]')).toBeAttached();
    await expect(page.locator('#ssc-checker-configuration input[type="number"]')).toHaveCount(0);
});

test('the code auto-generates from the label and is immutable once created', async ({ page }) => {
    await page.goto('/admin/completeness/rules/new');

    // on create the code is read-only and mirrors the label
    await expect(page.locator(CODE)).toHaveAttribute('readonly', /.*/);
    await page.fill(LABEL, 'My E2E rule!');
    await expect(page.locator(CODE)).toHaveValue('my_e2e_rule');
});

test('a rule can be created and shows up in the grid', async ({ page }) => {
    const label = `E2E rule ${Date.now()}`;

    await page.goto('/admin/completeness/rules/new');
    await page.fill(LABEL, label);
    await page.selectOption(TYPE, 'has_name');
    await page.locator('button[type="submit"]:has-text("Create")').click();

    await expect(page).toHaveURL(/\/admin\/completeness\/rules\/\d+\/edit/);
    await page.goto('/admin/completeness/rules/');
    await expect(page.getByText(label)).toBeVisible();
});

test('the rules grid exposes channel and locale filters', async ({ page }) => {
    await page.goto('/admin/completeness/rules/');
    await expect(page.locator('select[name="criteria[channelCode]"]')).toBeVisible();
    await expect(page.locator('select[name="criteria[localeCode]"]')).toBeVisible();
});
