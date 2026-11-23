import { getPlayerDetails, setPlayerDetails } from '../../../src/js/utils/player-utils.js';

describe('player-utils', () => {
    let originalAjax;

    beforeEach(() => {
        originalAjax = jQuery.ajax;
        jQuery.ajax = jest.fn();
        document.body.innerHTML = `
            <input type="text" id="player-name" />
            <input type="hidden" id="player-id" />
            <input type="text" id="contact-no" />
            <input type="text" id="contact-email" />
            <input type="text" id="player-btm" />
            <div id="notify" style="display: none;"></div>
            <input type="text" id="autocomplete-input" class="ui-autocomplete-input" />
            <input type="text" id="plain-input" />
        `;
    });

    afterEach(() => {
        jQuery.ajax = originalAjax;
        jest.restoreAllMocks();
    });

    describe('getPlayerDetails', () => {
        test('successfully fetches and parses player details JSON', () => {
            const playersData = [
                { id: 1, name: 'John Doe', btm: '123456' },
                { id: 2, name: 'Jane Smith', btm: '654321' }
            ];

            jQuery.ajax.mockImplementation((options) => {
                expect(options.type).toBe('POST');
                expect(options.url).toBe('/wp-admin/admin-ajax.php');
                expect(options.async).toBe(false);
                expect(options.dataType).toBe('json');
                expect(options.data).toEqual({
                    action: 'racketmanager_get_player_details',
                    security: 'test-nonce',
                    type: 'name',
                    name: 'John',
                    club: 10,
                    partnerGender: 'F'
                });

                options.success({
                    success: true,
                    data: JSON.stringify(playersData)
                });
            });

            const results = getPlayerDetails('name', 'John', 10, '#notify', 'F');
            expect(results).toEqual(playersData);
            expect(jQuery('#notify').css('display')).toBe('none');
        });

        test('handles malformed JSON in successful response data', () => {
            const consoleSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

            jQuery.ajax.mockImplementation((options) => {
                options.success({
                    success: true,
                    data: 'invalid json {'
                });
            });

            const results = getPlayerDetails('name', 'BadJson');
            expect(consoleSpy).toHaveBeenCalledWith('Error parsing player data:', expect.any(Error));
            expect(results).toEqual([]);
        });

        test('handles unsuccessful response with notifyField provided', () => {
            jQuery.ajax.mockImplementation((options) => {
                options.success({
                    success: false,
                    data: 'No players found for this club'
                });
            });

            const results = getPlayerDetails('name', 'Missing', null, '#notify');
            expect(results).toEqual([]);
            expect(jQuery('#notify').text()).toBe('No players found for this club');
            expect(jQuery('#notify').hasClass('error')).toBe(true);
            expect(jQuery('#notify').css('display')).not.toBe('none');
        });

        test('handles unsuccessful response with default message when response.data is falsy', () => {
            jQuery.ajax.mockImplementation((options) => {
                options.success({
                    success: false,
                    data: ''
                });
            });

            const results = getPlayerDetails('name', 'Missing', null, '#notify');
            expect(results).toEqual([]);
            expect(jQuery('#notify').text()).toBe('Error fetching player details');
        });

        test('handles unsuccessful response when notifyField is null', () => {
            jQuery.ajax.mockImplementation((options) => {
                options.success({
                    success: false,
                    data: 'Some error'
                });
            });

            const results = getPlayerDetails('name', 'Missing', null, null);
            expect(results).toEqual([]);
            expect(jQuery('#notify').css('display')).toBe('none');
        });

        test('handles network/AJAX error with notifyField', () => {
            const consoleSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

            jQuery.ajax.mockImplementation((options) => {
                options.error({}, 'error', 'Internal Server Error');
            });

            const results = getPlayerDetails('btm', '999999', null, '#notify');
            expect(consoleSpy).toHaveBeenCalledWith('Player lookup error:', 'Internal Server Error');
            expect(results).toEqual([]);
            expect(jQuery('#notify').text()).toBe('Error connecting to server');
            expect(jQuery('#notify').hasClass('error')).toBe(true);
            expect(jQuery('#notify').css('display')).not.toBe('none');
        });

        test('handles network/AJAX error without notifyField', () => {
            const consoleSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

            jQuery.ajax.mockImplementation((options) => {
                options.error({}, 'error', 'Network failure');
            });

            const results = getPlayerDetails('btm', '999999');
            expect(consoleSpy).toHaveBeenCalled();
            expect(results).toEqual([]);
        });
    });

    describe('setPlayerDetails', () => {
        test('clears form fields when ui.item is null', () => {
            jQuery('#player-name').val('Existing Name');
            jQuery('#player-id').val('456');
            jQuery('#contact-no').val('0123456789');
            jQuery('#contact-email').val('old@example.com');
            jQuery('#player-btm').val('987654');

            setPlayerDetails(
                { item: null },
                '#player-name',
                '#player-id',
                '#contact-no',
                '#contact-email',
                '#player-btm'
            );

            expect(jQuery('#player-name').val()).toBe('');
            expect(jQuery('#player-id').val()).toBe('');
            expect(jQuery('#contact-no').val()).toBe('');
            expect(jQuery('#contact-email').val()).toBe('');
            expect(jQuery('#player-btm').val()).toBe('');
        });

        test('populates form fields when ui.item contains player details', () => {
            const ui = {
                item: {
                    value: 'Alice Cooper',
                    playerId: '101',
                    contactno: '07123456789',
                    user_email: 'alice@example.com',
                    btm: '112233'
                }
            };

            setPlayerDetails(
                ui,
                '#player-name',
                '#player-id',
                '#contact-no',
                '#contact-email',
                '#player-btm'
            );

            expect(jQuery('#player-name').val()).toBe('Alice Cooper');
            expect(jQuery('#player-id').val()).toBe('101');
            expect(jQuery('#contact-no').val()).toBe('07123456789');
            expect(jQuery('#contact-email').val()).toBe('alice@example.com');
            expect(jQuery('#player-btm').val()).toBe('112233');
        });
    });

});
