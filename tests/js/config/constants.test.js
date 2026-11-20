describe('constants', () => {
    let originalRacketmanager;
    let originalDisableLegacy;

    beforeEach(() => {
        originalRacketmanager = window.Racketmanager;
        originalDisableLegacy = window.RACKETMANAGER_DISABLE_LEGACY;
        jest.resetModules();
    });

    afterEach(() => {
        window.Racketmanager = originalRacketmanager;
        window.RACKETMANAGER_DISABLE_LEGACY = originalDisableLegacy;
        jest.restoreAllMocks();
    });

    test('initializes window.Racketmanager and exports constants when window.Racketmanager is undefined', async () => {
        delete window.Racketmanager;
        delete window.RACKETMANAGER_DISABLE_LEGACY;

        const { LOADING_MODAL, AJAX_TIMEOUT, Racketmanager } = await import(
            '../../../src/js/config/constants.js'
        );

        expect(LOADING_MODAL).toBe('#loadingModal');
        expect(AJAX_TIMEOUT).toBe(30000);
        expect(window.Racketmanager).toEqual({ loadingModal: '#loadingModal' });
        expect(Racketmanager).toBe(window.Racketmanager);
        expect(window.RACKETMANAGER_DISABLE_LEGACY).toBe(true);
    });

    test('preserves existing window.Racketmanager and sets loadingModal if missing', async () => {
        window.Racketmanager = { customProp: 'value' };
        window.RACKETMANAGER_DISABLE_LEGACY = false;

        const { Racketmanager } = await import('../../../src/js/config/constants.js');

        expect(window.Racketmanager.customProp).toBe('value');
        expect(window.Racketmanager.loadingModal).toBe('#loadingModal');
        expect(Racketmanager).toBe(window.Racketmanager);
        expect(window.RACKETMANAGER_DISABLE_LEGACY).toBe(false);
    });

    test('leaves loadingModal unchanged if already present on window.Racketmanager', async () => {
        window.Racketmanager = { loadingModal: '#myCustomModal' };

        const { Racketmanager } = await import('../../../src/js/config/constants.js');

        expect(window.Racketmanager.loadingModal).toBe('#myCustomModal');
        expect(Racketmanager.loadingModal).toBe('#myCustomModal');
    });
});
