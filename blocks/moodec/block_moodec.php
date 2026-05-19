<?php
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
 * Moodec storefront block: a shortcut to the course store.
 *
 * @package    block_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Block that links to the Moodec course store and lists a few courses for sale.
 *
 * @package    block_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_moodec extends block_base {

    /** @var int Maximum number of courses listed in the block. */
    const PREVIEW_LIMIT = 5;

    /**
     * Initialise the block title.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_moodec');
    }

    /**
     * Allow the block on any page (dashboard, site home, courses).
     *
     * @return array
     */
    public function applicable_formats() {
        return ['all' => true];
    }

    /**
     * Build the block body.
     *
     * @return \stdClass|string
     */
    public function get_content() {
        global $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // The store requires a logged-in, non-guest user and the local_moodec plugin.
        if (!isloggedin() || isguestuser() || !class_exists('\local_moodec\product')) {
            return $this->content;
        }

        $catalogueurl = new moodle_url('/local/moodec/index.php');
        $items = [];

        try {
            $products = \local_moodec\product::get_enabled(null, 0, self::PREVIEW_LIMIT);
        } catch (\Throwable $e) {
            $products = [];
        }

        foreach ($products as $product) {
            $producturl = new moodle_url('/local/moodec/product.php', ['id' => $product->get_id()]);
            $price = get_string(
                'price_from',
                'local_moodec',
                format_float($product->get_price(), 2)
            );
            $name = html_writer::link($producturl, format_string($product->get_fullname()));
            $items[] = html_writer::tag(
                'li',
                $name . ' ' . html_writer::tag('span', $price, ['class' => 'text-muted small'])
            );
        }

        if (!empty($items)) {
            $this->content->text .= html_writer::tag(
                'ul',
                implode('', $items),
                ['class' => 'list-unstyled mb-2']
            );
        } else {
            $this->content->text .= html_writer::tag(
                'p',
                get_string('catalogue_empty', 'local_moodec'),
                ['class' => 'text-muted']
            );
        }

        $this->content->footer = html_writer::link(
            $catalogueurl,
            get_string('browsestore', 'block_moodec'),
            ['class' => 'btn btn-primary btn-sm']
        );

        return $this->content;
    }

    /**
     * Only one instance is needed per page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * This block has no global configuration.
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }
}
