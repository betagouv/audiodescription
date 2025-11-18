(function (Drupal, once) {
  Drupal.behaviors.iframeResponsive = {
    attach(context) {

      // @Todo : au resize (vers le mobile) -> recalculer la bonne largeur (trouver pourquoi ça ne marche pas en l'état)
      function adjustIframeSize(iframe) {
        // Reinit iframe width.
        iframe.style.width = '';

        const iframeWidth = parseInt(iframe.dataset.width);
        const iframeHeight = parseInt(iframe.dataset.height);

        // Find parent container.
        const container = iframe.closest('.pg-iframe.fr-container');

        console.log(iframe);
        console.log(container);

        if (!container) {
          console.warn('Container .pg-iframe.fr-container non trouvé pour', iframe.id);
          return;
        }

        // Get container width (without padding).
        const containerStyles = window.getComputedStyle(container);
        console.log(iframe.style.width);
        const containerWidth = container.clientWidth -
          parseFloat(containerStyles.paddingLeft) -
          parseFloat(containerStyles.paddingRight);

        console.log(containerWidth);
        console.log(iframeWidth);

        // According to containerWitdh, define iframe width.
        if (containerWidth < iframeWidth) {
          console.log('container');
          iframe.style.width = containerWidth + 'px';
        } else {
          console.log('iframe');
          iframe.style.width = iframeWidth + 'px';
        }

        console.log(iframe.style.width);

        // Always use same height.
        iframe.style.height = iframeHeight + 'px';
      }

      const iframes = once('iframe-responsive', 'iframe[data-width][data-height]', context);

      iframes.forEach(function(iframe) {
        iframe.onload = function() {
          adjustIframeSize(iframe);
        };

        if (iframe.contentWindow) {
          adjustIframeSize(iframe);
        }
      });

      // On resize.
      once('iframe-responsive-resize-listener', 'body', context).forEach(function() {
        window.addEventListener('resize', function() {
          const allIframes = document.querySelectorAll('iframe[data-width][data-height]');

          allIframes.forEach(function(iframe) {
            adjustIframeSize(iframe);
          });
        });
      });

    }
  };
})(Drupal, once);
