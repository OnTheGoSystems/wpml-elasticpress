# WPML ElasticPress integration

![OTGS CI](https://github.com/OnTheGoSystems/wpml-elasticpress/workflows/OTGS%20CI/badge.svg)

## The main goal

By default, ElasticPress indexes posts using a single, default language to analyze their content.

This has two main problems:
* As posts are indexed in the default language, they might appear in search results in that default language, and they might be missing in search results in their own language.
* As posts are indexed in the default language, syncronization can mess with features like stopwords and stemming, resulting in wrongly indexed data.

The purpose of this plugin is providing proper indexing for the content based on its own language.

To do so, this glue plugin appends two additional fields to indexed documents:
* `post_lang` stores all the languages where a post might appear as a search result. This includes the post own language, but also some additional languages in case your post type is set to display as translated when some translations are missing. Posts on non-translatable types store here the default language so they can appear on frontend search results.
* `post_unique_lang` stored the actual, single language of the each post. Posts on non-translatable types store here the default language.

## Additional CLI command

This plugin provides its own `wpml_elasticpress` WP CLI command that extends the `elasticpress` one. When using `wpml_elasticpress`, you can pass some extra attributes:

* `wp wpml_elasticpress sync` will index all your posts in their default language. The process runs in batches, and languages are indexed one after another.
* `wp wpml_elasticpress sync --post-type=book` will index posts of the type book in their own language, also in batches, one language at a time.
* `wp wpml_elasticpress sync --post-lang=de,it` will index all posts in German and Italian languages. Note the comma-separated list of language codes.
* `wp wpml_elasticpress sync --post-type=book --post-lang=it` will index only Italian items on the post type book.

## Dashboard indexing

This plugin will hook into the ElasticPress Sync dashboard, so ElasticPress can index the relevant content in its own WPML language.

To do so, indexing is split in batches per language. The sync log will show the progress when indexing in every language.

## Post save indexing

This plugin will ensure than your posts get synced using the right language when they are created or updated in the WordPress backend.

## Feature support

ElasticPress provides several features. We provide compatibility with some of them:
* [x] Post Search
* [ ] Instant Results
* [ ] Autosuggest
* [ ] Did You Mean
* [ ] WooCommerce
* [ ] Filters
* [x] Related Posts
* [ ] Custom Search Results
* [ ] Protected Content
* [ ] Documents

Additional compatibility with other features will be added in future releases.

## Building from source

After cloning this repository you need to generate the autoloader:
`composer dump-autoload`

This step isn't needed if you download a release instead.

## Changelog

### 2.0.5
- Fix a problem with a missing stemmer when creating an index for Thai content.
- Update the sync command synopsis to accept the latest elasticpress arguments.

### 2.0.4
- Improve the management of sites indices in Health stats.
- Cache cluster indices for better performance.
- Unsync documents when moved to a non indexable stati.
- Fix some undefined variables.

### 2.0.3
- Fix the compatibility with ElasticPress 5.0.0.

### 2.0.2
- Fix the compatibility with the stopwords analyzers on ElasticPress 4.7.0 or newer.
- Fix an error when deleting and trashing posts on PHP 8.0 or newer.

### 2.0.1
- Fix the name of the plugin main directory.

### 2.0.0
- **Breaking change**: introduce a different index per language.
- Fix `wpml_elasticpress` so it analiyzes posts in their own language when indexing them.
- Fix the dashboard syncing process so it analiyzes posts in their own language when indexing them.
- Fix the individual post sync-on-save process so it analiyzes the post in its own language. Update synced content on post trashed or deleted.
- Fix the custom fields indexing so language codes are not taken as stopwords.
- Bring support for the `Related posts` feature.

### 1.0.0

- Filter the search by language.
- Add `wpml_elasticpress` CLI command.
- Add `post-lang` parameter to CLI indexing command.
