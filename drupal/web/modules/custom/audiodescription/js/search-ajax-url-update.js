(function ($, Drupal) {
  Drupal.AjaxCommands.prototype.searchAjaxUpdateUrl = function (ajax, response, status) {
    const form = document.getElementById('filter-movie-search-form');

    if (form) {
      const formData = new FormData(form);
      formData.delete('form_build_id');
      formData.delete('form_token');
      formData.delete('form_id');
      const params = new URLSearchParams();

      // Preserve the validated search keyword from the current URL (before pushState).
      const currentSearch = new URLSearchParams(window.location.search).get('search');
      if (currentSearch) {
        params.append('search', currentSearch);
      }

      for (const [name, value] of formData.entries()) {
        params.append(name, value);
      }

      const path = window.location.pathname;
      let newUrl = path;
      if (params.toString()) {
        newUrl += '?' + params.toString();
      }

      window.history.pushState({}, '', newUrl);
    }
  };
  Drupal.AjaxCommands.prototype.searchAjaxUpdateTitle = function (ajax, response) {
    document.title = response.title;
  };
})(jQuery, Drupal);
