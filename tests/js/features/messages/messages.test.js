/**
 * @jest-environment jsdom
 */

import { getMessage, deleteMessage, deleteMessages, initializeMessages } from '../../../../src/js/features/messages/messages.js';

describe('features/messages/messages', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.ajax_var = {
            url: '/wp-admin/admin-ajax.php',
            ajax_nonce: 'msg-nonce-test'
        };
        window.jQuery = window.$ = require('jquery');
    });

    describe('getMessage', () => {
        test('requests message, updates read/unread count, and injects output', () => {
            document.body.innerHTML = `
                <div id="messageDetailDisplay"></div>
                <div id="message-summary-42" class="message-summary unread"></div>
                <div id="message_detail" style="display: none;"></div>
                <div id="read-messages">5</div>
                <div id="unread-messages">3</div>
                <div id="messagesAlert"><div id="messagesAlertResponse"></div></div>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({
                    data: {
                        status: '1',
                        output: '<p>Message content body</p>'
                    }
                });
                opts.complete();
            });

            const fakeEvt = { preventDefault: jest.fn() };
            getMessage(fakeEvt, 42);

            expect(fakeEvt.preventDefault).toHaveBeenCalled();
            expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                url: '/wp-admin/admin-ajax.php',
                data: expect.objectContaining({
                    action: 'racketmanager_get_message',
                    message_id: 42,
                    security: 'msg-nonce-test'
                })
            }));

            expect(document.getElementById('message-summary-42').className).toContain('selected');
            expect(document.getElementById('message-summary-42').className).toContain('read');
            expect(document.getElementById('read-messages').textContent).toBe('6');
            expect(document.getElementById('unread-messages').textContent).toBe('2');
            expect(document.getElementById('message_detail').innerHTML).toBe('<p>Message content body</p>');
            expect(document.getElementById('message_detail').style.display).not.toBe('none');
            expect(document.getElementById('messageDetailDisplay').classList.contains('is-loading')).toBe(false);

            spyAjax.mockRestore();
        });

        test('handles getMessage error callback', () => {
            document.body.innerHTML = `
                <div id="messageDetailDisplay"></div>
                <div id="messagesAlert" style="display: none;"><div id="messagesAlertResponse"></div></div>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.error({ responseJSON: { data: { message: 'Message not found' } } });
                opts.complete();
            });

            getMessage(null, 99);

            expect(document.getElementById('messagesAlertResponse').textContent).toContain('Message not found');
            expect(document.getElementById('messagesAlert').style.display).not.toBe('none');

            spyAjax.mockRestore();
        });
    });

    describe('deleteMessage', () => {
        test('aborts if confirm returns false', () => {
            const spyConfirm = jest.spyOn(window, 'confirm').mockReturnValue(false);
            const spyAjax = jest.spyOn(jQuery, 'ajax');

            deleteMessage(null, 10);

            expect(spyConfirm).toHaveBeenCalled();
            expect(spyAjax).not.toHaveBeenCalled();

            spyConfirm.mockRestore();
        });

        test('deletes message on confirm and updates DOM', () => {
            const spyConfirm = jest.spyOn(window, 'confirm').mockReturnValue(true);
            document.body.innerHTML = `
                <div id="message-summary-10"></div>
                <div id="message_detail"></div>
                <div id="messagesAlert"><div id="messagesAlertResponse"></div></div>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({
                    data: {
                        success: true,
                        output: 'Message deleted successfully'
                    }
                });
            });

            deleteMessage(null, 10);

            expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                data: expect.objectContaining({
                    action: 'racketmanager_delete_message',
                    message_id: 10
                })
            }));

            expect(document.getElementById('message-summary-10').style.display).toBe('none');
            expect(document.getElementById('message_detail').textContent).toBe('Message deleted successfully');

            spyConfirm.mockRestore();
            spyAjax.mockRestore();
        });
    });

    describe('deleteMessages', () => {
        test('deletes multiple messages on confirm', () => {
            const spyConfirm = jest.spyOn(window, 'confirm').mockReturnValue(true);
            document.body.innerHTML = `
                <div class="inbox">Msg 1</div>
                <div class="inbox">Msg 2</div>
                <div id="inbox-messages">2</div>
                <div id="message_detail"></div>
                <div id="messagesAlert"><div id="messagesAlertResponse"></div></div>
                <form id="msgForm">
                    <input name="msg_ids[]" value="1" />
                    <button id="delBtn" type="button"></button>
                </form>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({
                    data: {
                        success: true,
                        type: 'inbox',
                        output: 'All inbox messages deleted'
                    }
                });
            });

            const btn = document.getElementById('delBtn');
            deleteMessages(null, btn);

            expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                data: expect.stringContaining('action=racketmanager_delete_messages')
            }));

            expect(document.querySelectorAll('.inbox')[0].style.display).toBe('none');
            expect(document.getElementById('inbox-messages').textContent).toBe('0');
            expect(document.getElementById('message_detail').textContent).toBe('All inbox messages deleted');

            spyConfirm.mockRestore();
            spyAjax.mockRestore();
        });
    });

    describe('initializeMessages', () => {
        test('delegates click on message list and delete buttons', () => {
            const spyConfirm = jest.spyOn(window, 'confirm').mockReturnValue(true);
            document.body.innerHTML = `
                <div class="message-summary" data-message-id="77"></div>
                <button id="deleteMessage" data-msg-id="77"></button>
                <form id="toolbarForm">
                    <button id="deleteMessages"></button>
                </form>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});

            initializeMessages();

            document.querySelector('.message-summary').click();
            expect(spyAjax).toHaveBeenCalledTimes(1);

            document.getElementById('deleteMessage').click();
            expect(spyAjax).toHaveBeenCalledTimes(2);

            document.getElementById('deleteMessages').click();
            expect(spyAjax).toHaveBeenCalledTimes(3);

            spyConfirm.mockRestore();
            spyAjax.mockRestore();
        });
    });
});
