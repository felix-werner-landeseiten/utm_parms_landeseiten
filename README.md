# UTM Parameter Forwarder

WordPress plugin that carries UTM and ad click-ID parameters (Google, Meta, LinkedIn) from the landing URL to every internal link, so forms and tracking on inner pages receive them.

User-facing documentation: [`utm-parameter-forwarder/readme.txt`](utm-parameter-forwarder/readme.txt).

## Repository layout

| Path | Purpose |
| --- | --- |
| `utm-parameter-forwarder/` | The plugin itself. Exactly this folder ends up on the server. |
| `bin/build.php` | Used only by the release workflow: packs the plugin folder into the release ZIP and checks that all version numbers match. |
| `bin/changelog.php` | Used only by the release workflow: extracts one version's notes from `CHANGELOG.md`. |
| `.github/workflows/release.yml` | On a `v*.*.*` tag: lints, builds and publishes the release ZIP. |
| `CHANGELOG.md` | Version history. |

The plugin ZIP is never stored in the repository. It is created by GitHub Actions and attached to each release. Internal material such as security review reports is kept outside the repository (`local/` is excluded in `.gitignore`).

## Releasing a new version

1. Change the version in **three** places (the build fails if they differ):
   - `Version:` header in `utm-parameter-forwarder/utm-parameter-forwarder.php`
   - `UTMPF_VERSION` constant in the same file
   - `Stable tag:` in `utm-parameter-forwarder/readme.txt`
2. Add a `## [x.y.z] - YYYY-MM-DD` section to `CHANGELOG.md` (and a short entry in `readme.txt`).
3. Commit, then tag and push:
   ```bash
   git tag v1.1.0
   git push origin main v1.1.0
   ```
4. GitHub Actions publishes the release. Sites with the plugin see the update in *Dashboard > Updates* within about 6 hours (or immediately after *Check again*).

Automatic updates need the repository to be **public**, because the plugin deliberately stores no GitHub token. With a private repository, download the ZIP from the release and upload it under *Plugins > Add New > Upload Plugin*.

## Security notes

Security-relevant changes should be reviewed before tagging a release. Review reports are kept outside the repository (`local/`).

- PHP never reads request parameters; values are handled only in the browser.
- No database tables, AJAX/REST endpoints or cookies.
- Settings page requires `manage_options`, uses the WordPress Settings API (nonce + capability checks) and a strict whitelist sanitizer.
- The updater only accepts a release asset from this repository's own `releases/download/` URL with a strict `x.y.z` version.
