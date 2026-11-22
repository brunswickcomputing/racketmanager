/**
 * Tab data feature module index
 */

import {
    initializeTabData,


    attachTabDataToGlobal
} from './tab-data.js';

export function initializeTabDataFeature() {
    initializeTabData();
    attachTabDataToGlobal(); // For backward compatibility
}

// Re-export for direct use


export {tabData, tabDataLink} from './tab-data.js';