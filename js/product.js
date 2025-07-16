'use strict';

require(['jquery'], function($) {

    /**
     * Format amount using local currency.
     * @param {string|number} amount
     * @param {string} currencyCode
     * @returns {string}
     */
    const formatCurrency = (amount, currencyCode = 'AUD') => {
        const number = parseFloat(amount);
        if (isNaN(number)) return amount;

        return new Intl.NumberFormat(navigator.language || 'en-AU', {
            style: 'currency',
            currency: currencyCode,
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

        const currency = $product.data('currency') || 'AUD';

        // --- Update Price ---
        const $priceContainer = $product.find('.product-price');
        const $amount = $priceContainer.find('.amount');
        const rawPrice = $priceContainer.data(`tier-${variationId}`) ?? $priceContainer.data('tier-default');

        if (rawPrice !== undefined) {
            const formatted = formatCurrency(rawPrice, currency);
            $amount.text(formatted);
            $amount.attr('title', `Price: ${formatted}`);
        }

        // --- Update Duration ---
        const $durationContainer = $product.find('.product-duration');
        const rawDuration = $durationContainer.data(`tier-${variationId}`) ?? $durationContainer.data('tier-default');

        if (rawDuration !== undefined) {
            $durationContainer.text(rawDuration);
            $durationContainer.attr('title', `Course duration: ${rawDuration}`);
        }
    });
});
