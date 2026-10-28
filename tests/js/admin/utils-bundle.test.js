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

const devBundles = {
  ajax: buildBundle('src/js/admin/ajax.js', 'esm', false),
  functions: buildBundle('src/js/admin/functions.js', 'esm', false),
  utils: buildBundle('src/js/admin/utils.js', 'iife', false),
};

const minBundles = {
  ajax: buildBundle('src/js/admin/ajax.js', 'esm', true),
  functions: buildBundle('src/js/admin/functions.js', 'esm', true),
  utils: buildBundle('src/js/admin/utils.js', 'iife', true),
};

const bundleSets = {
  dev: devBundles,
  min: minBundles,
};

const html = `<!DOCTYPE html><html><body>
  <form id="f1"><table><thead><tr><th><input type="checkbox" id="master" data-action="check-all" data-target="f1"></th></tr></thead>
  <tbody><tr><td><input type="checkbox" class="row"></td><td><input type="checkbox" class="row"></td></tr></tbody></table></form>
  <input type="text" id="pts" data-action="save-add-points" data-table-id="5">
  <input type="text" id="other">
  </body></html>`;

const loadWindow = (bundles, withJquery = false) => {
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
  if (withJquery) {
    w.jQuery = w.$ = () => ({ ready: () => {}, on: () => {}, autocomplete: () => {} });
  }
  return { env, dom: env.dom, w, bundles };
};

const evalBundles = (w, bundles) => {
  ['ajax', 'functions', 'utils'].forEach((name) => {
    try {
      w.eval(bundles[name]);
    } catch (e) {
      // ajax/functions may rely on jQuery/WordPress globals not present here;
      // record error only if utils.js fails.
      if (name === 'utils') {
        throw e;
      }
    }
  });
};

describe('built bundles (in-memory esbuild bundles, loaded in enqueue order)', () => {
  it('ensures every src/js/admin/utils.js invocation in package.json build scripts specifies --format=iife', () => {
    const pkgPath = path.resolve(__dirname, '../../../package.json');
    const pkg = JSON.parse(fs.readFileSync(pkgPath, 'utf8'));

    ['build', 'build:prod'].forEach((scriptName) => {
      const script = pkg.scripts?.[scriptName];
      expect(script).toBeDefined();

      const commands = script.split('&&').map((cmd) => cmd.trim());
      const utilsCommands = commands.filter((cmd) => cmd.includes('src/js/admin/utils.js'));

      expect(utilsCommands.length).toBeGreaterThan(0);
      utilsCommands.forEach((cmd) => {
        expect(cmd).toMatch(/--format=iife\b/);
        expect(cmd).not.toMatch(/--format=esm\b/);
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
          try {
            w.eval(bundles.functions);
          } catch (e) {
            /* needs WP/jQuery globals */
          }
          const before = {};
          names().forEach((n) => {
            before[n] = w[n];
          });

          w.eval(bundles.utils);

          const added = names().filter((n) => !(n in before));
          expect(added.sort()).toEqual(['Racketmanager']);
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

      it('handles an iframe load that fires before DOMContentLoaded exactly once', async () => {
        const { env, w } = loadWindow(bundles);
        try {
          const calls = [];
          w.setIframeHeight = (id) => calls.push(id);
          w.eval(bundles.utils);
          expect(w.document.readyState).toBe('loading');
          const iframe = w.document.createElement('iframe');
          iframe.id = 'iframeMsg';
          iframe.setAttribute('data-action', 'set-iframe-height');
          iframe.setAttribute('srcdoc', '<p>hi</p>');
          w.document.body.appendChild(iframe);
          // jsdom fires the real iframe 'load' event asynchronously
          await new Promise((resolve) => setTimeout(resolve, 100));
          expect(calls).toEqual(['iframeMsg']);
        } finally {
          await env.teardown();
        }
      });
    });
  });
});
