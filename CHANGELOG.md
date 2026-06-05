# Changelog

All notable changes to `linkskipper/sdk` are documented here. This project follows
[Semantic Versioning](https://semver.org) and [Keep a Changelog](https://keepachangelog.com).
The PHP and TypeScript SDKs are released in lockstep (same version = same features).

## [Unreleased]

## [0.1.0] - 2026-06-05
### Added
- Initial release: `resolve`, `resolveAndWait` (resolve + poll to a terminal state), `getJob`,
  `account`, `providers`.
- One typed exception class per API error code over a common base; `Timeout`, `JobFailed`, `Network`.
- Retry with bounded exponential backoff on network + 5xx + 429 (honors `Retry-After`).
- PSR-4, native curl transport with an optional PSR-18 adapter; zero hard dependencies.
