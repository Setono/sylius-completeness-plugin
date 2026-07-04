# End-to-end (Playwright) specs

Browser specs for the plugin's admin UI (dashboard, rule form, context form, preview autocomplete,
grid filters). They run against a fully set-up test application and are executed in CI by the
[`e2e` job](../../.github/workflows/build.yaml) of the `build` workflow.

## Run locally

Start the test application (see the plugin README's *Test Application* section) with its assets built,
then:

```bash
cd tests/e2e
npm install
npx playwright install chromium
# point at your running app (defaults to the symfony server on :8033)
PLAYWRIGHT_BASE_URL=http://127.0.0.1:8033 npx playwright test
```

The admin credentials (`sylius` / `sylius`) are logged in once by `specs/auth.setup.ts` and reused via
a stored session, so every spec starts authenticated.
