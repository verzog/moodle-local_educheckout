// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * AJAX progressive enhancement for the Moodec cart.
 *
 * @module     local_moodec/cart
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';

const SELECTORS = {
    ADD_FORM: '[data-moodec="add-form"]',
    REMOVE_FORM: '[data-moodec="remove-form"]',
    TOTAL: '[data-moodec="total"]',
};

/**
 * Call a single cart web service method.
 *
 * @param {String} methodname The web service method name.
 * @param {Object} args The method arguments.
 * @returns {Promise} Resolves with the web service result.
 */
const call = (methodname, args) => {
    return Ajax.call([{methodname, args}])[0];
};

/**
 * Handle an add-to-cart form submission.
 *
 * @param {Event} e The submit event.
 * @returns {void}
 */
const onAdd = (e) => {
    e.preventDefault();
    const form = e.target;
    const productid = parseInt(form.querySelector('[name="product"]').value, 10);
    const variationField = form.querySelector('[name="variation"]:checked')
        || form.querySelector('[name="variation"]');
    const variationid = variationField ? parseInt(variationField.value, 10) : 0;

    call('local_moodec_cart_add', {productid, variationid})
        .then(() => getString('addedtocart', 'local_moodec'))
        .then((message) => {
            Notification.addNotification({message, type: 'success'});
            return message;
        })
        .catch(Notification.exception);
};

/**
 * Handle a remove-from-cart form submission.
 *
 * @param {Event} e The submit event.
 * @returns {void}
 */
const onRemove = (e) => {
    e.preventDefault();
    const form = e.target;
    const itemid = parseInt(form.querySelector('[name="item"]').value, 10);

    call('local_moodec_cart_remove', {itemid})
        .then((result) => {
            const row = form.closest('li');
            if (row) {
                row.remove();
            }
            const total = document.querySelector(SELECTORS.TOTAL);
            if (total) {
                total.textContent = result.total;
            }
            return result;
        })
        .catch(Notification.exception);
};

/**
 * Initialise the cart enhancement.
 *
 * @returns {void}
 */
export const init = () => {
    document.addEventListener('submit', (e) => {
        if (e.target.matches(SELECTORS.ADD_FORM)) {
            onAdd(e);
        } else if (e.target.matches(SELECTORS.REMOVE_FORM)) {
            onRemove(e);
        }
    });
};
