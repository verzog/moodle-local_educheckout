// catalogue.js - Updated for Moodle 5.0 standards
$(document).ready(function () {

    // Auto-submit filter form on selection change
    $('.filter-bar select').on('change', function () {
        $(this).closest('form').trigger('submit');
    });

    // Update price and duration for variable products
    $('.product-form .product-tier').on('change', function () {
        const $select = $(this);
        const variationId = $select.val();
        const $product = $select.closest('.product-item');

        // Update product price
        const newPrice = $product.find('.product-price').data(`tier-${variationId}`);
        if (newPrice !== undefined) {
            $product.find('.product-price .amount').text(newPrice);
        }

        // Update product duration
        const newDuration = $product.find('.product-duration').data(`tier-${variationId}`);
        if (newDuration !== undefined) {
            $product.find('.product-duration').text(newDuration);
        }
    });
});
