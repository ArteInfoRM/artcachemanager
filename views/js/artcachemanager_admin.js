/**
 * Art Cache Manager — back-office scripts
 *
 * @author    Tecnoacquisti.com <helpdesk@tecnoacquisti.com>
 * @copyright 2009-2026 Tecnoacquisti.com
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License (AFL) v. 3.0
 */

(function () {
    'use strict';

    /**
     * Apply the progress-bar widths from data-pct attributes.
     * Widths cannot be set in the Smarty template (inline style attributes
     * with dynamic values are rejected by the PS marketplace validator),
     * so they are stored in data-pct and applied here at runtime.
     */
    function applyProgressBars() {
        document.querySelectorAll('.artcm-progress-bar[data-pct]').forEach(function (el) {
            el.style.width = parseFloat(el.getAttribute('data-pct')) + '%';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyProgressBars);
    } else {
        applyProgressBars();
    }
}());
