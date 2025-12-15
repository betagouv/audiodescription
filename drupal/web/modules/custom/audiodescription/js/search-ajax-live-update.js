(function (Drupal) {
  'use strict';

  Drupal.behaviors.searchAjaxUpdateLive = {
    attach: function (context, settings) {

      const announceEl = document.getElementById('ad-search-page--results-live');
      const resultsHeading = document.getElementById('ad-search-page--results');

      if (announceEl && resultsHeading) {
        announceEl.textContent = resultsHeading.textContent;
      }
    }
  };

})(Drupal);
