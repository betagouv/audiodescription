(function ($, Drupal) {
  Drupal.AjaxCommands.prototype.searchAjaxUpdateUrl = function (ajax, response, status) {

    const form = document.getElementById('filter-movie-search-form');

    if (form) {
      const formData = new FormData(form);
      formData.delete('form_build_id');
      formData.delete('form_token');
      formData.delete('form_id');
      const params = new URLSearchParams();

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
})(jQuery, Drupal);
