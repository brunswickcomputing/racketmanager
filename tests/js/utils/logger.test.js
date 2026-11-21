describe('logger and telemetry', () => {
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

    test('logs at configured level and above', async () => {
        globalThis.RACKETMANAGER_CONFIG = {
            env: 'development',
            logging: { level: 'info' }
        };

        const consoleDebugSpy = jest.spyOn(console, 'debug').mockImplementation(() => {});
        const consoleInfoSpy = jest.spyOn(console, 'info').mockImplementation(() => {});
        const consoleWarnSpy = jest.spyOn(console, 'warn').mockImplementation(() => {});
        const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

        const { logger } = await import('../../../src/js/utils/logger.js');

        logger.debug('debug msg');
        expect(consoleDebugSpy).not.toHaveBeenCalled();

        logger.info('info msg');
        expect(consoleInfoSpy).toHaveBeenCalledWith('info msg');

        logger.warn('warn msg');
        expect(consoleWarnSpy).toHaveBeenCalledWith('warn msg');

        logger.error('error msg');
        expect(consoleErrorSpy).toHaveBeenCalledWith('error msg');
    });

    test('does not log when configured level is "none"', async () => {
        globalThis.RACKETMANAGER_CONFIG = {
            env: 'development',
            logging: { level: 'none' }
        };

        const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
        const consoleWarnSpy = jest.spyOn(console, 'warn').mockImplementation(() => {});

        const { logger } = await import('../../../src/js/utils/logger.js');

        logger.error('fail');
        logger.warn('warning');

        expect(consoleErrorSpy).not.toHaveBeenCalled();
        expect(consoleWarnSpy).not.toHaveBeenCalled();
    });

    test('does not log when configured level is invalid / unknown', async () => {
        globalThis.RACKETMANAGER_CONFIG = {
            env: 'development',
            logging: { level: 'unknown_level' }
        };

        const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

        const { logger } = await import('../../../src/js/utils/logger.js');

        logger.error('fail');
        expect(consoleErrorSpy).not.toHaveBeenCalled();
    });

    test('handles missing or non-function console methods safely without throwing', async () => {
        globalThis.RACKETMANAGER_CONFIG = {
            env: 'development',
            logging: { level: 'debug' }
        };

        const originalDebug = console.debug;
        console.debug = undefined;

        try {
            const { logger } = await import('../../../src/js/utils/logger.js');

            expect(() => {
                logger.debug('should not throw');
            }).not.toThrow();
        } finally {
            console.debug = originalDebug;
        }
    });

    test('suppresses errors if console method throws when logging', async () => {
        globalThis.RACKETMANAGER_CONFIG = {
            env: 'development',
            logging: { level: 'debug' }
        };

        const consoleWarnSpy = jest.spyOn(console, 'warn').mockImplementation(() => {
            throw new Error('Restricted console');
        });

        const { logger } = await import('../../../src/js/utils/logger.js');

        expect(() => {
            logger.warn('test throwing console');
        }).not.toThrow();
        expect(consoleWarnSpy).toHaveBeenCalledWith('test throwing console');
    });

    test('recordEvent logs debug telemetry when telemetryEnabled is true', async () => {
        globalThis.RACKETMANAGER_CONFIG = {
            env: 'development',
            flags: { enableTelemetry: true },
            logging: { level: 'debug' }
        };

        const consoleDebugSpy = jest.spyOn(console, 'debug').mockImplementation(() => {});

        const { recordEvent } = await import('../../../src/js/utils/logger.js');

        recordEvent('match_viewed', { matchId: 123 });
        expect(consoleDebugSpy).toHaveBeenCalledWith('[telemetry]', 'match_viewed', { matchId: 123 });

        recordEvent('simple_event');
        expect(consoleDebugSpy).toHaveBeenCalledWith('[telemetry]', 'simple_event', {});
    });

    test('recordEvent does nothing when telemetry is disabled', async () => {
        globalThis.RACKETMANAGER_CONFIG = {
            env: 'production',
            flags: { enableTelemetry: false },
            logging: { level: 'debug' }
        };

        const consoleDebugSpy = jest.spyOn(console, 'debug').mockImplementation(() => {});

        const { recordEvent } = await import('../../../src/js/utils/logger.js');

        recordEvent('match_viewed', { matchId: 123 });
        expect(consoleDebugSpy).not.toHaveBeenCalled();
    });
});
