# WPML ElasticPress integration

![OTGS CI](https://github.com/OnTheGoSystems/wpml-elasticpress/workflows/OTGS%20CI/badge.svg)

## The main goal

When you activate ElasticPress plugin without our plugin, you will see posts from all languages on the frontend.
This glue plugin has been created in order to support language information in search results.
It filters posts according to the current language.

To achieve this goal, we append an additional `post_lang` field to Elasticsearch document. 
It lets us filter them later.

## Additional parameter in ElasticPress CLI command

We also added the extra `--post-lang` argument in CLI indexing command.
You can use it in this way:

`wp wpml_elasticpress sync --post-type=post --post-lang=de` which will index only German posts.

## Building from source

After cloning this repository you need to generate the autoloader:
`composer dumpautoload`

This step isn't needed if you download a release instead.

## Changelog

### 1.0.0

- Filter the search by language.
- Add `post-lang` parameter to CLI indexing command.
