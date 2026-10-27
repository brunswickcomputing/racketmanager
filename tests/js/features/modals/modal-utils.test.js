// modal-utils.test.js
import {confirmModal} from '../../../../src/js/features/modals';

describe('confirmModal', () => {
    let modalInstance;

    beforeEach(() => {
        // Clear DOM before each test
        document.body.innerHTML = '';
        jest.clearAllMocks();

        // bootstrap is a page-level global in the plugin, so mock it globally
        modalInstance = {
            show: jest.fn(),
            hide: jest.fn(),
        };
        global.bootstrap = {
            Modal: jest.fn(() => modalInstance),
        };
    });

    afterEach(() => {
        delete global.bootstrap;
    });

    test('should resolve with true on confirm', async () => {
        const userAction = confirmModal({title: 'Confirm Action'});

        // Simulate user behavior
        const confirmButton = document.querySelector('#confirmBtn');
        confirmButton.click();

        // Check if the promise resolves with true
        await expect(userAction).resolves.toBe(true);

        // Ensure modal show/hide methods are called
        expect(global.bootstrap.Modal).toHaveBeenCalledTimes(1);
        expect(modalInstance.show).toHaveBeenCalledTimes(1);
        expect(modalInstance.hide).toHaveBeenCalledTimes(1);
    });

    test('should reject with an Error on cancel', async () => {
        const userAction = confirmModal({title: 'Confirm Action'});

        // Simulate user behavior (real Bootstrap would fire hidden.bs.modal after dismiss)
        const cancelButton = document.querySelector('.btn-secondary');
        cancelButton.click();
        jQuery('#confirmModal').trigger('hidden.bs.modal');

        // Check if the promise rejects with an Error
        await expect(userAction).rejects.toThrow('Confirmation cancelled');

        // Ensure modal is shown and then removed
        expect(global.bootstrap.Modal).toHaveBeenCalledTimes(1);
        expect(modalInstance.hide).not.toHaveBeenCalled();
    });

    test('should use default options if none provided', async () => {
        confirmModal();

        // Check modal elements
        expect(document.querySelector('.modal-title').textContent.trim()).toBe('Confirm');
        expect(document.querySelector('.modal-body').textContent.trim()).toBe('Are you sure?');
        expect(document.querySelector('#confirmBtn').textContent.trim()).toBe('Confirm');
        expect(document.querySelector('.btn-secondary').textContent.trim()).toBe('Cancel');
    });

    test('should merge provided options with defaults', async () => {
        const options = {
            title: 'Custom Title',
            message: 'Custom message',
            confirmText: 'Yes',
            cancelText: 'No',
            confirmClass: 'btn-success',
            cancelClass: 'btn-danger',
        };

        confirmModal(options);

        // Check modal elements
        expect(document.querySelector('.modal-title').textContent.trim()).toBe(options.title);
        expect(document.querySelector('.modal-body').textContent.trim()).toBe(options.message);
        expect(document.querySelector('#confirmBtn').textContent.trim()).toBe(options.confirmText);
        expect(document.querySelector('#confirmBtn').classList.contains('btn-success')).toBe(true);
        expect(document.querySelector('.btn-danger').textContent.trim()).toBe(options.cancelText);
    });

    test('should remove modal from DOM after it is closed', async () => {
        const userAction = confirmModal();

        expect(document.querySelector('#confirmModal')).not.toBeNull();

        // Simulate user closing the modal
        jQuery('#confirmModal').trigger('hidden.bs.modal');

        await expect(userAction).rejects.toThrow('Confirmation cancelled');

        // Ensure modal is removed from DOM
        expect(document.querySelector('#confirmModal')).toBeNull();
    });

    test('should not resolve or reject multiple times', async () => {
        const userAction = confirmModal();

        // Simulate user clicking confirm
        const confirmButton = document.querySelector('#confirmBtn');
        confirmButton.click();

        // Try resolving again
        confirmButton.click();

        await expect(userAction).resolves.toBe(true);
        expect(modalInstance.hide).toHaveBeenCalledTimes(2);
    });
});
