/**
 * Tests for admin/draggable.js entrypoint.
 */
import { initMatchDraggable } from '../../../src/js/admin/features/draggable/match-draggable.js';

jest.mock('../../../src/js/admin/features/draggable/match-draggable.js', () => ({
  initMatchDraggable: jest.fn(),
}));

describe('Admin draggable entrypoint', () => {
  it('calls initMatchDraggable on import when document is present', async () => {
    await import('../../../src/js/admin/draggable.js');

    expect(initMatchDraggable).toHaveBeenCalledWith(document);
  });
});
