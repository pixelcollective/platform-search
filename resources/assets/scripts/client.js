import algoliasearch from 'algoliasearch'
import instantsearch from 'instantsearch.js'
import { searchBox, refinementList, hits } from 'instantsearch.js/es/widgets'

/**
 * Build Algolia search
 */
const search = instantsearch({
  indexName: settings.indexName,
  searchClient: algoliasearch(
    settings.id,
    settings.key,
  ),
  searchFunction(helper) {
    helper.state.query
      && helper.search();
  },
});

const widgets = [
  refinementList({
    container: '#tagsbox',
    attribute: 'tags',
  }),
  searchBox({
    container: '#searchbox',
    limit: 5,
    showMore: true,
  }),
  hits({
    container: '#hitsbox',
    templates: {
      item: `
        <article>
          <a href="{{ url }}">
            <strong>
              {{#helpers.highlight}}
                { "attribute": "title", "highlightedTagName": "mark" }
              {{/helpers.highlight}}
            </strong>
          </a>
          {{#content}}
            <p>{{#helpers.snippet}}{ "attribute": "content", "highlightedTagName": "mark" }{{/helpers.snippet}}</p>
          {{/content}}
        </article>
      `
    },
  }),
]

search.addWidgets(widgets);

document.querySelector('#algolia-search')
  && search.start();
