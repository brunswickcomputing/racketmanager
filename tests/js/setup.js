global.jQuery = require('jquery');
global.$ = global.jQuery;

// Mock window.ajax_var to avoid console noise
global.window.ajax_var = {
    url: '/wp-admin/admin-ajax.php',
    ajax_nonce: 'test-nonce'
};

// Mock AJAX
global.jQuery.ajax = jest.fn();

// Mock Bootstrap modal if not available
global.jQuery.fn.modal = jest.fn().mockImplementation(function() { return this; });

// Mock document.title if not available
if (typeof document !== 'undefined') {
  document.title = 'Test Title';
}
