<?php
/**
 * Moodec Cart (Updated for Moodle 5.0)
 *
 * @package     local_moodec
 * @author       Vernon Spain formerly Thomas Threadgold
 * @copyright    2015 LearningWorks Ltd
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec; // Add namespace to support autoloading

use context_course;
use core\session\manager as session_manager;

defined('MOODLE_INTERNAL') || die();

class cart {

    const STORAGE_ID = 'MoodecCart';
    const CART_VERSION = '2015092300';

    protected array $_products = [];
    protected float $_cartTotal = 0;
    protected ?int $_transactionId = null;
    protected int $_lastUpdated = 0;

    public function __construct() {
        global $USER;

        $sessionTime = $cookieTime = 0;
        $sessionData = $cookieData = [];

        if (!empty($_SESSION[self::STORAGE_ID])) {
            [$sessionVersion, $sessionData] = @unserialize($_SESSION[self::STORAGE_ID]);
            if ($sessionVersion === self::CART_VERSION) {
                [$this->_products, $this->_cartTotal, $this->_transactionId, $sessionTime] = $sessionData;
            } else {
                unset($_SESSION[self::STORAGE_ID]);
            }
        }

        if (!empty($_COOKIE[self::STORAGE_ID])) {
            [$cookieVersion, $cookieData] = @unserialize($_COOKIE[self::STORAGE_ID]);
            if ($cookieVersion === self::CART_VERSION) {
                [$cookieProducts, $cookieCartTotal, $cookieTransactionId, $cookieTime] = $cookieData;
                if ($cookieTime > $sessionTime) {
                    $this->_products = $cookieProducts;
                    $this->_cartTotal = $cookieCartTotal;
                    $this->_transactionId = $cookieTransactionId;
                    $this->_lastUpdated = $cookieTime;
                }
            } else {
                unset($_COOKIE[self::STORAGE_ID]);
            }
        }

        foreach ($this->_products as $pID => $vID) {
            $newProduct = \local_moodec_get_product($pID);
            if ($newProduct->get_type() === PRODUCT_TYPE_VARIABLE && $vID === 0) {
                $this->clear();
                break;
            }
        }

        if ($this->get_transaction_id()) {
            try {
                $transaction = new \MoodecTransaction($this->get_transaction_id());
                if ($transaction->get_status() === \MoodecTransaction::STATUS_COMPLETE) {
                    $this->clear();
                }
            } catch (\Exception $e) {
                $this->_transactionId = null;
                $this->clear();
            }
        }
    }

    private function update(): void {
        $this->_lastUpdated = time();
        $cartData = [$this->_products, $this->_cartTotal, $this->_transactionId, $this->_lastUpdated];
        $data = serialize([self::CART_VERSION, $cartData]);
        $_SESSION[self::STORAGE_ID] = $data;
        setcookie(self::STORAGE_ID, $data, time() + 31536000, '/');
    }

    public function refresh(): array {
        global $USER;

        $itemsToRemove = [];

        foreach ($this->_products as $pID => $vID) {
            $product = \local_moodec_get_product($pID);

            if (!$product->is_enabled()) {
                $itemsToRemove[] = $pID;
            } elseif ($product->get_type() === PRODUCT_TYPE_SIMPLE && $vID !== 0) {
                $this->_products[$pID] = 0;
            } elseif ($product->get_type() === PRODUCT_TYPE_VARIABLE && !$product->get_variation($vID)) {
                $itemsToRemove[] = $pID;
            } elseif (isloggedin()) {
                $context = context_course::instance($product->get_course_id());
                if (is_enrolled($context, $USER, '', true)) {
                    $itemsToRemove[] = $pID;
                }
            }
        }

        foreach ($itemsToRemove as $id) {
            $this->remove($id);
        }

        return $itemsToRemove;
    }

    public function clear(): void {
        $this->_products = [];
        $this->_cartTotal = 0;
        $this->_transactionId = null;
        $this->_lastUpdated = time();
        $this->update();
    }

    public function check(int $id): bool {
        return array_key_exists($id, $this->_products);
    }

    public function add(int $p, int $v = 0): bool {
        if ($this->check($p)) {
            return false;
        }

        $product = \local_moodec_get_product($p);
        $this->_products[$p] = $v;

        $this->_cartTotal += ($product->get_type() === PRODUCT_TYPE_VARIABLE)
            ? $product->get_variation($v)->get_price()
            : $product->get_price();

        $this->update();
        return true;
    }

    public function remove(int $id): bool {
        if (!$this->check($id)) {
            return false;
        }

        $product = \local_moodec_get_product($id);
        $v = $this->_products[$id];

        $this->_cartTotal -= ($product->get_type() === PRODUCT_TYPE_VARIABLE)
            ? $product->get_variation($v)->get_price()
            : $product->get_price();

        unset($this->_products[$id]);
        if (empty($this->_products)) {
            $this->_transactionId = null;
        }

        $this->update();
        return true;
    }

    public function get(): array {
        return $this->_products;
    }

    public function get_total(bool $format = true): float|string {
        return $format ? number_format($this->_cartTotal, 2, '.', ',') : $this->_cartTotal;
    }

    public function get_size(): int {
        return count($this->_products);
    }

    public function is_empty(): bool {
        return empty($this->_products);
    }

    public function get_transaction_id(): int|bool {
        return $this->_transactionId ?? false;
    }

    public function set_transaction_id(int $id): void {
        $this->_transactionId = $id;
        $this->update();
    }
}
