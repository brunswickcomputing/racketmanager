/**
 * Loads built admin bundles in enqueue order into a fresh window to test for
 * global collisions, runtime script execution, and classic-script compatibility.
 * Bundles are compiled in memory via esbuild matching the package build configuration,
 * ensuring tests are fully independent of disk dist files and CI-clean.
 */
import fs from 'fs';
import path from 'path';
import vm from 'vm';
import { TextEncoder, TextDecoder } from 'util';
// Fix Jest JSDOM Uint8Array realm mismatch for Node's esbuild
global.Uint8Array = Object.getPrototypeOf(Buffer.prototype).constructor;
const esbuild = require('esbuild');

// Polyfill TextEncoder/TextDecoder if not present in test scope
global.TextEncoder = global.TextEncoder || TextEncoder;
global.TextDecoder = global.TextDecoder || TextDecoder;

const JSDOMEnvironment = require('jest-environment-jsdom').default;

const buildBundle = (entryRelPath, format, minify) => {
  const entryPoint = path.resolve(__dirname, '../../../', entryRelPath);
  const result = esbuild.buildSync({
    entryPoints: [entryPoint],
    bundle: true,
    format,
    target: 'es2020',
    minify,
    write: false,
  });
  return result.outputFiles[0].text;
};

const ENTRIES = ['ajax', 'functions', 'utils', 'draggable'];

// WordPress loads these as classic scripts, so every one is built as an IIFE (see package.json).
const buildSet = (minify) => {
  const set = {};
  ENTRIES.forEach((name) => {
    set[name] = buildBundle(`src/js/admin/${name}.js`, 'iife', minify);
  });
  return set;
};

const devBundles = buildSet(false);
const minBundles = buildSet(true);

const bundleSets = {
  dev: devBundles,
  min: minBundles,
};

const html = `<!DOCTYPE html><html><body>
  <form id="f1"><table><thead><tr><th><input type="checkbox" id="master" data-action="check-all" data-target="f1"></th></tr></thead>
  <tbody><tr><td><input type="checkbox" class="row"></td><td><input type="checkbox" class="row"></td></tr></tbody></table></form>
  <input type="text" id="pts" data-action="save-add-points" data-table-id="5">
  <input type="text" id="other">
  <input type="hidden" id="competitionType" value="league">
  <input type="text" id="dateStart" value="2026-01-15">
  <input type="text" id="dateEnd" value="">
  <input type="text" id="round_length" value="7">
  <input type="text" id="num_match_days" value="4">
  <input type="text" id="filler_weeks" value="0">
  <input type="text" id="home_away_diff" value="0">
  <div id="notifyMessage-7" style="display:none"></div>
  <div id="events" style="display:none"></div>
  <div id="leagues" style="display:none"></div>
  <div id="seasons" style="display:none"></div>
  <select id="competition_id" data-action="get-event-dropdown"><option value="3" selected>3</option></select>
  <div id="schedule-0" class="tournament-match"><div id="match-5" class="finals-match" draggable="true"></div></div>
  <div id="schedule-1" class="tournament-match"><input type="hidden" id="match-1" class="matchId" value=""></div>
  </body></html>`;

const AJAX_URL = '/wp-admin/admin-ajax.php';
const NONCE = 'nonce-123';

// Creates a fresh window with a recording jQuery stub; WordPress globals are defined before any bundle runs.
const loadWindow = (bundles) => {
  const env = new JSDOMEnvironment(
    {
      projectConfig: {
        testEnvironmentOptions: {
          html,
          runScripts: 'outside-only',
          url: 'http://localhost/',
        },
      },
      globalConfig: {},
    },
    { console }
  );
  const w = env.dom.window;
  const ajaxCalls = [];
  const jq = () => ({ ready: () => {}, on: () => {}, autocomplete: () => {} });
  jq.fn = {};
  jq.ajax = (opts) => {
    ajaxCalls.push(opts);
  };
  w.jQuery = w.$ = jq;
  w.ajaxurl = AJAX_URL;
  w.ajax_var = { ajax_nonce: NONCE, url: AJAX_URL };
  return { env, dom: env.dom, w, bundles, ajaxCalls };
};

// Errors are NOT swallowed: any bundle that throws fails the test.
const evalBundles = (w, bundles, names = ENTRIES) => {
  names.forEach((name) => {
    w.eval(bundles[name]);
  });
};

const WP_ENQUEUE_ORDER = ['ajax', 'functions', 'utils', 'draggable'];

describe('built bundles (in-memory esbuild bundles, loaded in enqueue order)', () => {
  it('ensures every src/js/admin/*.js entry invocation in package.json build scripts specifies --format=iife', () => {
    const pkgPath = path.resolve(__dirname, '../../../package.json');
    const pkg = JSON.parse(fs.readFileSync(pkgPath, 'utf8'));

    ['build', 'build:prod'].forEach((scriptName) => {
      const script = pkg.scripts?.[scriptName];
      expect(script).toBeDefined();

      const commands = script.split('&&').map((cmd) => cmd.trim());
      ENTRIES.forEach((entry) => {
        const entryCommands = commands.filter((cmd) => cmd.includes(`src/js/admin/${entry}.js`));

        expect(entryCommands.length).toBeGreaterThan(0);
        entryCommands.forEach((cmd) => {
          expect(cmd).toMatch(/--format=iife\b/);
          expect(cmd).not.toMatch(/--format=esm\b/);
        });
      });
    });
  });

  it('distinguishes dev and min bundles (unminified dev is larger and formatted differently)', () => {
    expect(devBundles.utils.length).toBeGreaterThan(minBundles.utils.length);
    expect(devBundles.utils).not.toEqual(minBundles.utils);
    expect(devBundles.utils).toMatch(/\n\s{2,}/);

    expect(devBundles.functions.length).toBeGreaterThan(minBundles.functions.length);
    expect(devBundles.functions).not.toEqual(minBundles.functions);

    expect(devBundles.ajax.length).toBeGreaterThan(minBundles.ajax.length);
    expect(devBundles.ajax).not.toEqual(minBundles.ajax);

    expect(devBundles.draggable.length).toBeGreaterThan(minBundles.draggable.length);
    expect(devBundles.draggable).not.toEqual(minBundles.draggable);
  });

  Object.entries(bundleSets).forEach(([label, bundles]) => {
    describe(`${label} bundle set`, () => {
      it('parses every bundle as a classic script (no export/import)', () => {
        Object.values(bundles).forEach((code) => {
          expect(code).not.toMatch(/^\s*export\s+/m);
          expect(() => new vm.Script(code)).not.toThrow();
        });
      });

      it('utils bundle adds no top-level globals and leaves functions.js globals intact', async () => {
        const { env, w } = loadWindow(bundles);
        try {
          const names = () => Object.getOwnPropertyNames(w);

          // Run only functions.js first (as WP does), then snapshot globals and their values.
          w.eval(bundles.functions);
          const before = {};
          names().forEach((n) => {
            before[n] = w[n];
          });

          w.eval(bundles.utils);

          const added = names().filter((n) => !(n in before));
          // functions.js has already created the Racketmanager namespace; utils must add nothing else.
          expect(added).toEqual([]);
          Object.keys(before).forEach((n) => {
            if (n === 'Racketmanager') return;
            expect(w[n]).toBe(before[n]);
          });
        } finally {
          await env.teardown();
        }
      });

      it('does not overwrite a function global defined by functions.js (player autocomplete)', async () => {
        const { env, w } = loadWindow(bundles);
        try {
          const marker = function player_lookup() {};
          // Simulate every single-letter global the minified functions.js could expose.
          'abcdefghijklmnopqrstuvwxyz'.split('').forEach((c) => {
            w[c] = marker;
          });
          w.eval(bundles.utils);
          'abcdefghijklmnopqrstuvwxyz'.split('').forEach((c) => {
            expect(w[c]).toBe(marker);
          });
        } finally {
          await env.teardown();
        }
      });

      it('delegated action works end-to-end in the bundle (check-all)', async () => {
        const { env, w } = loadWindow(bundles);
        try {
          evalBundles(w, bundles);
          const master = w.document.getElementById('master');
          const rows = w.document.querySelectorAll('.row');
          master.click(); // unchecked -> checked, then the delegated handler runs
          expect(master.checked).toBe(true);
          rows.forEach((r) => expect(r.checked).toBe(true));
          master.click();
          expect(master.checked).toBe(false);
          rows.forEach((r) => expect(r.checked).toBe(false));
        } finally {
          await env.teardown();
        }
      });

      it('registers once at script run time (before DOMContentLoaded) and is idempotent', async () => {
        const { env, w } = loadWindow(bundles);
        try {
          expect(w.document.readyState).toBe('loading');
          w.eval(bundles.utils);
          expect(w.document.__rmAdminActionsRegistered).toBe(true);
          const calls = [];
          w.Racketmanager.saveAddPoints = (v, id) => calls.push([v, id]);
          // second evaluation / manual registration must not double-bind
          w.eval(bundles.utils);
          w.Racketmanager.registerAdminActions(w.document);
          const input = w.document.getElementById('pts');
          input.value = '3';
          input.dispatchEvent(new w.FocusEvent('focusout', { bubbles: true }));
          expect(calls).toEqual([['3', '5']]);
        } finally {
          await env.teardown();
        }
      });

      it('loads ajax -> functions -> utils -> draggable without errors and leaks only Racketmanager', async () => {
        const { env, w } = loadWindow(bundles);
        try {
          const before = new Set(Object.getOwnPropertyNames(w));
          evalBundles(w, bundles, WP_ENQUEUE_ORDER);
          w.document.dispatchEvent(new w.Event('DOMContentLoaded'));
          const added = Object.getOwnPropertyNames(w).filter((n) => !before.has(n));
          expect(added).toEqual(['Racketmanager']);
          ['getEventDropdown', 'getLeagueDropdown', 'getSeasonDropdown', 'getMatchDropdown', 'setEndDate',
            'amendDate', 'notifyTeams', 'saveAddPoints', 'activaTab', 'checkAll', 'setShortcode'].forEach((m) => {
            expect(typeof w.Racketmanager[m]).toBe('function');
          });
        } finally {
          await env.teardown();
        }
      });

      it('setEndDate gives the same result with ajax.js alone and after every bundle has loaded', async () => {
        const alone = loadWindow(bundles);
        const all = loadWindow(bundles);
        try {
          evalBundles(alone.w, bundles, ['ajax']);
          alone.w.Racketmanager.setEndDate();
          const expected = alone.w.document.getElementById('dateEnd').value;
          expect(expected).toMatch(/^2026-02-\d{2}$/);
          expect(alone.ajaxCalls).toHaveLength(0);

          evalBundles(all.w, bundles, WP_ENQUEUE_ORDER);
          all.w.document.dispatchEvent(new all.w.Event('DOMContentLoaded'));
          all.w.Racketmanager.setEndDate();
          expect(all.w.document.getElementById('dateEnd').value).toBe(expected);
          expect(all.w.Racketmanager.amendDate('2026-01-15', 27)).toBe(expected);
          // No stray AJAX request (e.g. racketmanager_get_player_clubs) from a clobbered internal name.
          expect(all.ajaxCalls).toHaveLength(0);
        } finally {
          await alone.env.teardown();
          await all.env.teardown();
        }
      });

      it('notifyTeams still posts the notify request and renders the response after all bundles load', async () => {
        const { env, w, ajaxCalls } = loadWindow(bundles);
        try {
          evalBundles(w, bundles, WP_ENQUEUE_ORDER);
          w.document.dispatchEvent(new w.Event('DOMContentLoaded'));
          expect(() => w.Racketmanager.notifyTeams(7)).not.toThrow();
          expect(ajaxCalls).toHaveLength(1);
          expect(ajaxCalls[0].url).toBe(AJAX_URL);
          expect(ajaxCalls[0].type).toBe('POST');
          expect(ajaxCalls[0].data).toEqual({ matchId: 7, action: 'racketmanager_notify_teams', security: NONCE });
          ajaxCalls[0].success({ data: { message: 'Teams notified' } });
          const el = w.document.getElementById('notifyMessage-7');
          expect(el.textContent).toBe('Teams notified');
          expect(el.className).toBe('message-success');
          expect(el.style.display).not.toBe('none');
        } finally {
          await env.teardown();
        }
      });

      it('dropdown cascades post the right payload via direct call and delegated change, after all bundles load', async () => {
        const { env, w, ajaxCalls } = loadWindow(bundles);
        try {
          evalBundles(w, bundles, WP_ENQUEUE_ORDER);
          w.document.dispatchEvent(new w.Event('DOMContentLoaded'));

          w.Racketmanager.getLeagueDropdown(9);
          w.Racketmanager.getSeasonDropdown(4);
          w.Racketmanager.getMatchDropdown(4, '2026');
          w.Racketmanager.getEventDropdown(3);
          expect(ajaxCalls.map((c) => c.data.action)).toEqual([
            'racketmanager_get_league_dropdown',
            'racketmanager_get_season_dropdown',
            'racketmanager_get_match_dropdown',
            'racketmanager_get_event_dropdown',
          ]);
          ajaxCalls.forEach((c) => {
            expect(c.url).toBe(AJAX_URL);
            expect(c.data.security).toBe(NONCE);
          });

          ajaxCalls.length = 0;
          w.document.getElementById('competition_id').dispatchEvent(new w.Event('change', { bubbles: true }));
          // exactly one request: the delegated listener must not be registered twice
          expect(ajaxCalls).toHaveLength(1);
          expect(ajaxCalls[0].data).toMatchObject({ competition_id: '3', action: 'racketmanager_get_event_dropdown' });
          ajaxCalls[0].success({ data: '<select id="event_id"></select>' });
          ajaxCalls[0].complete();
          const events = w.document.getElementById('events');
          expect(events.querySelector('#event_id')).not.toBeNull();
          expect(events.style.display).not.toBe('none');
        } finally {
          await env.teardown();
        }
      });

      it('draggable moves a match into an empty schedule slot and updates the hidden inputs after all bundles load', async () => {
        const { env, w } = loadWindow(bundles);
        try {
          evalBundles(w, bundles, WP_ENQUEUE_ORDER);
          const match = w.document.getElementById('match-5');
          const target = w.document.getElementById('schedule-1');
          const drop = new w.Event('drop', { bubbles: true, cancelable: true });
          Object.defineProperty(drop, 'dataTransfer', {
            value: { getData: () => 'match-5', setData: () => {} },
          });
          target.dispatchEvent(drop);
          expect(match.parentElement).toBe(target);
          expect(w.document.getElementById('match-1').value).toBe('5');
        } finally {
          await env.teardown();
        }
      });

      it('handles an iframe load that fires before DOMContentLoaded exactly once', async () => {
        const { env, w } = loadWindow(bundles);
        try {
          w.eval(bundles.utils);
          expect(w.document.readyState).toBe('loading');
          const iframe = w.document.createElement('iframe');
          iframe.id = 'iframeMsg';
          iframe.setAttribute('data-action', 'set-iframe-height');
          iframe.setAttribute('srcdoc', '<p>hi</p>');
          w.document.body.appendChild(iframe);
          // jsdom fires the real iframe 'load' event asynchronously
          await new Promise((resolve) => setTimeout(resolve, 100));
          expect(iframe.style.visibility).toBe('visible');
          expect(iframe.style.height).toMatch(/^\d+px$/);
        } finally {
          await env.teardown();
        }
      });
    });
  });
});
