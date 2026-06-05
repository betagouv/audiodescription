(function (Drupal, once) {
  Drupal.behaviors.newsletterFormMessages = {
    attach(context) {
      for (const zone of once('newsletter-messages', '#newsletter-messages', context)) {
        const inner = zone.querySelector('[data-drupal-messages]');
        if (inner && inner.children.length > 0) {
          zone.focus();
        }
      }
    }
  };
})(Drupal, once);
