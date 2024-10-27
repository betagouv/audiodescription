import { LitElement, html, css } from '/libraries/lit/lit-all.min.js';

export class RichSelect extends LitElement {
  static properties = {
    label: { type: String }
  };

  constructor() {
    super();
    this.label = '';
  }

  static styles = css`
    :host {
      color: seagreen;
    }
  `;

  render() {
    return html`
      <div>
        <button class="fr-btn"  data-fr-opened="false" aria-controls="fr-modal-3">
          ${this.label}
        </button>
        <dialog aria-labelledby="fr-modal-title-modal-3" role="dialog" id="fr-modal-3" class="fr-modal">
          <div class="rich-select-widget">
            <div class="fr-container fr-container--fluid fr-container-md">
              <div class="fr-grid-row fr-grid-row--center">
                <div class="fr-col-12 fr-col-md-4">
                  <div class="fr-modal__body">
                    <div class="fr-modal__header">
                      <button class="fr-btn--close fr-btn" title="Fermer la fenêtre modale" aria-controls="fr-modal-3">Fermer</button>
                    </div>
                    <div class="fr-modal__content">
                      <h1 id="fr-modal-title-modal-3" class="fr-modal__title"><span class="fr-icon-arrow-right-line fr-icon--lg"></span>Titre de la modale SM</h1>
                      <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius tortor nibh, sit amet tempor nibh finibus et. Aenean eu enim justo. Vestibulum aliquam hendrerit molestie. Mauris malesuada nisi sit amet augue accumsan tincidunt. Maecenas tincidunt, velit ac porttitor pulvinar, tortor eros facilisis libero, vitae commodo nunc quam et ligula. Ut nec ipsum sapien. Interdum et malesuada fames ac ante ipsum primis in faucibus. Integer id nisi nec nulla luctus lacinia non eu turpis. Etiam in ex imperdiet justo tincidunt egestas. Ut porttitor urna ac augue cursus tincidunt sit amet sed orci.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </dialog>
      </div>
    `;
  }
}

customElements.define('rich-select', RichSelect);
