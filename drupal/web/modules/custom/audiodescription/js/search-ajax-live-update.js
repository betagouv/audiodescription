(function (Drupal) {
  'use strict';

  Drupal.behaviors.searchAjaxUpdateLive = {
    attach: function (context, settings) {

      const announceEl = document.getElementById('audiodesc-search-page--results-live');
      const resultsHeading = document.getElementById('audiodesc-search-page--results');

      if (announceEl && resultsHeading) {
        announceEl.textContent = resultsHeading.textContent;
      }
    }
  };

})(Drupal);
