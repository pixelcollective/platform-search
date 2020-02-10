# Algolia search integration

This is just meant to be a starting point.

## Required definitions in `wp-config.php`:

- `ALGOLIA_APP_ID`
- `ALGOLIA_ADMIN_API_KEY`
- `ALGOLIA_SEARCH_API_KEY`

## Markup to add to theme:

```html
<div id="algolia-search">
  <aside id="searchbox"></aside>
  <aside id="tagsbox"></aside>
  <aside id="hitsbox"></aside>
</div>
```
