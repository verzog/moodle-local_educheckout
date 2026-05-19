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

// EduCheckout cart progressive enhancement (static script, no build step).
// Uses the global AMD loader to reach core modules; the add/remove forms
// still POST normally when this script is absent or fails.

(function() {
    "use strict";

    var SELECTORS = {
        ADD_FORM: '[data-educheckout="add-form"]',
        REMOVE_FORM: '[data-educheckout="remove-form"]',
        TOTAL: '[data-educheckout="total"]'
    };

    if (typeof window.require !== "function") {
        return;
    }

    window.require(["core/ajax", "core/notification", "core/str"], function(Ajax, Notification, Str) {
        var call = function(methodname, args) {
            return Ajax.call([{methodname: methodname, args: args}])[0];
        };

        var onAdd = function(e) {
            e.preventDefault();
            var form = e.target;
            var productid = parseInt(form.querySelector('[name="product"]').value, 10);
            var variationField = form.querySelector('[name="variation"]:checked')
                || form.querySelector('[name="variation"]');
            var variationid = variationField ? parseInt(variationField.value, 10) : 0;

            call("local_educheckout_cart_add", {productid: productid, variationid: variationid})
                .then(function() {
                    return Str.get_string("addedtocart", "local_educheckout");
                })
                .then(function(message) {
                    Notification.addNotification({message: message, type: "success"});
                    return message;
                })
                .catch(Notification.exception);
        };

        var onRemove = function(e) {
            e.preventDefault();
            var form = e.target;
            var itemid = parseInt(form.querySelector('[name="item"]').value, 10);

            call("local_educheckout_cart_remove", {itemid: itemid})
                .then(function(result) {
                    var row = form.closest("li");
                    if (row) {
                        row.remove();
                    }
                    var total = document.querySelector(SELECTORS.TOTAL);
                    if (total) {
                        total.textContent = result.total;
                    }
                    return result;
                })
                .catch(Notification.exception);
        };

        document.addEventListener("submit", function(e) {
            if (e.target.matches(SELECTORS.ADD_FORM)) {
                onAdd(e);
            } else if (e.target.matches(SELECTORS.REMOVE_FORM)) {
                onRemove(e);
            }
        });
    });
})();
