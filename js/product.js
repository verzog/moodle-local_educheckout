// catalogue.js - Enhanced with tooltips and price formatting
'use strict';

require(['jquery'], function($) {

    // Format price as currency (AUD used as example – adjust as needed)
    const formatCurrency = amount => {
        const number = parseFloat(amount);
        if (isNaN(number)) return amount;
        return new Intl.NumberFormat('en-AU', {
            style: 'currency',
            currency: 'AUD',
            minimumFractionDigits: 2
        }).format(number);
    };

    // Auto-submit filter form when selection changes
    $(document).on('change', '.filter-bar select', function () {
        const $form = $(this).closest('form');
        if ($form.length) {
            $form.trigger('submit');
        }
    });

    // Update price and duration when product variation is selected
    $(document).on('change', '.product-form .product-tier', function () {
        const $select = $(this);
        const variationId = $select.val();
        const $product = $select.closest('.product-item');

        if (!$product.length) return;

        // Update price
        const $priceContainer = $product.find('.product-price');
        const $amount = $priceContainer.find('.amount');
        const rawPrice = $priceContainer.data(`tier-${variationId}`) ?? $priceContainer.data('tier-default');

        if (rawPrice !== undefined) {
            const formatted = formatCurrency(rawPrice);
            $amount.text(formatted);
            $amount.attr('title', `Price: ${formatted}`);
        }

        // Update duration
        const $durationContainer = $product.find('.product-duration');
        const rawDuration = $durationContainer.data(`tier-${variationId}`) ?? $durationContainer.data('tier-default');

        if (rawDuration !== undefined) {
            $durationContainer.text(rawDuration);
            $durationContainer.attr('title', `Course duration: ${rawDuration}`);
        }
    });

});
