/**
 * Get the CSP nonce from the meta tag for inline styles/scripts.
 * Note: Nonce only works for <style> elements, NOT for style attributes on elements.
 */
export function useCspNonce(): string | null {
  if (typeof document === 'undefined') {
    return null;
  }

  const nonceMeta = document.querySelector('meta[name="csp-nonce"]');
  return nonceMeta ? nonceMeta.getAttribute('content') : null;
}

/**
 * Apply dynamic styles to an element using a nonce-enabled <style> element.
 * This is CSP-compliant because it uses a <style> element with nonce,
 * rather than inline style attributes which are blocked by CSP.
 */
export function applyDynamicStyles(element: HTMLElement, styles: Record<string, string>): void {
  const id = element.id || `csp-styled-${Math.random().toString(36).slice(2, 9)}`;
  if (!element.id) {
    element.id = id;
  }

  const cssText = Object.entries(styles)
    .map(([prop, value]) => `${prop}: ${value}`)
    .join('; ');

  const styleElement = createStyleWithNonce(`#${id} { ${cssText} }`);
  styleElement.setAttribute('data-dynamic-style', id);

  // Remove any existing dynamic style for this element
  document.querySelectorAll(`style[data-dynamic-style="${id}"]`).forEach((el) => el.remove());

  document.head.appendChild(styleElement);
}

/**
 * Create a style element with nonce for dynamic styles.
 * This is the CSP-compliant way to add dynamic CSS.
 */
export function createStyleWithNonce(css: string): HTMLStyleElement {
  const style = document.createElement('style');
  const nonce = useCspNonce();

  if (nonce) {
    style.setAttribute('nonce', nonce);
  }

  style.textContent = css;
  return style;
}

/**
 * Apply CSS custom properties (variables) to an element.
 * CSS variables set via style attribute are generally CSP-compliant
 * because they don't execute code.
 */
export function setCssVariables(element: HTMLElement, variables: Record<string, string>): void {
  Object.entries(variables).forEach(([name, value]) => {
    element.style.setProperty(name, value);
  });
}
