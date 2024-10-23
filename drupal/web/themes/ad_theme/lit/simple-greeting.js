import { LitElement, html, css } from '/libraries/lit/lit-all.min.js';

export class SimpleGreeting extends LitElement {
  static properties = {
    name: { type: String }
  };

  constructor() {
    super();
    this.name = 'World';
  }

  static styles = css`
    :host {
      color: blue;
    }
  `;

  render() {
    return html`<p>Hello, ${this.name}!</p>`;
  }
}

customElements.define('simple-greeting', SimpleGreeting);
