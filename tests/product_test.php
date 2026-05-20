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
 * Tests for the EduCheckout product model.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educheckout;

/**
 * Unit tests for \local_educheckout\product.
 *
 * @covers \local_educheckout\product
 */
final class product_test extends \advanced_testcase {
    /**
     * Create, persist and reload a product record.
     *
     * @return void
     */
    public function test_create_and_load(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $product = product::create((int) $course->id);

        $this->assertSame((int) $course->id, $product->get_course_id());
        $this->assertFalse($product->is_enabled());
        $this->assertSame(0, $product->get_sort_order());
        $this->assertNull($product->get_category_id());
        $this->assertSame('', $product->get_type());

        $reloaded = new product($product->get_id());
        $this->assertSame($product->get_id(), $reloaded->get_id());
        $this->assertSame((int) $course->id, $reloaded->get_course_id());
    }

    /**
     * save() persists metadata and the changes survive a reload.
     *
     * @return void
     */
    public function test_save_metadata(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $catid = (int) $DB->insert_record('local_educheckout_category', (object) [
            'name' => 'Compliance',
            'description' => '',
            'sortorder' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $product = product::create((int) $course->id);
        $product->save($catid, 'compliance,induction', '<p>Test</p>', FORMAT_HTML, 3);

        $this->assertSame($catid, $product->get_category_id());
        $this->assertSame('compliance,induction', $product->get_tags());
        $this->assertSame('<p>Test</p>', $product->get_description());
        $this->assertSame(3, $product->get_sort_order());

        $reloaded = new product($product->get_id());
        $this->assertSame($catid, $reloaded->get_category_id());
        $this->assertSame('compliance,induction', $reloaded->get_tags());
        $this->assertSame(3, $reloaded->get_sort_order());
    }

    /**
     * set_enabled() toggles the flag in memory and in the database.
     *
     * @return void
     */
    public function test_set_enabled(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $product = product::create((int) $course->id);

        $this->assertFalse($product->is_enabled());
        $product->set_enabled(true);
        $this->assertTrue($product->is_enabled());

        $reloaded = new product($product->get_id());
        $this->assertTrue($reloaded->is_enabled());

        $product->set_enabled(false);
        $this->assertFalse($product->is_enabled());
    }

    /**
     * Variations can be added, updated and deleted; price resolution follows.
     *
     * @return void
     */
    public function test_variations_crud(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $product = product::create((int) $course->id);

        $this->assertEmpty($product->get_variations());
        $this->assertSame(0.0, $product->get_price());

        $var = $product->add_variation('Standard', 99.0, 365, 0, true);
        $varid = (int) $var->id;

        $this->assertCount(1, $product->get_variations());
        $this->assertSame(99.0, $product->get_price($varid));
        $this->assertSame(99.0, $product->get_price());

        $product->update_variation($varid, 'Standard', 149.0, 365, 0, true);
        $this->assertSame(149.0, $product->get_price($varid));
        $this->assertSame(149.0, $product->get_price());

        $product->update_variation($varid, 'Standard', 149.0, 365, 0, false);
        $this->assertCount(0, $product->get_enabled_variations());
        $this->assertSame(0.0, $product->get_price());

        $product->delete_variation($varid);
        $this->assertEmpty($product->get_variations());
    }

    /**
     * get_price() returns the lowest enabled variation price across multiple variations.
     *
     * @return void
     */
    public function test_get_price_returns_lowest_enabled(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $product = product::create((int) $course->id);

        $product->add_variation('Early bird', 79.0, 0, 0, true);
        $product->add_variation('Standard', 129.0, 0, 0, true);

        $this->assertSame(79.0, $product->get_price());
    }

    /**
     * get_enabled() returns only enabled products; category filter narrows the result.
     *
     * @return void
     */
    public function test_get_enabled_and_count(): void {
        $this->resetAfterTest();
        global $DB;

        $catid = (int) $DB->insert_record('local_educheckout_category', (object) [
            'name' => 'Safety',
            'description' => '',
            'sortorder' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $p1 = product::create((int) $course1->id);
        $p1->set_enabled(true);
        $p1->save($catid, '', '', FORMAT_HTML, 0);

        $p2 = product::create((int) $course2->id);
        $p2->set_enabled(true);

        product::create((int) $course3->id); // Disabled, excluded from get_enabled().

        $this->assertCount(2, product::get_enabled());
        $this->assertSame(2, product::count_enabled());

        $this->assertCount(1, product::get_enabled($catid));
        $this->assertSame(1, product::count_enabled($catid));

        $incat = product::get_enabled($catid);
        $this->assertArrayHasKey($p1->get_id(), $incat);
    }

    /**
     * get_by_course() returns the matching product or null when none exists.
     *
     * @return void
     */
    public function test_get_by_course(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $this->assertNull(product::get_by_course((int) $course->id));

        $product = product::create((int) $course->id);
        $loaded = product::get_by_course((int) $course->id);

        $this->assertNotNull($loaded);
        $this->assertSame($product->get_id(), $loaded->get_id());
        $this->assertSame((int) $course->id, $loaded->get_course_id());
    }

    /**
     * get_tags_array() parses the comma-separated tag string, trimming whitespace.
     *
     * @return void
     */
    public function test_tags_array(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $product = product::create((int) $course->id);

        $this->assertSame([], $product->get_tags_array());

        $product->save(null, 'compliance, induction, safety', '', FORMAT_HTML, 0);
        $this->assertSame(['compliance', 'induction', 'safety'], $product->get_tags_array());

        $product->save(null, '', '', FORMAT_HTML, 0);
        $this->assertSame([], $product->get_tags_array());
    }

    /**
     * set_type() accepts 'session' and coerces any other value to the empty string.
     *
     * @return void
     */
    public function test_type(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $product = product::create((int) $course->id);

        $this->assertFalse($product->is_session_type());
        $this->assertSame('', $product->get_type());

        $product->set_type('session');
        $this->assertTrue($product->is_session_type());
        $this->assertSame('session', $product->get_type());

        $product->set_type('unknown');
        $this->assertFalse($product->is_session_type());
        $this->assertSame('', $product->get_type());
    }
}
