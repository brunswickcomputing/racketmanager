global.jQuery = require('jquery');
global.$ = global.jQuery;

// Mock document.title if not available
if (typeof document !== 'undefined') {
  document.title = 'Test Title';
}
