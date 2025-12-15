import { LitElement, html, css } from 'lit';
import { classMap } from 'lit/directives/class-map.js';

// Global manager for all rich select.
class PartnersSelectManager {
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

// Unique manager.
const manager = new PartnersSelectManager();

export class PartnersSelect extends LitElement {
  static properties = {

  };

  // Disable shadow DOM.
  createRenderRoot() {
    return this;
  }

  connectedCallback() {
    super.connectedCallback();

    // Save this PartnersSelect in manager.
    manager.register(this);
  }

  disconnectedCallback() {
    super.disconnectedCallback();

    manager.unregister(this);
  }

  constructor() {
    super();
  }

  render() {

    return html`
      <div>
        <p>Mes abonnements</p>
      </div>
    `;
  }
}

customElements.define('partners-select', PartnersSelect);
