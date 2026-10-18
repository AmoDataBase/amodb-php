# Changelog

## Unreleased

Align extension types with the live API.

- `moods` returns `source` (`measured`, `estimated` or `null`) and `results` with `name`, `avg` and `values` on a 0 to 10 scale.
- `soundtracks` returns `results` with albums and their `tracks`.
- `ratings` returns `{value, votes}` or `null` for imdb, kinopoisk, metacritic, rotten_tomatoes and tmdb.
- `wikidata` money fields use `value` and `currency`. Links carry `tmdb_id` and `media_type` for catalog titles.
- `showtimes` takes an optional country. Without it the reply lists `countries` with counts. With it, `results` holds the venues and their showtimes. The method is now on `tv` too.
- The reply shapes are documented as array shapes in the `@return` tags.
- `find()` documents the `kinopoisk_id` and `wikidata_id` sources and the full source list.
- Added `movies->latest()`, `movies->lists()`, `tv->latest()`, `tv->lists()`, `tv->translations()`, `tv->alternativeTitles()` and `tv->episodeGroups()`.
- Removed `tv->aggregateCredits()`. The API does not serve `aggregate_credits`.
- README lists the valid `append_to_response` names and the calls that are not offered.

## 0.1.0 - 2026-10-18

- Initial release
