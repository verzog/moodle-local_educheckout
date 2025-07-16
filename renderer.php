<?php
/**
 * Moodec Renderer
 * Updated for Moodle 5.0 / Bootstrap 5
 *
 * Replace in /local/moodec/classes/output/renderer.php or equivalent
 */

namespace local_moodec\output;

use plugin_renderer_base;
use html_writer;
use moodle_url;

class renderer extends plugin_renderer_base {

    /**
     * Render add to cart button
     */
    public function render_add_to_cart_button($productid) {
        $url = new moodle_url('/local/moodec/actions/add_to_cart.php', ['id' => $productid]);
        return html_writer::start_tag('form', ['method' => 'post', 'action' => $url]) .
            html_writer::empty_tag('input', [
                'type' => 'submit',
                'class' => 'btn btn-primary',
                'value' => get_string('add_to_cart', 'local_moodec')
            ]) .
            html_writer::end_tag('form');
    }

    /**
     * Render already enrolled button (disabled)
     */
    public function render_enrolled_button() {
        return html_writer::tag('button', get_string('already_enrolled', 'local_moodec'), [
            'class' => 'btn btn-outline-secondary',
            'disabled' => 'disabled'
        ]);
    }

    /**
     * Render proceed to checkout button
     */
    public function render_proceed_to_checkout() {
        $url = new moodle_url('/local/moodec/pages/checkout.php');
        return html_writer::link($url, get_string('proceed_to_checkout', 'local_moodec'), [
            'class' => 'btn btn-success']
        );
    }

    /**
     * Render remove from cart button
     */
    public function render_remove_from_cart_button($itemid) {
        $url = new moodle_url('/local/moodec/actions/remove_from_cart.php', ['itemid' => $itemid]);
        return html_writer::start_tag('form', ['method' => 'post', 'action' => $url]) .
            html_writer::empty_tag('input', [
                'type' => 'submit',
                'class' => 'btn btn-danger',
                'value' => get_string('remove', 'local_moodec')
            ]) .
            html_writer::end_tag('form');
    }

    /**
     * Render back to shop link
     */
    public function render_back_to_shop() {
        $url = new moodle_url('/local/moodec/pages/catalogue.php');
        return html_writer::link($url, get_string('back_to_shop', 'local_moodec'), [
            'class' => 'btn btn-outline-primary back-to-shop']
        );
    }

    /**
     * Render cart overview title
     */
    public function render_cart_overview_title() {
        return html_writer::tag('h2', get_string('cart_title', 'local_moodec'), [
            'class' => 'mb-4 fs-4 text-center']
        );
    }

    /**
     * Render empty catalogue message
     */
    public function render_catalogue_empty_message() {
        return html_writer::tag('div', get_string('catalogue_empty', 'local_moodec'), [
            'class' => 'alert alert-warning text-center']
        );
    }

    /**
     * Render product card
     */
    public function render_product_card($product) {
        $output = html_writer::start_div('card mb-4 product-item');
        $output .= html_writer::start_div('card-body');

        $output .= html_writer::tag('h5', $product->get_fullname(), ['class' => 'card-title']);
        $output .= html_writer::div(format_text($product->get_description(), FORMAT_HTML), 'card-text');

        if ($product->has_image()) {
            $output .= html_writer::empty_tag('img', [
                'src' => $product->get_image_url(),
                'alt' => $product->get_fullname(),
                'class' => 'card-img-top my-3'
            ]);
        }

        $output .= html_writer::div(
            get_string('price', 'local_moodec') . ': ' . $product->get_price_display(),
            'mb-3 fw-bold'
        );

        $output .= $this->render_add_to_cart_button($product->get_id());
        $output .= html_writer::end_div(); // .card-body
        $output .= html_writer::end_div(); // .card

        return $output;
    }

    /**
     * Render transaction table
     */
    public function render_transaction_table($transactions) {
        if (empty($transactions)) {
            return html_writer::tag('p', get_string('notransactions', 'local_moodec'), ['class' => 'text-muted']);
        }

        $output = html_writer::start_tag('table', ['class' => 'table table-striped table-hover transaction_table']);
        $output .= html_writer::start_tag('thead');
        $output .= html_writer::tag('tr',
            html_writer::tag('th', get_string('transactionid', 'local_moodec')) .
            html_writer::tag('th', get_string('date', 'local_moodec')) .
            html_writer::tag('th', get_string('amount', 'local_moodec')) .
            html_writer::tag('th', get_string('status', 'local_moodec'))
        );
        $output .= html_writer::end_tag('thead');

        $output .= html_writer::start_tag('tbody');
        foreach ($transactions as $tx) {
            $output .= html_writer::tag('tr',
                html_writer::tag('td', $tx->id) .
                html_writer::tag('td', userdate($tx->timecreated)) .
                html_writer::tag('td', format_string($tx->amount)) .
                html_writer::tag('td', format_string($tx->status))
            );
        }
        $output .= html_writer::end_tag('tbody');
        $output .= html_writer::end_tag('table');

        return $output;
    }
}
