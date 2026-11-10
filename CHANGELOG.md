# Changelog

All notable changes to this plugin are documented here.
The project follows [Semantic Versioning](https://semver.org/): MAJOR.MINOR.PATCH.

## [1.0.0] - 2026-11-10

### Added
- Forward UTM parameters (`utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, `utm_id`) and click IDs (`gclid`, `gbraid`, `wbraid`, `gad_source`, `fbclid`, `li_fat_id`) to all internal links.
- Optional per-tab memory (sessionStorage) so parameters survive navigation without them.
- Settings page (Settings > UTM Forwarder) to edit the parameter list.
- Automatic updates from GitHub releases.
