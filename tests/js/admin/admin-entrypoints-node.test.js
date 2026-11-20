/**
 * @jest-environment node
 */

describe('Admin entrypoints in Node (SSR / non-browser environment)', () => {
  it('handles functions.js when document is undefined', async () => {
    const origJQuery = global.jQuery;
    const origWindow = global.window;
    try {
      global.window = {};
      global.jQuery = { fn: {} };
      jest.resetModules();
      await import('../../../src/js/admin/functions.js');
      expect(typeof global.window.Racketmanager.activaTab).toBe('function');
      const activaTab = global.window.Racketmanager.activaTab;
      delete global.window;
      expect(() => activaTab('tab-name')).not.toThrow();
    } finally {
      if (origJQuery !== undefined) global.jQuery = origJQuery;
      else delete global.jQuery;
      if (origWindow !== undefined) global.window = origWindow;
      else delete global.window;
    }
  });

  it('handles ajax.js entrypoint when document is undefined', async () => {
    const origWindow = global.window;
    try {
      global.window = {};
      jest.resetModules();
      await expect(import('../../../src/js/admin/ajax.js')).resolves.toBeDefined();
    } finally {
      if (origWindow !== undefined) global.window = origWindow;
      else delete global.window;
    }
  });

  it('handles draggable.js entrypoint when document is undefined', async () => {
    jest.resetModules();
    await expect(import('../../../src/js/admin/draggable.js')).resolves.toBeDefined();
  });

  it('handles utils.js entrypoint when document is undefined', async () => {
    const origWindow = global.window;
    try {
      global.window = {};
      jest.resetModules();
      await expect(import('../../../src/js/admin/utils.js')).resolves.toBeDefined();
    } finally {
      if (origWindow !== undefined) global.window = origWindow;
      else delete global.window;
    }
  });

  it('handles admin-ajax.js SSR window guards in Node environment', async () => {
    expect(typeof window).toBe('undefined');
    const adminAjax = await import('../../../src/js/admin/features/ajax/admin-ajax.js');
    expect(adminAjax).toBeDefined();

    jest.useFakeTimers();
    const mockNotify = {
      style: { display: '' },
      className: '',
      textContent: '',
      innerHTML: '',
      classList: { remove: jest.fn(), add: jest.fn() },
    };
    const mockDoc = {
      getElementById: () => mockNotify,
    };
    global.document = mockDoc;
    let savedAjaxOpts;
    const mockAjax = jest.fn((opts) => {
      savedAjaxOpts = opts;
    });

    try {
      // test getJQueryAjax SSR branch when window is undefined
      delete global.window;
      expect(() => adminAjax.setTournamentOpenDate()).not.toThrow();
      expect(() => adminAjax.sendFixtures()).not.toThrow();

      // test emailConstitution SSR branch
      global.window = {
        jQuery: { ajax: mockAjax },
        ajax_var: { url: '/admin-ajax.php', ajax_nonce: 'xyz' },
      };
      adminAjax.emailConstitution(null, 1);
      delete global.window;
      expect(savedAjaxOpts).toBeDefined();
      savedAjaxOpts.success({ data: 'Email sent' });
      expect(mockNotify.style.display).toBe('');
      jest.advanceTimersByTime(10000);
      expect(mockNotify.style.display).toBe('none');

      // test notifyTeams SSR branch
      global.window = {
        jQuery: { ajax: mockAjax },
        ajax_var: { url: '/admin-ajax.php', ajax_nonce: 'xyz' },
      };
      adminAjax.notifyTeams(10);
      delete global.window;
      savedAjaxOpts.success({ data: { message: 'Teams notified' } });
      expect(mockNotify.style.display).toBe('');
      jest.advanceTimersByTime(10000);
      expect(mockNotify.style.display).toBe('none');

      // test saveAddPoints SSR branch
      global.window = {
        jQuery: { ajax: mockAjax },
        ajax_var: { url: '/admin-ajax.php', ajax_nonce: 'xyz' },
      };
      const reloadPage = jest.fn();
      adminAjax.saveAddPoints('3', 10, reloadPage);
      delete global.window;
      savedAjaxOpts.success();
      expect(reloadPage).toHaveBeenCalled();

      // test setTournamentOpenDate and fadeOutNotification SSR branches
      mockNotify.style.display = 'block';
      global.window = {
        jQuery: { ajax: mockAjax },
        ajax_var: { url: '/admin-ajax.php', ajax_nonce: 'xyz' },
      };
      adminAjax.setTournamentOpenDate();
      delete global.window;
      savedAjaxOpts.success({ data: { msg: 'Date updated', dateStart: '2026-01-01', dateEnd: '2026-02-01' } });
      if (savedAjaxOpts.complete) savedAjaxOpts.complete();
      expect(mockNotify.style.display).toBe('');
      expect(mockNotify.classList.add).toHaveBeenCalledWith('alert--success');
      jest.advanceTimersByTime(10000);
      expect(mockNotify.style.display).toBe('none');
    } finally {
      delete global.document;
      delete global.window;
      jest.useRealTimers();
    }
  });
});
