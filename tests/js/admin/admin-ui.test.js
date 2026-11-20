/**
 * Tests for admin UI widgets: player club popovers, go-top, sortable tables, and misc setup.
 */
import {
  showPlayerClubs,
  initTooltips,
  initPopovers,
  initColorpicker,
  initGoTop,
  initStandingsSortable,
  initMiscellaneousUI,
  initAdminUI,
} from '../../../src/js/admin/features/ui/admin-ui.js';
import { createJQueryStub, respondWithSuccess, respondWithError } from './support/jquery-stub.js';

describe('Admin feature: admin-ui', () => {
  const originalScrollTo = window.scrollTo;
  let container;
  let stub;
  let scrollPosition = 0;

  beforeAll(() => {
    Object.defineProperty(window, 'scrollY', { configurable: true, get: () => scrollPosition });
    initGoTop();
  });

  beforeEach(() => {
    window.ajax_var = { url: '/wp-admin/admin-ajax.php', ajax_nonce: 'test-nonce-123' };
    stub = createJQueryStub();
    window.jQuery = stub.jq;

    container = document.createElement('div');
    document.body.appendChild(container);
  });

  afterEach(() => {
    document.body.removeChild(container);
    scrollPosition = 0;
    delete document.documentElement.scrollTop;
    window.scrollTo = originalScrollTo;
    jest.clearAllMocks();
  });

  describe('showPlayerClubs', () => {
    const makeLink = () => {
      const link = document.createElement('a');
      link.id = 'player_12';
      return link;
    };

    it('posts the player id, action, and nonce with a synchronous request', () => {
      respondWithSuccess(stub.jq, { data: [] });
      showPlayerClubs(makeLink());

      expect(stub.jq.ajax).toHaveBeenCalledTimes(1);
      const opts = stub.jq.ajax.mock.calls[0][0];
      expect(opts.url).toBe('/wp-admin/admin-ajax.php');
      expect(opts.async).toBe(false);
      expect(opts.data).toEqual({
        player: '12',
        action: 'racketmanager_get_player_clubs',
        security: 'test-nonce-123',
      });
    });

    it('lists club names separated by line breaks', () => {
      respondWithSuccess(stub.jq, { data: [{ name: 'Club A' }, { name: 'Club B' }] });
      expect(showPlayerClubs(makeLink())).toBe('Club A<br />Club B<br />');
    });

    it('reports that the player is not linked to clubs when the list is empty', () => {
      respondWithSuccess(stub.jq, { data: [] });
      expect(showPlayerClubs(makeLink())).toBe('Player not linked to clubs');
    });

    it('formats error message and reason list from responseJSON', () => {
      respondWithError(stub.jq, { responseJSON: { data: ['Not allowed', ['Reason 1', 'Reason 2']] } });
      expect(showPlayerClubs(makeLink())).toBe('Not allowed<br />Reason 1<br />Reason 2');
    });

    it('falls back to statusText when no responseJSON data is present', () => {
      respondWithError(stub.jq, { statusText: 'Forbidden' });
      expect(showPlayerClubs(makeLink())).toBe('Forbidden');
    });

    it('returns an empty string when jQuery is unavailable', () => {
      window.jQuery = {};
      expect(showPlayerClubs(makeLink())).toBe('');
    });

    it('still requests clubs with undefined player when link is null or lacks id', () => {
      respondWithSuccess(stub.jq, { data: [{ name: 'Club A' }] });
      expect(showPlayerClubs(null)).toBe('Club A<br />');
      expect(stub.jq.ajax.mock.calls[0][0].data.player).toBeUndefined();

      expect(showPlayerClubs({})).toBe('Club A<br />');
      expect(stub.jq.ajax.mock.calls[1][0].data.player).toBeUndefined();
    });

    it('handles null or missing response data in success callback', () => {
      respondWithSuccess(stub.jq, null);
      expect(showPlayerClubs(makeLink())).toBe('Player not linked to clubs');

      respondWithSuccess(stub.jq, {});
      expect(showPlayerClubs(makeLink())).toBe('Player not linked to clubs');

      respondWithSuccess(stub.jq, { data: null });
      expect(showPlayerClubs(makeLink())).toBe('Player not linked to clubs');
    });

    it('handles club items with missing or empty name', () => {
      respondWithSuccess(stub.jq, { data: [{ name: 'Club A' }, {}, { name: '' }] });
      expect(showPlayerClubs(makeLink())).toBe('Club A<br /><br /><br />');
    });

    it('handles non-array responseJSON data or missing responseJSON', () => {
      respondWithError(stub.jq, { responseJSON: 'not an object', statusText: 'Bad Request' });
      expect(showPlayerClubs(makeLink())).toBe('Bad Request');

      respondWithError(stub.jq, { responseJSON: { data: 'string error' }, statusText: 'Bad Request' });
      expect(showPlayerClubs(makeLink())).toBe('Bad Request');

      respondWithError(stub.jq, { responseJSON: null, statusText: 'Gateway Error' });
      expect(showPlayerClubs(makeLink())).toBe('Gateway Error');
    });

    it('handles responseJSON with empty first item and non-array second item', () => {
      respondWithError(stub.jq, { responseJSON: { data: ['', ['Detailed reason']] } });
      expect(showPlayerClubs(makeLink())).toBe('<br />Detailed reason');

      respondWithError(stub.jq, { responseJSON: { data: ['Base error', 'not an array'] } });
      expect(showPlayerClubs(makeLink())).toBe('Base error');
    });

    it('handles error response with missing statusText or undefined response', () => {
      respondWithError(stub.jq, {});
      expect(showPlayerClubs(makeLink())).toBe('');

      respondWithError(stub.jq, undefined);
      expect(showPlayerClubs(makeLink())).toBe('');
    });
  });

  describe('initGoTop', () => {
    const renderGoTop = () => {
      container.innerHTML = `<a class="go-top" href="#"><span class="icon">top</span></a>`;
      return {
        button: container.querySelector('.go-top'),
        icon: container.querySelector('.icon'),
      };
    };

    it('shows the go-top button when scrolled beyond 800px and hides it again', () => {
      const { button } = renderGoTop();

      scrollPosition = 900;
      window.dispatchEvent(new Event('scroll'));
      expect(button.classList.contains('show')).toBe(true);

      scrollPosition = 100;
      window.dispatchEvent(new Event('scroll'));
      expect(button.classList.contains('show')).toBe(false);
    });

    it('prevents default on click and animates to the top with jQuery', () => {
      const { icon } = renderGoTop();
      const event = new MouseEvent('click', { bubbles: true, cancelable: true });
      icon.dispatchEvent(event);

      expect(event.defaultPrevented).toBe(true);
      expect(stub.jq).toHaveBeenCalledWith('html, body');
      expect(stub.animate).toHaveBeenCalledWith({ scrollTop: 0 }, 1000);
    });

    it('scrolls smoothly to the top when jQuery is unavailable', () => {
      const { icon } = renderGoTop();
      window.jQuery = undefined;
      window.scrollTo = jest.fn();

      icon.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
      expect(window.scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' });
    });

    it('ignores clicks outside a go-top element', () => {
      container.innerHTML = `<a class="other" href="#"><span id="plain">x</span></a>`;
      const event = new MouseEvent('click', { bubbles: true, cancelable: true });
      container.querySelector('#plain').dispatchEvent(event);

      expect(event.defaultPrevented).toBe(false);
      expect(stub.animate).not.toHaveBeenCalled();
    });

    it('falls back to document.documentElement.scrollTop when window.scrollY is 0', () => {
      const { button } = renderGoTop();
      scrollPosition = 0;
      Object.defineProperty(document.documentElement, 'scrollTop', { configurable: true, value: 850 });

      window.dispatchEvent(new Event('scroll'));
      expect(button.classList.contains('show')).toBe(true);

      Object.defineProperty(document.documentElement, 'scrollTop', { configurable: true, value: 300 });
      window.dispatchEvent(new Event('scroll'));
      expect(button.classList.contains('show')).toBe(false);

      Object.defineProperty(document.documentElement, 'scrollTop', { configurable: true, value: 0 });
      window.dispatchEvent(new Event('scroll'));
      expect(button.classList.contains('show')).toBe(false);

      delete document.documentElement.scrollTop;
    });

    it('handles click events when target lacks closest method', () => {
      renderGoTop();
      const event = new MouseEvent('click', { bubbles: true, cancelable: true });
      Object.defineProperty(event, 'target', { value: {} });

      expect(() => document.dispatchEvent(event)).not.toThrow();
      expect(event.defaultPrevented).toBe(false);
    });

    it('falls back to window.scrollTo when jQuery animate is unavailable', () => {
      const { icon } = renderGoTop();
      stub.jq.fn.animate = undefined;
      window.scrollTo = jest.fn();

      icon.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
      expect(window.scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' });
    });

    it('handles click gracefully when neither jQuery animate nor window.scrollTo is a function', () => {
      const { icon } = renderGoTop();
      window.jQuery = undefined;
      window.scrollTo = undefined;

      const event = new MouseEvent('click', { bubbles: true, cancelable: true });
      expect(() => {
        icon.dispatchEvent(event);
      }).not.toThrow();
      expect(event.defaultPrevented).toBe(true);
    });
  });

  describe('initStandingsSortable', () => {
    it('initialises jQuery UI sortable on standings tables with vertical axis', () => {
      stub.jq.fn.sortable = jest.fn();
      stub.chain.sortable = jest.fn();
      container.innerHTML = `<table class="standings-table sortable"></table>`;

      initStandingsSortable();
      expect(stub.jq).toHaveBeenCalledWith('.standings-table.sortable');
      expect(stub.chain.sortable).toHaveBeenCalledWith({ axis: 'y' });
    });

    it('sets move cursor on sortable elements without jQuery', () => {
      window.jQuery = undefined;
      container.innerHTML = `<ul class="sortable"><li>one</li></ul>`;

      initStandingsSortable();
      expect(container.querySelector('.sortable').style.cursor).toBe('move');
    });
  });

  describe('initMiscellaneousUI', () => {
    it('hides top links, disables rank inputs, and sets js-active to 1', () => {
      container.innerHTML = `
        <a class="top-link" href="#">Top</a>
        <input class="rank-input" value="3">
        <div id="teams-filter"><input class="js-active" value="0"></div>
      `;

      initMiscellaneousUI();
      expect(container.querySelector('.top-link').style.display).toBe('none');
      expect(container.querySelector('input.rank-input').disabled).toBe(true);
      expect(container.querySelector('#teams-filter > input.js-active').value).toBe('1');
    });
  });

  describe('bootstrap and iris widgets', () => {
    it('initialises tooltips and popovers when the plugins are available', () => {
      stub.jq.fn.tooltip = jest.fn();
      stub.jq.fn.popover = jest.fn();
      stub.chain.tooltip = jest.fn();
      stub.chain.popover = jest.fn();

      initTooltips();
      expect(stub.jq).toHaveBeenCalledWith('[data-bs-toggle="tooltip"]');
      expect(stub.chain.tooltip).toHaveBeenCalled();

      initPopovers();
      expect(stub.jq).toHaveBeenCalledWith('[data-bs-toggle="popover"]');
      expect(stub.chain.popover).toHaveBeenCalledWith(expect.objectContaining({ placement: 'left', html: true }));
    });

    it('initialises the iris colorpicker on racketmanager-colorpicker inputs', () => {
      stub.jq.fn.iris = jest.fn();
      stub.chain.iris = jest.fn();

      initColorpicker();
      expect(stub.jq).toHaveBeenCalledWith('.racketmanager-colorpicker');
      expect(stub.chain.iris).toHaveBeenCalled();
    });

    it('does nothing when the plugins are not loaded', () => {
      initTooltips();
      initPopovers();
      initColorpicker();
      expect(stub.jq).not.toHaveBeenCalled();
    });

    it('passes showPlayerClubs as content callback in initPopovers', () => {
      stub.jq.fn.popover = jest.fn();
      stub.chain.popover = jest.fn();

      initPopovers();

      const options = stub.chain.popover.mock.calls[0][0];
      expect(typeof options.content).toBe('function');

      respondWithSuccess(stub.jq, { data: [{ name: 'Test Club' }] });
      const link = document.createElement('a');
      link.id = 'player_99';
      expect(options.content(link)).toBe('Test Club<br />');
    });
  });

  describe('initAdminUI', () => {
    it('initialises all admin UI enhancements together without leaking duplicate listeners', () => {
      stub.jq.fn.tooltip = jest.fn();
      stub.jq.fn.popover = jest.fn();
      stub.jq.fn.iris = jest.fn();
      stub.jq.fn.sortable = jest.fn();
      stub.chain.tooltip = jest.fn();
      stub.chain.popover = jest.fn();
      stub.chain.iris = jest.fn();
      stub.chain.sortable = jest.fn();

      const addWinSpy = jest.spyOn(window, 'addEventListener').mockImplementation(() => {});
      const addDocSpy = jest.spyOn(document, 'addEventListener').mockImplementation(() => {});

      container.innerHTML = `
        <a class="top-link" href="#">Link</a>
        <input class="rank-input" value="1">
        <div id="teams-filter"><input class="js-active" value="0"></div>
        <table class="standings-table sortable"></table>
      `;

      try {
        initAdminUI();

        expect(stub.jq).toHaveBeenCalledWith('[data-bs-toggle="tooltip"]');
        expect(stub.jq).toHaveBeenCalledWith('[data-bs-toggle="popover"]');
        expect(stub.jq).toHaveBeenCalledWith('.racketmanager-colorpicker');
        expect(stub.jq).toHaveBeenCalledWith('.standings-table.sortable');
        expect(container.querySelector('.top-link').style.display).toBe('none');
        expect(container.querySelector('input.rank-input').disabled).toBe(true);
        expect(container.querySelector('#teams-filter > input.js-active').value).toBe('1');
        expect(addWinSpy).toHaveBeenCalledWith('scroll', expect.any(Function));
        expect(addDocSpy).toHaveBeenCalledWith('click', expect.any(Function));
      } finally {
        addWinSpy.mockRestore();
        addDocSpy.mockRestore();
      }
    });
  });
});
