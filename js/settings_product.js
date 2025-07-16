'use strict';

require(['jquery'], function($) {

    $(document).ready(function () {

        const $productType = $('#id_product_type');
        const $formatField = $('#id_format');

        if (!$productType.length || !$formatField.length) {
            console.warn('Moodec settings: Required form fields not found.');
            return;
        }

        const toggleVariationHeaders = function () {
            const fieldCount = parseInt($formatField.val(), 10) || 0;
            const isSimple = $productType.val() === 'PRODUCT_TYPE_SIMPLE';

            for (let i = fieldCount; i > 1; i--) {
                const $header = $('#id_product_variation_header_' + i);
                if ($header.length) {
                    $header.toggle(!isSimple);
                }
            }
        };

        $productType.on('change', toggleVariationHeaders);

        // Trigger once on page load to set correct state
        toggleVariationHeaders();
    });

});
