// widget-carousel.js
// Initializes the clubs carousel for the RacketManager widget within the main module bundle.
// This replaces the legacy enqueue of wp-content/plugins/racketmanager/js/widget.js.

export function initializeWidgetCarousel() {
  // Presence check: only run when the widget markup exists
  const container = document.querySelector('#clubs.roll-club');
  if (!container) return;

  // Avoid double initialization
  if (container.dataset.rmCarouselInit === '1') return;

  // Ensure the owlCarouselFork plugin is available
  if (typeof jQuery === 'undefined' || typeof jQuery.fn.owlCarouselFork !== 'function') {
    // Graceful no-op if the carousel plugin isn't present
    return;
  }

  const $ = jQuery;
  const $container = $(container);

  try {
    $container.owlCarouselFork({
      navigation: false,
      pagination: true,
      responsive: true,
      items: 1,
      itemsDesktop: [3000, 1],
      itemsDesktopSmall: [1400, 1],
      itemsTablet: [970, 1],
      itemsTabletSmall: [600, 1],
      itemsMobile: [360, 1],
      touchDrag: true,
      mouseDrag: true,
      autoHeight: true,
      autoPlay: $container.data('autoplay'),
    });
    container.dataset.rmCarouselInit = '1';
  } catch (e) {
    try { console.warn('Failed to initialize widget carousel:', e); } catch (_) {}
  }
}
