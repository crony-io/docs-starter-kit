import type { DirectiveBinding, ObjectDirective } from 'vue';

/**
 * Get the CSP nonce from the meta tag
 */
function getCspNonce(): string | null {
  if (typeof document === 'undefined') {
    return null;
  }
  const nonceMeta = document.querySelector('meta[name="csp-nonce"]');
  return nonceMeta ? nonceMeta.getAttribute('content') : null;
}

/**
 * Vue directive for CSP-compliant dynamic styles.
 * Instead of using inline style attributes (blocked by CSP),
 * this directive creates a nonce-enabled <style> element.
 *
 * Usage:
 * <div v-csp-style="{ '--my-var': value, 'color': 'red' }">
 */
export const vCspStyle: ObjectDirective<HTMLElement, Record<string, string | number>> = {
  mounted(el: HTMLElement, binding: DirectiveBinding<Record<string, string | number>>) {
    applyStyles(el, binding.value);
  },

  updated(el: HTMLElement, binding: DirectiveBinding<Record<string, string | number>>) {
    if (binding.value !== binding.oldValue) {
      applyStyles(el, binding.value);
    }
  },

  unmounted(el: HTMLElement) {
    removeStyles(el);
  },
};

function applyStyles(el: HTMLElement, styles: Record<string, string | number> | undefined): void {
  if (!styles) {
    return;
  }

  // Generate or get element ID
  const id = el.id || `csp-${Math.random().toString(36).slice(2, 9)}`;
  if (!el.id) {
    el.id = id;
  }

  // Build CSS text
  const cssText = Object.entries(styles)
    .map(([prop, value]) => {
      // Convert camelCase to kebab-case for CSS properties
      const cssProp = prop.startsWith('--') ? prop : prop.replace(/([A-Z])/g, '-$1').toLowerCase();
      return `${cssProp}: ${value}`;
    })
    .join('; ');

  // Remove existing style element for this element
  removeStyles(el);

  // Create new style element with nonce
  const styleEl = document.createElement('style');
  const nonce = getCspNonce();
  if (nonce) {
    styleEl.setAttribute('nonce', nonce);
  }
  styleEl.setAttribute('data-csp-style-for', id);
  styleEl.textContent = `#${id} { ${cssText} }`;

  document.head.appendChild(styleEl);
}

function removeStyles(el: HTMLElement): void {
  if (el.id) {
    document.querySelectorAll(`style[data-csp-style-for="${el.id}"]`).forEach((s) => s.remove());
  }
}

export default vCspStyle;
