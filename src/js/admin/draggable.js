/**
 * Tournament and cup match drag-and-drop scheduler entrypoint.
 *
 * @module admin/draggable
 */

import { initMatchDraggable } from './features/draggable/index.js';

if (typeof document !== 'undefined') {
    initMatchDraggable(document);
}
