/**
 * @jest-environment jsdom
 */

import { showModal, hideModal } from '../../../../src/js/features/modals';

describe('features/modals/modal-utils', () => {
    let modalInstance;

    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();

        window.jQuery = window.$ = require('jquery');

        modalInstance = {
            show: jest.fn(),
            hide: jest.fn(),
        };

        const ModalMock = jest.fn(() => modalInstance);
        ModalMock.getInstance = jest.fn(() => modalInstance);

        global.bootstrap = {
            Modal: ModalMock,
        };
    });

    afterEach(() => {
        delete global.bootstrap;
        document.body.innerHTML = '';
        jest.restoreAllMocks();
    });

    describe('showModal', () => {
        test('instantiates bootstrap.Modal with element and calls show() when element exists', () => {
            document.body.innerHTML = '<div id="testModal"></div>';

            showModal('#testModal');

            const modalElement = document.getElementById('testModal');
            expect(global.bootstrap.Modal).toHaveBeenCalledTimes(1);
            expect(global.bootstrap.Modal).toHaveBeenCalledWith(modalElement);
            expect(modalInstance.show).toHaveBeenCalledTimes(1);
        });

        test('does not instantiate bootstrap.Modal or throw when element does not exist', () => {
            expect(() => showModal('#nonExistentModal')).not.toThrow();

            expect(global.bootstrap.Modal).not.toHaveBeenCalled();
            expect(modalInstance.show).not.toHaveBeenCalled();
        });

        test('handles null, undefined, empty string, or invalid selectors gracefully', () => {
            expect(() => showModal(null)).not.toThrow();
            expect(() => showModal(undefined)).not.toThrow();
            expect(() => showModal('')).not.toThrow();

            expect(global.bootstrap.Modal).not.toHaveBeenCalled();
            expect(modalInstance.show).not.toHaveBeenCalled();
        });

        test('uses the first element when selector matches multiple elements', () => {
            document.body.innerHTML = `
                <div class="multi-modal" id="firstModal"></div>
                <div class="multi-modal" id="secondModal"></div>
            `;

            showModal('.multi-modal');

            const firstModal = document.getElementById('firstModal');
            expect(global.bootstrap.Modal).toHaveBeenCalledTimes(1);
            expect(global.bootstrap.Modal).toHaveBeenCalledWith(firstModal);
            expect(modalInstance.show).toHaveBeenCalledTimes(1);
        });
    });

    describe('hideModal', () => {
        test('calls hide() on modal instance when element and instance exist', () => {
            document.body.innerHTML = '<div id="activeModal"></div>';

            hideModal('#activeModal');

            const modalElement = document.getElementById('activeModal');
            expect(global.bootstrap.Modal.getInstance).toHaveBeenCalledTimes(1);
            expect(global.bootstrap.Modal.getInstance).toHaveBeenCalledWith(modalElement);
            expect(modalInstance.hide).toHaveBeenCalledTimes(1);
        });

        test('does not call hide() or throw when element exists but getInstance returns null/undefined', () => {
            document.body.innerHTML = '<div id="orphanModal"></div>';
            global.bootstrap.Modal.getInstance.mockReturnValue(null);

            expect(() => hideModal('#orphanModal')).not.toThrow();

            const modalElement = document.getElementById('orphanModal');
            expect(global.bootstrap.Modal.getInstance).toHaveBeenCalledWith(modalElement);
            expect(modalInstance.hide).not.toHaveBeenCalled();
        });

        test('does not call getInstance() or throw when element does not exist', () => {
            expect(() => hideModal('#nonExistentModal')).not.toThrow();

            expect(global.bootstrap.Modal.getInstance).not.toHaveBeenCalled();
            expect(modalInstance.hide).not.toHaveBeenCalled();
        });

        test('handles null, undefined, empty string, or invalid selectors gracefully', () => {
            expect(() => hideModal(null)).not.toThrow();
            expect(() => hideModal(undefined)).not.toThrow();
            expect(() => hideModal('')).not.toThrow();

            expect(global.bootstrap.Modal.getInstance).not.toHaveBeenCalled();
            expect(modalInstance.hide).not.toHaveBeenCalled();
        });

        test('uses the first element when selector matches multiple elements', () => {
            document.body.innerHTML = `
                <div class="multi-modal" id="firstModal"></div>
                <div class="multi-modal" id="secondModal"></div>
            `;

            hideModal('.multi-modal');

            const firstModal = document.getElementById('firstModal');
            expect(global.bootstrap.Modal.getInstance).toHaveBeenCalledTimes(1);
            expect(global.bootstrap.Modal.getInstance).toHaveBeenCalledWith(firstModal);
            expect(modalInstance.hide).toHaveBeenCalledTimes(1);
        });
    });
});
