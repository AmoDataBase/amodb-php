# Contributing

Thanks for helping. Keep changes small and focused.

1. Open an issue first for anything larger than a bug fix or a doc fix.
2. Fork the repository and create a branch from `main`.
3. Run the checks before you open a pull request:

```sh
composer install
composer test
```

4. Add or update tests for the behavior you change.
5. Write commit messages in the imperative mood, one short line, no trailing period.

Text in docs and comments: short, concrete sentences, sentence case headings, no long dashes.

Never commit API keys. Use environment variables and `.env` files that are ignored by git.

## Releasing

For maintainers.

1. Update `CHANGELOG.md`.
2. Commit, then tag the commit and push the tag: `git tag v0.2.0 && git push origin v0.2.0`.
3. The Release workflow runs the tests and creates the GitHub release.

Composer installs from Packagist, which reads new tags from GitHub through a webhook. One time setup:

1. Submit `https://github.com/AmoDataBase/amodb-php` on Packagist as the package `amodb/amodb-php`.
2. In the GitHub repository open Settings, then Webhooks, then Add webhook.
3. Set the payload URL to `https://packagist.org/api/github?username=YOUR_PACKAGIST_USER`, the content type to `application/json`, the secret to your Packagist API token and the event to push only.

After that every pushed tag appears on Packagist within a minute. The version comes from the tag, `composer.json` has no `version` field.

By contributing you agree that your work is released under the MIT license of this repository.
