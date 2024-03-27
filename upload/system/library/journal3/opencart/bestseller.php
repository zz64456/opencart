<?php

namespace Journal3\Opencart;

use Journal3\Base;

/**
 * Class Bestseller stores products sold quantity using journal3_product_sales table
 * It is used to speedup sort by sales in various places (products module, product extras, etc).
 *
 * @package Journal3\Opencart
 */
class Bestseller extends Base {

	/**
	 * Create journal3_product_sales if not exists and populate it with data
	 *
	 * @param false $force
	 */
	public function init($force = false) {
		static $status;

		if ($status === null) {
			$status = true;

			if (!$this->check() || $force) {
				$this->create();
				$this->generate($force);
			}
		}
	}

	/**
	 * Check if journal3_product_sales exists
	 *
	 * @return mixed
	 */
	private function check() {
		return $this->db->query("
			SELECT *
			FROM
				INFORMATION_SCHEMA.TABLES
			WHERE
				TABLE_SCHEMA = '{$this->db->escape(DB_DATABASE)}'
				AND TABLE_NAME = '{$this->db->escape(DB_PREFIX . 'journal3_product_sales')}'
		")->num_rows;
	}

	/**
	 *
	 * Creates the necessary journal3_product_sales table
	 *
	 */
	private function create() {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `{$this->db->escape(DB_PREFIX . 'journal3_product_sales')}` (
			    `product_id` INT(11) NOT NULL,
			    `sales` INT(11) NOT NULL,
			    PRIMARY KEY (`product_id`),
			    INDEX (`sales`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8
		");
	}

	/**
	 * Updates journal3_product_sales based on sales from all orders
	 * If $force, table is truncated first
	 *
	 * @param $force
	 */
	private function generate($force) {
		if ($force) {
			$this->db->query("
				TRUNCATE TABLE `{$this->db->escape(DB_PREFIX . 'journal3_product_sales')}`
			");
		}

		$this->db->query("
			INSERT INTO `{$this->db->escape(DB_PREFIX . 'journal3_product_sales')}` (
				SELECT 
				   p.product_id, 
				   SUM(op.quantity) as sales
				FROM `{$this->db->escape(DB_PREFIX . 'product')}` p
				LEFT JOIN `{$this->db->escape(DB_PREFIX . 'order_product')}` op ON (p.product_id = op.product_id)
				LEFT JOIN `{$this->db->escape(DB_PREFIX . 'order')}` o ON (o.order_id = op.order_id)
				WHERE o.order_status_id > 0
				GROUP BY p.product_id
			)
		");
	}

	/**
	 * Updates journal3_product_sales based on sales from $order_id order
	 *
	 * @param $order_id
	 */
	public function update($order_id) {
		// get product_ids from order
		$this->db->query("
			DELETE FROM `{$this->db->escape(DB_PREFIX . 'journal3_product_sales')}`
			WHERE product_id IN (
				SELECT
					distinct product_id
				FROM
					`{$this->db->escape(DB_PREFIX . 'order_product')}`
				WHERE
					order_id = '{$this->journal3_db->escapeInt($order_id)}'
			)
		");

		// calculate sales for product_ids
		$this->db->query("
			INSERT INTO `{$this->db->escape(DB_PREFIX . 'journal3_product_sales')}` (
				SELECT 
				   p.product_id, 
				   SUM(op.quantity) as sales
				FROM `{$this->db->escape(DB_PREFIX . 'product')}` p
				LEFT JOIN `{$this->db->escape(DB_PREFIX . 'order_product')}` op ON (p.product_id = op.product_id)
				LEFT JOIN `{$this->db->escape(DB_PREFIX . 'order')}` o ON (o.order_id = op.order_id)
				WHERE o.order_status_id > 0
				AND p.product_id IN (
					SELECT
						distinct product_id
					FROM
						`{$this->db->escape(DB_PREFIX . 'order_product')}`
					WHERE
						order_id = '{$this->journal3_db->escapeInt($order_id)}'
				)
				GROUP BY p.product_id
			)  
		");
	}

}
