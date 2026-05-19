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
 * Product model for the EduCheckout storefront.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educheckout;

/**
 * Representation of a saleable course (product) and its variations.
 */
class product {
    /** @var int the product id */
    protected $id;

    /** @var int the Moodle course id */
    protected $courseid;

    /** @var int|null the category id */
    protected $categoryid;

    /** @var bool whether the product is enabled for sale */
    protected $enabled;

    /** @var int sort order within the catalogue */
    protected $sortorder;

    /** @var string the course full name */
    protected $fullname;

    /** @var string product type: '' = simple, 'session' = scheduled delivery */
    protected $type;

    /** @var string comma-separated tags */
    protected $tags;

    /** @var string custom description (may be empty; falls back to course summary) */
    protected $description;

    /** @var int description text format */
    protected $descriptionformat;

    /** @var array variation records keyed by variation id */
    protected $variations;

    /**
     * Load a product by id.
     *
     * @param int $id the product id
     */
    public function __construct(int $id) {
        global $DB;

        $sql = 'SELECT p.id, p.course_id, p.category_id, p.is_enabled, p.sort_order,
                       p.type, p.tags, p.description, p.description_format,
                       c.fullname
                  FROM {local_educheckout_product} p
                  JOIN {course} c ON c.id = p.course_id
                 WHERE p.id = :id';
        $record = $DB->get_record_sql($sql, ['id' => $id], MUST_EXIST);

        $this->id = (int) $record->id;
        $this->courseid = (int) $record->course_id;
        $this->categoryid = $record->category_id !== null ? (int) $record->category_id : null;
        $this->enabled = (bool) $record->is_enabled;
        $this->sortorder = (int) ($record->sort_order ?? 0);
        $this->type = (string) ($record->type ?? '');
        $this->fullname = (string) $record->fullname;
        $this->tags = (string) ($record->tags ?? '');
        $this->description = (string) ($record->description ?? '');
        $this->descriptionformat = (int) ($record->description_format ?? FORMAT_HTML);
        $this->variations = $DB->get_records('local_educheckout_variation', ['product_id' => $this->id]);
    }

    /**
     * Return the product id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Return the associated course id.
     *
     * @return int
     */
    public function get_course_id(): int {
        return $this->courseid;
    }

    /**
     * Return the category id, or null if uncategorised.
     *
     * @return int|null
     */
    public function get_category_id(): ?int {
        return $this->categoryid;
    }

    /**
     * Whether the product is enabled for sale.
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return $this->enabled;
    }

    /**
     * Return the product type string ('' for simple, 'session' for scheduled delivery).
     *
     * @return string
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Whether this is a session-type product (scheduled delivery with date/time/location/capacity).
     *
     * @return bool
     */
    public function is_session_type(): bool {
        return $this->type === 'session';
    }

    /**
     * Set the product type.
     *
     * @param string $type '' for simple or 'session' for scheduled delivery
     * @return void
     */
    public function set_type(string $type): void {
        global $DB;
        $type = ($type === 'session') ? 'session' : '';
        $DB->set_field('local_educheckout_product', 'type', $type, ['id' => $this->id]);
        $this->type = $type;
    }

    /**
     * Return the sort order.
     *
     * @return int
     */
    public function get_sort_order(): int {
        return $this->sortorder;
    }

    /**
     * Return the course full name.
     *
     * @return string
     */
    public function get_fullname(): string {
        return $this->fullname;
    }

    /**
     * Return the comma-separated tag string.
     *
     * @return string
     */
    public function get_tags(): string {
        return $this->tags;
    }

    /**
     * Return tags as a trimmed array (empty strings filtered out).
     *
     * @return string[]
     */
    public function get_tags_array(): array {
        if ($this->tags === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $this->tags))));
    }

    /**
     * Return the custom description HTML (may be empty).
     *
     * @return string
     */
    public function get_description(): string {
        return $this->description;
    }

    /**
     * Return the description text format.
     *
     * @return int
     */
    public function get_description_format(): int {
        return $this->descriptionformat;
    }

    /**
     * Return all variation records keyed by id.
     *
     * @return array
     */
    public function get_variations(): array {
        return $this->variations;
    }

    /**
     * Return only the enabled variations.
     *
     * @return array variation records keyed by id
     */
    public function get_enabled_variations(): array {
        $enabled = [];
        foreach ($this->variations as $id => $variation) {
            if (!empty($variation->is_enabled)) {
                $enabled[$id] = $variation;
            }
        }
        return $enabled;
    }

    /**
     * Return a single variation record, or null if it does not belong to this product.
     *
     * @param int $variationid the variation id
     * @return \stdClass|null
     */
    public function get_variation(int $variationid): ?\stdClass {
        return $this->variations[$variationid] ?? null;
    }

    /**
     * Return the price for a specific variation, or the lowest enabled price if none given.
     *
     * @param int $variationid the variation id, or 0 for the lowest price
     * @return float
     */
    public function get_price(int $variationid = 0): float {
        if ($variationid > 0 && isset($this->variations[$variationid])) {
            return (float) $this->variations[$variationid]->price;
        }
        $prices = [];
        foreach ($this->get_enabled_variations() as $variation) {
            $prices[] = (float) $variation->price;
        }
        return $prices ? min($prices) : 0.0;
    }

    /**
     * Return the URL for the product image, or null if none is stored.
     *
     * @param \context $context system context
     * @return \moodle_url|null
     */
    public function get_image_url(\context $context): ?\moodle_url {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'local_educheckout', 'product_image', $this->id, '', false);
        if (!empty($files)) {
            $file = reset($files);
            return \moodle_url::make_pluginfile_url(
                $context->id,
                'local_educheckout',
                'product_image',
                $this->id,
                $file->get_filepath(),
                $file->get_filename()
            );
        }

        $coursecontext = \context_course::instance($this->course_id, IGNORE_MISSING);
        if ($coursecontext) {
            $files = $fs->get_area_files($coursecontext->id, 'course', 'overviewfiles', 0, '', false);
            if (!empty($files)) {
                $file = reset($files);
                return \moodle_url::make_pluginfile_url(
                    $coursecontext->id,
                    'course',
                    'overviewfiles',
                    0,
                    $file->get_filepath(),
                    $file->get_filename()
                );
            }
        }

        return null;
    }

    /**
     * Create a new product record for a course.
     *
     * @param int $courseid
     * @return product
     */
    public static function create(int $courseid): product {
        global $DB;
        $id = $DB->insert_record('local_educheckout_product', (object) [
            'course_id' => $courseid,
            'category_id' => null,
            'is_enabled' => 0,
            'variation_count' => 0,
            'sort_order' => 0,
            'type' => '',
            'tags' => '',
            'description' => '',
            'description_format' => FORMAT_HTML,
        ]);
        return new self((int) $id);
    }

    /**
     * Save product metadata (everything except enabled flag and variations).
     *
     * @param int|null $categoryid
     * @param string $tags
     * @param string $description
     * @param int $descriptionformat
     * @param int $sortorder
     * @return void
     */
    public function save(
        ?int $categoryid,
        string $tags,
        string $description,
        int $descriptionformat,
        int $sortorder = 0
    ): void {
        global $DB;
        $DB->update_record('local_educheckout_product', (object) [
            'id' => $this->id,
            'category_id' => $categoryid,
            'tags' => $tags,
            'description' => $description,
            'description_format' => $descriptionformat,
            'sort_order' => $sortorder,
        ]);
        $this->categoryid = $categoryid;
        $this->tags = $tags;
        $this->description = $description;
        $this->descriptionformat = $descriptionformat;
        $this->sortorder = $sortorder;
    }

    /**
     * Enable or disable this product.
     *
     * @param bool $enabled
     * @return void
     */
    public function set_enabled(bool $enabled): void {
        global $DB;
        $DB->set_field('local_educheckout_product', 'is_enabled', (int) $enabled, ['id' => $this->id]);
        $this->enabled = $enabled;
    }

    /**
     * Add a variation to this product.
     *
     * @param string $name
     * @param float $price
     * @param int $duration days until enrolment expires (0 = no limit)
     * @param int $groupid Moodle group to enrol into (0 = none)
     * @param bool $enabled
     * @param int $sessionstarttime unix timestamp for session start (0 = not a session)
     * @param int $sessionendtime unix timestamp for session end
     * @param string $sessionlocation venue or location string
     * @param int $sessioncapacity max seats (0 = unlimited)
     * @return \stdClass the new variation record
     */
    public function add_variation(
        string $name,
        float $price,
        int $duration = 0,
        int $groupid = 0,
        bool $enabled = true,
        int $sessionstarttime = 0,
        int $sessionendtime = 0,
        string $sessionlocation = '',
        int $sessioncapacity = 0
    ): \stdClass {
        global $DB;
        $record = (object) [
            'product_id' => $this->id,
            'is_enabled' => (int) $enabled,
            'name' => $name,
            'price' => $price,
            'duration' => $duration,
            'group_id' => $groupid,
            'session_starttime' => $sessionstarttime,
            'session_endtime' => $sessionendtime,
            'session_location' => $sessionlocation,
            'session_capacity' => $sessioncapacity,
        ];
        $record->id = $DB->insert_record('local_educheckout_variation', $record);
        $this->variations[(int) $record->id] = $record;
        $DB->set_field('local_educheckout_product', 'variation_count', count($this->variations), ['id' => $this->id]);
        return $record;
    }

    /**
     * Update an existing variation.
     *
     * @param int $variationid
     * @param string $name
     * @param float $price
     * @param int $duration
     * @param int $groupid
     * @param bool $enabled
     * @param int $sessionstarttime unix timestamp for session start (0 = not a session)
     * @param int $sessionendtime unix timestamp for session end
     * @param string $sessionlocation venue or location string
     * @param int $sessioncapacity max seats (0 = unlimited)
     * @return void
     */
    public function update_variation(
        int $variationid,
        string $name,
        float $price,
        int $duration = 0,
        int $groupid = 0,
        bool $enabled = true,
        int $sessionstarttime = 0,
        int $sessionendtime = 0,
        string $sessionlocation = '',
        int $sessioncapacity = 0
    ): void {
        global $DB;
        if (!isset($this->variations[$variationid])) {
            return;
        }
        $record = $this->variations[$variationid];
        $record->name = $name;
        $record->price = $price;
        $record->duration = $duration;
        $record->group_id = $groupid;
        $record->is_enabled = (int) $enabled;
        $record->session_starttime = $sessionstarttime;
        $record->session_endtime = $sessionendtime;
        $record->session_location = $sessionlocation;
        $record->session_capacity = $sessioncapacity;
        $DB->update_record('local_educheckout_variation', $record);
        $this->variations[$variationid] = $record;
    }

    /**
     * Delete a variation.
     *
     * @param int $variationid
     * @return void
     */
    public function delete_variation(int $variationid): void {
        global $DB;
        if (!isset($this->variations[$variationid])) {
            return;
        }
        $DB->delete_records('local_educheckout_variation', ['id' => $variationid]);
        unset($this->variations[$variationid]);
        $DB->set_field('local_educheckout_product', 'variation_count', count($this->variations), ['id' => $this->id]);
    }

    /**
     * Delete this product and all its variations.
     *
     * @return void
     */
    public function delete(): void {
        global $DB;
        $DB->delete_records('local_educheckout_variation', ['product_id' => $this->id]);
        $DB->delete_records('local_educheckout_product', ['id' => $this->id]);
        $fs = get_file_storage();
        $context = \context_system::instance();
        $fs->delete_area_files($context->id, 'local_educheckout', 'product_image', $this->id);
    }

    /**
     * Return all enabled products, optionally filtered by category.
     *
     * @param int|null $categoryid filter to a category, or null for all
     * @param int $page zero-based page index
     * @param int $perpage records per page (0 = no limit)
     * @return product[]
     */
    public static function get_enabled(?int $categoryid = null, int $page = 0, int $perpage = 0): array {
        global $DB;

        $params = ['enabled' => 1];
        $where = 'is_enabled = :enabled';
        if ($categoryid !== null) {
            $where .= ' AND category_id = :categoryid';
            $params['categoryid'] = $categoryid;
        }

        $limitfrom = ($perpage > 0) ? ($page * $perpage) : 0;
        $limitnum = ($perpage > 0) ? $perpage : 0;

        $ids = $DB->get_fieldset_select(
            'local_educheckout_product',
            'id',
            $where,
            $params,
            'sort_order ASC, id ASC',
            $limitfrom,
            $limitnum
        );

        $products = [];
        foreach ($ids as $id) {
            $products[(int) $id] = new self((int) $id);
        }
        return $products;
    }

    /**
     * Count enabled products, optionally filtered by category.
     *
     * @param int|null $categoryid
     * @return int
     */
    public static function count_enabled(?int $categoryid = null): int {
        global $DB;
        $params = ['enabled' => 1];
        $where = 'is_enabled = :enabled';
        if ($categoryid !== null) {
            $where .= ' AND category_id = :categoryid';
            $params['categoryid'] = $categoryid;
        }
        return (int) $DB->count_records_select('local_educheckout_product', $where, $params);
    }

    /**
     * Return the product for a given course id, or null if none exists.
     *
     * @param int $courseid
     * @return product|null
     */
    public static function get_by_course(int $courseid): ?product {
        global $DB;
        $id = $DB->get_field('local_educheckout_product', 'id', ['course_id' => $courseid]);
        return $id ? new self((int) $id) : null;
    }
}
