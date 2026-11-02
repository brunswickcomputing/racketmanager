/**
 * Tests for admin/functions.js: namespace exposure and activaTab behavior.
 */
describe('Admin functions: activaTab and namespace exposure', () => {
  let container;
  let tabShowSpy;

  beforeAll(async () => {
    // Mock jQuery.fn.tab if not present
    tabShowSpy = jest.fn();
    jQuery.fn.tab = function (action) {
      if (action === 'show') {
        tabShowSpy(this);
      }
      return this;
    };

    window.ajaxurl = '/wp-admin/admin-ajax.php';
    window.ajax_var = { url: '/wp-admin/admin-ajax.php', ajax_nonce: 'test-nonce' };
    await import('../../../src/js/admin/functions.js');
  });

  beforeEach(() => {
    container = document.createElement('div');
    document.body.appendChild(container);
    tabShowSpy.mockClear();
  });

  afterEach(() => {
    document.body.removeChild(container);
  });

  it('exposes activaTab on the Racketmanager namespace and does not create bare globals', () => {
    expect(typeof window.Racketmanager).toBe('object');
    expect(typeof window.Racketmanager.activaTab).toBe('function');

    // Ensure no forbidden bare globals were attached to window / globalThis
    expect(window.activaTab).toBeUndefined();
    expect(globalThis.activaTab).toBeUndefined();
    expect(window.setIframeHeight).toBeUndefined();
    expect(globalThis.setIframeHeight).toBeUndefined();
  });

  it('switches matching tab buttons across nav styles when activaTab is called', () => {
    container.innerHTML = `
      <div class="navbar-nav">
        <button id="btn-nav" data-bs-target="#config">Config</button>
        <button data-bs-target="#other">Other</button>
      </div>
      <div class="nav-tabs">
        <button id="btn-tabs" data-bs-target="#config">Config</button>
      </div>
      <div class="nav-pills">
        <button id="btn-pills" data-bs-target="#config">Config</button>
      </div>
    `;

    window.Racketmanager.activaTab('config');

    expect(tabShowSpy).toHaveBeenCalledTimes(3);

    // Verify the buttons targeted by the calls
    const targetElements = tabShowSpy.mock.calls.map((call) => call[0].get(0));
    expect(targetElements).toContain(document.getElementById('btn-nav'));
    expect(targetElements).toContain(document.getElementById('btn-tabs'));
    expect(targetElements).toContain(document.getElementById('btn-pills'));
  });
});
