(function (Drupal, once) {
  Drupal.behaviors.backButton = {
    attach(context) {
      for (const el of once('back-button', '#back-button', context)) {
        const referrer = document.referrer;
        const currentHost = window.location.host;

        const isSameSite = referrer && new URL(referrer).host === currentHost;

        if (!isSameSite) {
          el.textContent = 'Retour à la page d’accueil';
        }

        el.addEventListener('click', (e) => {
          e.preventDefault();

          if (isSameSite) {
            if (window.location.hash === '#top') {
              window.history.go(-2);
            } else {
              window.history.back();
            }
          } else {
            window.location.href = '/';
          }
        });
      }
    }
  };
})(Drupal, once);
