import { LitElement, html, css } from 'lit';
import { classMap } from 'lit/directives/class-map.js';
import Fuse from 'fuse.js'

// Gestionnaire global pour coordonner toutes les instances
class RichSelectManager {
  constructor() {
    this.instances = new Set();
  }

  register(instance) {
    this.instances.add(instance);
  }

  unregister(instance) {
    this.instances.delete(instance);
  }

  closeAllExcept(exceptInstance) {
    this.instances.forEach(instance => {
      if (instance !== exceptInstance && instance.visible) {
        instance.close();
      }
    });
  }
}

// Instance unique du gestionnaire
const manager = new RichSelectManager();

export class RichSelect extends LitElement {
  static properties = {
    title: { type: String },
    name: { type: String },
    plural_title: { type: String },
    singular_title: { type: String },
    is_female: { type: Boolean },
    visible: { type: Boolean },
    options: { type: String },
    selected: { type: String },
    selectedOptions: { type: Array},
    currentOptions: { type: Array},
    fuseOptions: { type: Object },
    fuse: {type: Object},
    btnSelectAll: { type: Object}
  };

  // Disable shadow DOM.
  createRenderRoot() {
    return this;
  }

  connectedCallback() {
    super.connectedCallback();

    this.currentOptions = this._formatOptions();
    this.selectedOptions = Object.keys(JSON.parse(this.selected));

    this.fuse = new Fuse(this.currentOptions, this.fuseOptions);

    this.btnSelectAll = {
      'icon': 'fr-icon-check-line',
      'text': 'Tout sélectionner'
    }

    if (this.selected !== '[]') {
      this.btnSelectAll = {
        'icon': 'fr-icon-close-circle-line',
        'text': 'Tout désélectionner'
      }
    }

    // Enregistrer cette instance auprès du gestionnaire
    manager.register(this);

    // Ajouter un écouteur global pour les clics à l'extérieur
    this._handleOutsideClick = this._handleOutsideClick.bind(this);
    document.addEventListener('click', this._handleOutsideClick);
  }

  disconnectedCallback() {
    super.disconnectedCallback();

    // Désinscrire cette instance
    manager.unregister(this);

    // Retirer l'écouteur de clics
    document.removeEventListener('click', this._handleOutsideClick);
  }

  constructor() {
    super();
    this.visible = false;
    this.icon = 'fr-icon-arrow-down-s-line';

    this.fuseOptions = {
      isCaseSensitive: false,
      threshold: 0.6,
      distance: 0,
      useExtendedSearch: true,
      keys: [
        'value'
      ]
    };
  }

  get pronoun() {
    return this.is_female ? 'une' : 'un';
  }

  get btnLabel() {
    let length = this.selectedOptions.length;
    if (length === 1) {
      if (this.is_female) {
        return '1 ' + this.singular_title + ' sélectionnée';
      }
      return '1 ' + this.singular_title + ' sélectionné';
    }

    if (length > 1) {
      if (this.is_female) {
        return length + ' ' + this.plural_title + ' sélectionnées';
      }
      return length + ' ' + this.plural_title + ' sélectionnés';
    }

    return 'Sélectionner' + ' ' + this.pronoun + ' ' + this.singular_title;
  }

  _formatOptions() {
    return Object.entries(JSON.parse(this.options))
      .map(([key, value]) => ({
        key: key,
        value: value
      }))
      .sort((a, b) => a.value.localeCompare(b.value));
  }

  _handleCheckboxChange(e) {
    const value = e.target.value;

    if (e.target.checked) {
      this.selectedOptions.push(value);
    } else {
      this.selectedOptions = this.selectedOptions.filter((v) => v !== value);
    }
  }

  _isSelected(key) {
    if (this.selectedOptions.length > 0) {
      return this.selectedOptions.includes(key);
    }

    return false;
  }

  _onSearch(e) {
    if (e.target.value.length === 0) {
      this.currentOptions = this._formatOptions();
      return;
    }

    // Extended search : add ' for include match
    const results = this.fuse.search("'" + e.target.value);

    let ar = [];
    for (let result of results) {
      ar.push({'key': result.item.key, 'value': result.item.value})
    }

    this.currentOptions = ar;
  }

  _selectAll(e) {
    if (this.btnSelectAll.icon == 'fr-icon-close-circle-line') {
      this.selectedOptions = [];

      this.btnSelectAll = {
        'icon': 'fr-icon-check-line',
        'text': 'Tout sélectionner'
      }
    } else {
      for(const option of this.currentOptions) {
        this.selectedOptions = [...this.currentOptions.map(option => option.key)];
      }

      this.btnSelectAll = {
        'icon': 'fr-icon-close-circle-line',
        'text': 'Tout désélectionner'
      }
    }

    this.requestUpdate();
  }

  // Nouvelle méthode pour gérer les clics à l'extérieur
  _handleOutsideClick(event) {
    // Si le dropdown n'est pas visible, ne rien faire
    if (!this.visible) return;

    // Vérifier si le clic est à l'intérieur du composant
    const isClickInside = this.contains(event.target);

    // Si le clic est à l'extérieur, fermer le dropdown
    if (!isClickInside) {
      this.close();
    }
  }

  // Nouvelle méthode pour fermer proprement le dropdown
  close() {
    this.visible = false;
    this.icon = 'fr-icon-arrow-down-s-line';
    this.requestUpdate();
  }

  // Nouvelle méthode pour ouvrir proprement le dropdown
  open() {
    this.visible = true;
    this.icon = 'fr-icon-arrow-up-s-line';
    this.requestUpdate();
  }

  render() {
    const classes = {
      'ad-dropdown': true,
      'ad-dropdown--visible': this.visible,
      'ad-rich-select__dropdown': true,
    };

    return html`
      <div
        class="ad-rich-select"
        data-drupal-selector="edit-${this.plural_title}"
        data-once="drupal-ajax"
        id="edit-partner"
      >
        <button
          type="button"
          class="ad-rich-select__btn-control-dropdown fr-btn--icon-right ${this.icon}"
          data-fr-opened="false"
          aria-controls="ad-${this.plural_title}"
          aria-expanded="${this.visible}"
          @click="${this._toggleDropdown}">
          ${this.btnLabel}
        </button>

        <div
          id="ad-${this.plural_title}"
          class=${classMap(classes)}
          aria-hidden="${!this.visible}"
        >

          <fieldset class="fr-fieldset" id="checkboxes-${this.plural_title}" aria-labelledby="checkboxes-legend-${this.plural_title} checkboxes-messages-${this.plural_title}">
            <legend class="fr-fieldset__legend--regular fr-fieldset__legend" id="checkboxes-legend-${this.plural_title}">
              Liste des ${this.plural_title}
            </legend>

            <div class="ad-rich-select__dropdown-options">
              ${this.currentOptions.map(
      (item) => html`
              <div class="fr-fieldset__element">
                <div class="fr-checkbox-group">
                  <input
                    name="${this.name}[${item.key}]"
                    id="edit-${this.name}-${item.key}"
                    type="checkbox"
                    aria-describedby="checkboxes-${this.plural_title}-messages-${item.key}"
                    value="${item.key}"
                    ?checked="${this._isSelected(item.key)}"
                    @change="${this._handleCheckboxChange}"
                    data-drupal-selector="edit-${this.name}-${item.key}"
                  >
                  <label class="fr-label" for="edit-${this.name}-${item.key}">
                    ${item.value}
                  </label>
                  </div>
                </div>
              </div>
            `
    )}
            </div>
          </fieldset>
        </div>

      </div>
    `;
  }

  _toggleDropdown(e) {
    // Empêcher la propagation pour éviter de déclencher le _handleOutsideClick immédiatement
    e.stopPropagation();

    if (this.visible) {
      // Si on ferme, juste fermer
      this.close();
    } else {
      // Si on ouvre, fermer les autres d'abord
      manager.closeAllExcept(this);
      this.open();
    }
  }
}

customElements.define('rich-select', RichSelect);
