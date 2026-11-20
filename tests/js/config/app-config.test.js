describe('app-config', () => {
    let originalConfig;

    beforeEach(() => {
        originalConfig = globalThis.RACKETMANAGER_CONFIG;
        jest.resetModules();
    });

    afterEach(() => {
        if (originalConfig !== undefined) {
            globalThis.RACKETMANAGER_CONFIG = originalConfig;
        } else {
            delete globalThis.RACKETMANAGER_CONFIG;
        }
        jest.restoreAllMocks();
    });

    test('uses production defaults when RACKETMANAGER_CONFIG is not defined', async () => {
        delete globalThis.RACKETMANAGER_CONFIG;

        const { AppConfig, isDev, telemetryEnabled, perfMarksEnabled } = await import(
            '../../../src/js/config/app-config.js'
        );

        expect(AppConfig.env).toBe('production');
        expect(AppConfig.flags.enableTelemetry).toBe(false);
        expect(AppConfig.flags.enablePerfMarks).toBe(false);
        expect(AppConfig.logging.level).toBe('warn');
        expect(isDev()).toBe(false);
        expect(telemetryEnabled()).toBe(false);
        expect(perfMarksEnabled()).toBe(false);
    });

    test('forces telemetry off and respects custom logging level in production', async () => {
        globalThis.RACKETMANAGER_CONFIG = {
            env: 'production',
            flags: {
                enableTelemetry: true,
                enablePerfMarks: true
            },
            logging: {
                level: 'error'
            }
        };

        const { AppConfig, isDev, telemetryEnabled, perfMarksEnabled } = await import(
            '../../../src/js/config/app-config.js'
        );

        expect(AppConfig.env).toBe('production');
        expect(AppConfig.flags.enableTelemetry).toBe(false);
        expect(AppConfig.flags.enablePerfMarks).toBe(true);
        expect(AppConfig.logging.level).toBe('error');
        expect(isDev()).toBe(false);
        expect(telemetryEnabled()).toBe(false);
        expect(perfMarksEnabled()).toBe(false);
    });

    test('allows telemetry and perfMarks in development', async () => {
        globalThis.RACKETMANAGER_CONFIG = {
            env: 'development',
            flags: {
                enableTelemetry: true,
                enablePerfMarks: true
            },
            logging: {
                level: 'debug'
            }
        };

        const { AppConfig, isDev, telemetryEnabled, perfMarksEnabled } = await import(
            '../../../src/js/config/app-config.js'
        );

        expect(AppConfig.env).toBe('development');
        expect(AppConfig.flags.enableTelemetry).toBe(true);
        expect(AppConfig.flags.enablePerfMarks).toBe(true);
        expect(AppConfig.logging.level).toBe('debug');
        expect(isDev()).toBe(true);
        expect(telemetryEnabled()).toBe(true);
        expect(perfMarksEnabled()).toBe(true);
    });

    test('perfMarksEnabled returns false if perfMarks is enabled but telemetry is disabled', async () => {
        globalThis.RACKETMANAGER_CONFIG = {
            env: 'development',
            flags: {
                enableTelemetry: false,
                enablePerfMarks: true
            }
        };

        const { AppConfig, isDev, telemetryEnabled, perfMarksEnabled } = await import(
            '../../../src/js/config/app-config.js'
        );

        expect(isDev()).toBe(true);
        expect(telemetryEnabled()).toBe(false);
        expect(perfMarksEnabled()).toBe(false);
    });
});
