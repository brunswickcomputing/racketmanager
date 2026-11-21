/**
 * @jest-environment jsdom
 */

import { initializeWidgetCarousel } from '../../../../src/js/features/widgets/widget-carousel.js';

describe('features/widgets/widget-carousel', () => {
  let mockOwl;

  beforeEach(() => {
    document.body.innerHTML = `
      <div id="clubs" class="roll-club" data-autoplay="true"></div>
    `;
    window.jQuery = window.$ = require('jquery');
    mockOwl = jest.fn();
    jQuery.fn.owlCarouselFork = mockOwl;
  });

  afterEach(() => {
    delete jQuery.fn.owlCarouselFork;
    jest.restoreAllMocks();
  });

  test('does nothing when container is missing', () => {
    document.getElementById('clubs').remove();
    initializeWidgetCarousel();
    expect(mockOwl).not.toHaveBeenCalled();
  });

  test('does nothing when container is already initialized', () => {
    const container = document.getElementById('clubs');
    container.dataset.rmCarouselInit = '1';
    initializeWidgetCarousel();
    expect(mockOwl).not.toHaveBeenCalled();
  });

  test('does nothing when owlCarouselFork is not a function', () => {
    delete jQuery.fn.owlCarouselFork;
    initializeWidgetCarousel();
    const container = document.getElementById('clubs');
    expect(container.dataset.rmCarouselInit).toBeUndefined();
  });

  test('initializes carousel with options and marks dataset as initialized', () => {
    initializeWidgetCarousel();
    const container = document.getElementById('clubs');
    expect(mockOwl).toHaveBeenCalledWith(expect.objectContaining({
      navigation: false,
      pagination: true,
      items: 1,
      autoPlay: true
    }));
    expect(container.dataset.rmCarouselInit).toBe('1');
  });

  test('logs warning when owlCarouselFork throws an error', () => {
    mockOwl.mockImplementation(() => {
      throw new Error('Carousel initialization failed');
    });
    const warnSpy = jest.spyOn(console, 'warn').mockImplementation(() => {});

    initializeWidgetCarousel();

    expect(warnSpy).toHaveBeenCalledWith(
      'Failed to initialize widget carousel:',
      expect.any(Error)
    );
  });

  test('suppresses error when owlCarouselFork throws and console.warn also throws', () => {
    mockOwl.mockImplementation(() => {
      throw new Error('Carousel initialization failed');
    });
    const warnSpy = jest.spyOn(console, 'warn').mockImplementation(() => {
      throw new Error('Blocked console');
    });

    expect(() => {
      initializeWidgetCarousel();
    }).not.toThrow();

    expect(warnSpy).toHaveBeenCalled();
  });
});
