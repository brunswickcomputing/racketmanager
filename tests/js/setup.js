global.jQuery = require('jquery');
global.$ = global.jQuery;

// Mock Bootstrap modal if not available
global.jQuery.fn.modal = jest.fn().mockImplementation(function() { return this; });

// Mock document.title if not available
if (typeof document !== 'undefined') {
  document.title = 'Test Title';
}
