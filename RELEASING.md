# Releasing

`linkskipper/sdk` (Packagist) and `@linkskipper/sdk` (npm) are versioned in LOCKSTEP: the same
SemVer version ships for both whenever the API surface changes, so `sdk-php@X.Y.Z` and
`sdk-js@X.Y.Z` always expose the same features.

## Semantic Versioning
- MAJOR - a backward-incompatible API change.
- MINOR - backward-compatible new functionality.
- PATCH - backward-compatible bug fixes.

Pre-1.0 (`0.x`): the public API may still change in MINOR releases; cut `1.0.0` when it is stable.
Commit messages use Conventional Commits (`feat:` -> MINOR, `fix:` -> PATCH, `feat!:` /
`BREAKING CHANGE:` -> MAJOR) so the bump is unambiguous.

## Cutting release `X.Y.Z`
1. In BOTH repos move the `Unreleased` notes under a new `## [X.Y.Z] - <date>` heading in
   `CHANGELOG.md`.
2. There is intentionally NO `version` field in `composer.json` - Packagist derives the version from
   the git tag. (JS bumps its `package.json` separately.)
3. Commit, then tag each repo with the SAME tag and push it:
   `git tag vX.Y.Z && git push origin vX.Y.Z`
4. Packagist: auto-updates from the new tag via the GitHub webhook set up when the package was
   submitted. Nothing else to do.
5. npm: the JS repo's `release` workflow publishes `@linkskipper/sdk` on the matching `v*` tag.

Never move or reuse a published tag - cut a new PATCH instead.
