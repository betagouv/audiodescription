import { LitElement, html, css, classMap } from '/libraries/lit/lit-all.min.js';

export class RichSelect extends LitElement {
  static properties = {
    label: { type: String },
    visible: { type: Boolean }
  };

  constructor() {
    super();
    this.visible = false;
  }

  // Disable shadow DOM.
  createRenderRoot() {
    return this;
  }

  render() {
    const classes = {
      'ad-dropdown': true,
      'ad-dropdown--visible': this.visible
    };

    return html`
      <div>
        <button type="button" class="fr-btn" data-fr-opened="false" aria-controls="ad-${this.label}" @click="${this._toggleDropdown}">
          ${this.label} ${this.visible}
        </button>
        <div id="ad-${this.label}" class=${classMap(classes)} aria-hidden="${!this.visible}">
          <h1>Coucou ${this.label}</h1>
          <input type="text" name="prout[]" />
        </div>

      </div>
    `;
  }

  _toggleDropdown(e) {
    this.visible = !this.visible;
  }
}

customElements.define('rich-select', RichSelect);
