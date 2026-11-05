# SIGAP NPM Dependency and JavaScript Coverage Review

**Review date:** 2026-11-05

**Scope:** Read-only follow-up on residual npm advisories, Puppeteer/Browsershot and Flowbite usage, JavaScript coverage, and the Vite bundle.

## Summary

The current `npm audit` reports 14 affected packages: 10 high and 4 low. `npm audit --omit=dev` reports the same 14 findings, so the remaining paths are in the production dependency graph. This confirms advisory presence, not that an attacker can trigger the vulnerable behavior through SIGAP. No exploitable application path was demonstrated in the reviewed source or configuration.

No application source, package manifest, lockfile, or build/test configuration was changed during this audit. The 44 existing JavaScript tests were run with V8 coverage: all passed. Vitest updated its ignored coverage cache under `node_modules`.

## Residual npm advisories

| # | Severity | Package and installed version | Dependency path / advisory | Fix indicated by npm |
|---|---|---|---|---|
| 1 | High | `puppeteer@24.43.1` | Direct production dependency; vulnerable browser, proxy, and zip transitive dependencies | `puppeteer@25.16.0`, major |
| 2 | High | `@puppeteer/browsers@2.13.2` | `puppeteer` → `@puppeteer/browsers`; `extract-zip` and `proxy-agent` advisories | `puppeteer@25.16.0`, major |
| 3 | High | `basic-ftp@5.3.1` | `puppeteer` → `proxy-agent` → `pac-proxy-agent` → `get-uri` → `basic-ftp`; ReDoS advisory | `puppeteer@25.16.0`, major |
| 4 | High | `chromium-bidi@14.0.0` | `puppeteer` → `puppeteer-core` → `chromium-bidi` → `zod` | `puppeteer@25.16.0`, major |
| 5 | High | `extract-zip@2.0.1` | `puppeteer` → `@puppeteer/browsers` → `extract-zip`; archive symlink path traversal/arbitrary file-write advisories | `puppeteer@25.16.0`, major |
| 6 | High | `get-uri@6.0.5` | `puppeteer` → proxy-agent chain; dependency on vulnerable `basic-ftp` | `puppeteer@25.16.0`, major |
| 7 | High | `pac-proxy-agent@7.2.0` | `puppeteer` → proxy-agent chain → `pac-proxy-agent` | `puppeteer@25.16.0`, major |
| 8 | High | `proxy-agent@6.5.0` | `puppeteer` → `@puppeteer/browsers` → `proxy-agent` | `puppeteer@25.16.0`, major |
| 9 | High | `puppeteer-core@24.43.1` | Transitive dependency of Puppeteer; browser/proxy advisories | `puppeteer@25.16.0`, major |
| 10 | High | `zod@3.25.76` | `puppeteer` → `puppeteer-core` → `chromium-bidi` → `zod`; memory-exhaustion advisory | `puppeteer@25.16.0`, major |
| 11 | Low | `flowbite@4.0.2` | Direct production dependency; advisory path through `flowbite-datepicker` | `flowbite@2.3.0`, major downgrade |
| 12 | Low | `flowbite-datepicker@2.0.0` | `flowbite` → `flowbite-datepicker` → `@rollup/plugin-node-resolve` | `flowbite@2.3.0`, major downgrade |
| 13 | Low | `@rollup/plugin-node-resolve@15.3.1` | `flowbite` → `flowbite-datepicker` → plugin; vulnerable `deepmerge` dependency | `flowbite@2.3.0`, major downgrade |
| 14 | Low | `deepmerge@4.3.1` | Flowbite datepicker/build-tool dependency chain; prototype pollution advisory | `flowbite@2.3.0`, major downgrade |

No remaining finding was reported without a fix path, but the available fixes require major-version changes to Puppeteer or Flowbite. Configuration changes alone do not update or remove these affected packages from the audited dependency graph.

## Puppeteer and Browsershot

- `package.json` declares Puppeteer as a direct production dependency.
- `composer.json` declares `spatie/browsershot` 5.4.0. The installed Browsershot bridge can `require('puppeteer')` when used.
- No Browsershot/Puppeteer call sites were found in application source, routes, views, or configuration. The employee PDF export inspected in `EmployeeService` uses DomPDF instead.
- `instal.bat` installs Composer dependencies but does not install npm packages or a browser. The repository does not establish whether another deployment process supplies Puppeteer and Chromium.
- Therefore, there is no demonstrated SIGAP request path that invokes the vulnerable Puppeteer operations. Do not treat this as proof that a deployment never uses Browsershot externally or in uninspected operational code.
- A Puppeteer major upgrade should be compatibility-tested with Browsershot and the target Node/Chrome deployment before adoption. The installed Node version at audit time is 24.13.1; the suggested Puppeteer 25.16.0 declares Node `>=22.12.0`.

## Flowbite and flowbite-datepicker

- `resources/js/app.js` imports `flowbite`, and `resources/css/app.css` imports its styles.
- Blade uses Flowbite data attributes for employee import/about/notification modals and the employee export dropdown. Flowbite is therefore active and should not be removed casually.
- No datepicker markup or direct datepicker use was found under `resources`. `flowbite-datepicker` is nevertheless a dependency of Flowbite 4.0.2 and is present in the built dependency graph.
- Downgrading Flowbite 4.0.2 to 2.3.0 is a major downgrade and could affect the current Tailwind 4 integration and component behavior. It requires compatibility and UI regression testing.

## JavaScript coverage validity

`vitest.config.js` sets the V8 provider and explicitly includes `resources/js/app.js`; tests are excluded from coverage.

The aggregate V8 JSON summary contains `app.js` with:

- Statements: 30/30 (100%)
- Lines: 30/30 (100%)
- Functions: 3/3 (100%)
- Branches: 0/0

The text coverage table does not print `app.js` as a separate row, but the generated JSON summary confirms it contributes to aggregate totals. This is measured coverage, not merely a test that imported the entry point. The app tests exercise entry-point setup, dispatch `DOMContentLoaded`, verify initializer ordering/calls, and test debounced resize handling. Imported initializers and bootstrap dependencies are mocked in the app-entry test, so this score measures orchestration in `app.js`, not the internals of those modules.

Full JavaScript suite result:

| Metric | Result |
|---|---:|
| Test files | 6 passed |
| Tests | 44 passed |
| Statements | 92.89% (1203/1295) |
| Branches | 76.35% (523/685) |
| Functions | 99.48% (195/196) |
| Lines | 93.09% (1200/1289) |

`resources/js/app1.js` is not an input in `vite.config.js`, does not appear in the generated Vite manifest, and has no external references in the reviewed project files. It is not an active Vite entry point based on current repository evidence.

## Bundle size and recommendations

The built entry is one JavaScript chunk, `public/build/assets/app-C6KU4aut.js`, at 689,516 bytes (215.23 kB gzip). The manifest contains no imported JS chunks. Vite reports that the chunk exceeds its 500 kB recommendation.

The main cause is eager static loading through `app.js`. The measured pre-minification module contributions were largest for Chart.js (about 385 kB), jQuery (176 kB), simple-datatables (99 kB), Axios (94 kB), Alpine (94 kB), Flowbite (93 kB), TomSelect (89 kB), and Flowbite datepicker (81 kB). These module figures are diagnostic contributions and are not additive final bundle sizes.

Recommended order, without changes applied:

1. Lazy-load Chart.js only on dashboard pages with chart data/canvases.
2. Load simple-datatables only on pages containing `.js-datatable`, and TomSelect only on forms that use it.
3. Keep Flowbite behavior used by Blade data attributes; investigate its selective module imports only with UI regression tests. Splitting imports does not by itself remove npm audit findings while vulnerable packages remain installed.
4. Evaluate whether Puppeteer/Browsershot is required by deployment. If retained, test the Puppeteer major upgrade in a representative deployment; if unused, discuss dependency removal separately rather than silently deleting it.
5. Consider the Flowbite major downgrade only after validating modal/dropdown behavior and Tailwind 4 compatibility.

Dynamic imports can change initialization timing and create asynchronous ordering/race issues; validate each affected page before adopting them.

## Files and evidence examined

- Dependency and build configuration: `package.json`, `package-lock.json`, `composer.json`, `composer.lock`, `vite.config.js`, `vitest.config.js`, `instal.bat`
- Application entry and libraries: `resources/js/app.js`, `resources/js/app1.js`, `resources/js/bootstrap.js`, `resources/js/components/core/chart.js`, `resources/js/components/core/datatable.js`, `resources/js/components/form/tomselect.js`, `resources/css/app.css`
- Flowbite templates: `resources/views/partials/topbar.blade.php`, `resources/views/employee/index.blade.php`, `resources/views/employee/import.blade.php`, `resources/views/notification/index.blade.php`, `resources/views/notification/confirmation.blade.php`, `resources/views/components/support.blade.php`
- Coverage tests: `tests/javascript/app/app.test.js`, `tests/javascript/app/bootstrap.test.js`, and the full `tests/javascript` suite
- Browsershot implementation: `vendor/spatie/browsershot/bin/browser.cjs`; application export path: `app/Services/EmployeeService.php`
- Generated evidence: `public/build/manifest.json`, V8 `coverage-summary.json`, and current `npm audit` output
