(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.barraePesquisaArtigosAutocomplete = {
    attach: function (context, settings) {
      var elements = once('article-search-autocomplete', 'input[name="keyword"]', context);
      
      elements.forEach(function (input) {
        var $input = $(input);
        var $dropdown = $('<div class="article-search-dropdown"></div>');
        
        // Add wrapper class to parent container
        $input.parent().addClass('article-search-autocomplete-wrapper');
        $input.parent().append($dropdown);
        
        var searchTimeout;
        
        $input.on('keyup', function() {
          var query = $(this).val().trim();
          
          clearTimeout(searchTimeout);
          
          if (query.length < 2) {
            $dropdown.hide().empty();
            return;
          }
          
          searchTimeout = setTimeout(function() {
            $.ajax({
              url: '/jsonapi/node/article',
              data: { 
                'filter[status]': 1,
                'filter[title][operator]': 'CONTAINS',
                'filter[title][value]': query,
                'fields[node--article]': 'title,path,body,created',
                'page[limit]': 5,
                'sort': '-created'
              },
              success: function(response) {
                $dropdown.empty();
                
                if (response.data && response.data.length > 0) {
                  response.data.forEach(function(article) {
                    var title = article.attributes.title;
                    var path = article.attributes.path ? article.attributes.path.alias : '/node/' + article.attributes.drupal_internal__nid;
                    var summary = article.attributes.body ? (article.attributes.body.summary || article.attributes.body.value || '') : '';
                    
                    var $item = $('<a href="' + path + '" class="autocomplete-item">' +
                      '<div class="autocomplete-title">' + title + '</div>' +
                      '<div class="autocomplete-summary">' + summary.substring(0, 100).replace(/<[^>]*>/g, '') + '...</div>' +
                      '</a>');
                    $dropdown.append($item);
                  });
                  
                  var totalCount = response.data.length;
                  var $showAll = $('<div class="autocomplete-footer">' +
                    '<a href="/blog?keyword=' + encodeURIComponent(query) + '">Ver todos os resultados (' + totalCount + '+)</a>' +
                    '</div>');
                  $dropdown.append($showAll);
                  
                  $dropdown.show();
                } else {
                  $dropdown.html('<div class="autocomplete-no-results">Nenhum artigo encontrado</div>').show();
                }
              }
            });
          }, 300);
        });
        
        // Fechar dropdown ao clicar fora
        $(document).on('click', function(e) {
          if (!$(e.target).closest('.article-search-autocomplete-wrapper').length) {
            $dropdown.hide();
          }
        });
      });
    }
  };
})(jQuery, Drupal, once);
