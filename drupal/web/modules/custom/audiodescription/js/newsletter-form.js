(function (Drupal, once) {
  Drupal.behaviors.newsletterFormMessages = {
    attach(context) {
      for (const zone of once('newsletter-messages', '#newsletter-messages', context)) {
        if (zone.children.length > 0) {
          zone.focus();
        }
      }
    }
  };
})(Drupal, once);
