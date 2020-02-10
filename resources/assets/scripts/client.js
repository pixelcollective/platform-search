import algoliasearch from 'algoliasearch'
import instantsearch from 'instantsearch.js'
import { searchBox, hits, poweredBy, configure, panel } from 'instantsearch.js/es/widgets'

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
  stalledSearchDelay: 500,
  routing: true,
});

const widgets = [
  searchBox({
    container: '#searchbox',
    limit: 5,
    placeholder: 'Search site content',
    showLoadingIndicator: true,
    showSubmit: true,
  }),
  hits({
    container: '#hitsbox',
    templates: {
      item: `
        <article>
          {{#image}}
            <div class="image-container">
              <a href="{{ url }}">
                <img src="{{ image }}" />
              </a>
            </div>
          {{/image}}

          <div>
            <a href="{{ url }}">
              <strong>
                {{#helpers.highlight}}
                  {
                    "attribute": "title",
                    "highlightedTagName": "mark"
                  }
                {{/helpers.highlight}}
              </strong>
            </a>

            <div>
              {{#helpers.highlight}}
                {
                  "attribute": "excerpt",
                  "highlightedTagName": "mark"
                }
              {{/helpers.highlight}}
            </div>
          </div>
        </article>
      `,
      empty: `No results for <q>{{ query }}</q>`,
    },
  }),
  poweredBy({
    container: '#poweredbybox',
  }),
  configure({
    attributesToSnippet: [
      'content',
    ],
  }),
];

document.querySelector('#algolia-search') && (() => {
  search.addWidgets(widgets)
    && search.start();
})();
