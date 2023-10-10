<?php
namespace Opencart\Admin\Model\Extension\OsecLanguage\Language;
class TraditionalChinese extends \Opencart\System\Engine\Model {
	public function install($language_id): void {

		// Address Format
		$this->db->query("DELETE FROM `" . DB_PREFIX . "address_format` WHERE `address_format_id` = '2'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "address_format` (`address_format_id`, `name`, `address_format`) VALUES (2, '台灣地址格式', '{lastname}{firstname}, {postcode} {zone}{city}{address_1}{address_2}')");


		// Category
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_description` WHERE `language_id` = '" . (int)$language_id . "'");

		$tc_category = [
			17 => '電腦軟體',
			18 => '平板、筆電',
			20 => '桌上型電腦',
			24 => '手機、行動助理',
			25 => '周邊設備',
			28 => '電腦螢幕',
			29 => '滑鼠、軌跡球',
			30 => '印表機',
			31 => '掃描器',
			32 => '視訊設備',
			33 => '數位相機',
			34 => '影音播放器',
			57 => '平板'
		];

		foreach ($query->rows as $category) {
			$cid = (int)$category['category_id'];
			if (array_key_exists($cid, $tc_category)) {
				$this->db->query("UPDATE `" . DB_PREFIX . "category_description` SET `name` = '" . $this->db->escape($tc_category[$cid]) . "', `meta_title` = '" . $this->db->escape($tc_category[$cid]) . "' WHERE `category_id` = '" . (int)$cid . "' AND `language_id` = '" . (int)$language_id . "'");
			}
		}


		// Currency
		$this->db->query("UPDATE `" . DB_PREFIX . "currency` SET `title` = '" . $this->db->escape('新台幣') . "', `code` = 'TWD', `symbol_left` = '$', `symbol_right` = '', `decimal_place` = '0', `value` = '1' WHERE `currency_id` = '6'");


		// Geo Zone
		$this->db->query("DELETE FROM `" . DB_PREFIX . "geo_zone` WHERE `geo_zone_id` = '3'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "geo_zone` WHERE `geo_zone_id` = '4'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "geo_zone` WHERE `geo_zone_id` = '5'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "geo_zone` (`geo_zone_id`, `name`, `description`, `date_modified`, `date_added`) VALUES
(3, '台灣離島', '澎湖、金門、馬祖地區', '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(4, '台灣本島', '台灣本島', '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(5, '台灣地區', '台灣本島+離島', '2023-07-01 12:00:00', '2023-07-01 12:00:00')");


		// Length
		$tc_lengthes = [
			1 => '公分',
			2 => '毫米',
			3 => '英吋'
		];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "length_class_description` WHERE `language_id` = '" . (int)$language_id . "'");

		foreach ($query->rows as $length) {
			$length_class_id = (int)$length['length_class_id'];
			if (array_key_exists($length_class_id, $tc_lengthes)) {
				$this->db->query("UPDATE `" . DB_PREFIX . "length_class_description` SET `title` = '" . $this->db->escape($tc_lengthes[$length_class_id]) . "' WHERE `length_class_id` = '" . (int)$length_class_id . "' AND `language_id` = '" . (int)$language_id . "'");
			}
		}


		// Order Status
		$tc_order_statuses = [
			1 => '待處理',
			2 => '處理中',
			3 => '已出貨',
			4 => '待付款',
			5 => '已完成',
			7 => '已取消',
			10 => '付款失敗',
			15 => '已付款',
			16 => '無效的'
		];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_status` WHERE `language_id` = '" . (int)$language_id . "'");

		foreach ($query->rows as $order_status) {
			$order_status_id = (int)$order_status['order_status_id'];
			if (array_key_exists($order_status_id, $tc_order_statuses)) {
				$this->db->query("UPDATE `" . DB_PREFIX . "order_status` SET `name` = '" . $this->db->escape($tc_order_statuses[$order_status_id]) . "' WHERE `order_status_id` = '" . (int)$order_status_id . "' AND `language_id` = '" . (int)$language_id . "'");
			}
		}

		// Return Status
		$tc_return_statuses = [
			1 => '待處理',		// Pending
			2 => '待換貨',		// Awaiting Products
			3 => '已完成'		// Complete
		];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "return_status` WHERE `language_id` = '" . (int)$language_id . "'");

		foreach ($query->rows as $return_status) {
			$return_status_id = (int)$return_status['return_status_id'];
			if (array_key_exists($return_status_id, $tc_return_statuses)) {
				$this->db->query("UPDATE `" . DB_PREFIX . "return_status` SET `name` = '" . $this->db->escape($tc_return_statuses[$return_status_id]) . "' WHERE `return_status_id` = '" . (int)$return_status_id . "' AND `language_id` = '" . (int)$language_id . "'");
			}
		}


		// Subscription Status
		$tc_subscription_statuses = [
			1 => '等待中',		// Pending
			2 => '已訂閱',		// Active
			3 => '已逾期',		// Expired
			4 => '已暫停',		// Suspended
			5 => '已取消',		// Cancelled
			6 => '訂閱失敗',		// Failed
			7 => '已拒絕'		// Denied
		];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "subscription_status` WHERE `language_id` = '" . (int)$language_id . "'");

		foreach ($query->rows as $subscription_status) {
			$subscription_status_id = (int)$subscription_status['subscription_status_id'];
			if (array_key_exists($subscription_status_id, $tc_subscription_statuses)) {
				$this->db->query("UPDATE `" . DB_PREFIX . "subscription_status` SET `name` = '" . $this->db->escape($tc_subscription_statuses[$subscription_status_id]) . "' WHERE `subscription_status_id` = '" . (int)$subscription_status_id . "' AND `language_id` = '" . (int)$language_id . "'");
			}
		}


		// Stock Status
		$tc_stock_statuses = [
			5 => '缺貨中',
			6 => '缺貨補貨中',
			7 => '有現貨',
			8 => '需預購'
		];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "stock_status` WHERE `language_id` = '" . (int)$language_id . "'");

		foreach ($query->rows as $stock_status) {
			$stock_status_id = (int)$stock_status['stock_status_id'];
			if (array_key_exists($stock_status_id, $tc_stock_statuses)) {
				$this->db->query("UPDATE `" . DB_PREFIX . "stock_status` SET `name` = '" . $this->db->escape($tc_stock_statuses[$stock_status_id]) . "' WHERE `stock_status_id` = '" . (int)$stock_status_id . "' AND `language_id` = '" . (int)$language_id . "'");
			}
		}


		// Tax
		$this->db->query("DELETE FROM `" . DB_PREFIX . "tax_class` WHERE `tax_class_id` = '1'");
		$query = $this->db->query("INSERT INTO `" . DB_PREFIX . "tax_class` (`tax_class_id`, `title`, `description`, `date_added`, `date_modified`) VALUES (1, '營業稅', '(台灣)營利事業所得稅', '2023-07-01 12:00:00', '2023-07-01 12:00:00')");

		$this->db->query("DELETE FROM `" . DB_PREFIX . "tax_rate` WHERE `tax_rate_id` = '86'");
		$query = $this->db->query("INSERT INTO `" . DB_PREFIX . "tax_rate` (`tax_rate_id`, `geo_zone_id`, `name`, `rate`, `type`, `date_added`, `date_modified`) VALUES (86, 5, '營業稅 (5%)', '5.0000', 'P', '2023-07-01 12:00:00', '2023-07-01 12:00:00')");


		// Weight
		$tc_weightes = [
			1 => '公斤',
			2 => '公克',
			5 => '磅',
			6 => '盎司'
		];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "weight_class_description` WHERE `language_id` = '" . (int)$language_id . "'");

		foreach ($query->rows as $weight) {
			$weight_class_id = (int)$weight['weight_class_id'];
			if (array_key_exists($weight_class_id, $tc_weightes)) {
				$this->db->query("UPDATE `" . DB_PREFIX . "weight_class_description` SET `title` = '" . $this->db->escape($tc_weightes[$weight_class_id]) . "' WHERE `weight_class_id` = '" . (int)$weight_class_id . "' AND `language_id` = '" . (int)$language_id . "'");
			}
		}


		// Zone
		$tc_zones = [
			3135 => '基隆市',
			3136 => '臺北市',
			3137 => '新北市',
			3138 => '桃園市',
			3139 => '新竹市',
			3140 => '新竹縣',
			3141 => '苗栗縣',
			3142 => '臺中市',
			3143 => '彰化縣',
			3144 => '南投縣',
			3145 => '雲林縣',
			3146 => '嘉義市',
			3147 => '嘉義縣',
			3148 => '臺南市',
			3149 => '高雄市',
			3150 => '屏東縣',
			3151 => '臺東縣',
			3152 => '花蓮縣',
			3153 => '宜蘭縣',
			3154 => '澎湖縣',
			3155 => '金門縣',
			3156 => '連江縣'
		];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE `country_id` = '206'");

		foreach ($query->rows as $zone) {
			$zone_id = (int)$zone['zone_id'];
			if (array_key_exists($zone_id, $tc_zones)) {
				$this->db->query("UPDATE `" . DB_PREFIX . "zone` SET `name` = '" . $this->db->escape($tc_zones[$zone_id]) . "' WHERE `zone_id` = '" . (int)$zone_id . "'");
			}
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "zone` WHERE `zone_id` = '3157'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "zone` WHERE `zone_id` = '3158'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "zone` WHERE `zone_id` = '3159'");


		// Zone to Geo Zone
		$this->db->query("DELETE FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '3'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '4'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '5'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "zone_to_geo_zone` (`zone_to_geo_zone_id`, `country_id`, `zone_id`, `geo_zone_id`, `date_added`, `date_modified`) VALUES
(125, 206, 3154, 3, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(126, 206, 3155, 3, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(127, 206, 3156, 3, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(128, 206, 3135, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(129, 206, 3136, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(130, 206, 3137, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(131, 206, 3138, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(132, 206, 3139, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(133, 206, 3140, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(134, 206, 3141, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(135, 206, 3142, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(136, 206, 3143, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(137, 206, 3144, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(138, 206, 3145, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(139, 206, 3146, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(140, 206, 3147, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(141, 206, 3148, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(142, 206, 3149, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(143, 206, 3150, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(144, 206, 3151, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(145, 206, 3152, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(146, 206, 3153, 4, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(147, 206, 3135, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(148, 206, 3136, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(149, 206, 3137, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(150, 206, 3138, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(151, 206, 3139, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(152, 206, 3140, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(153, 206, 3141, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(154, 206, 3142, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(155, 206, 3143, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(156, 206, 3144, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(157, 206, 3145, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(158, 206, 3146, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(159, 206, 3147, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(160, 206, 3148, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(161, 206, 3149, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(162, 206, 3150, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(163, 206, 3151, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(164, 206, 3152, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(165, 206, 3153, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(166, 206, 3154, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(167, 206, 3155, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00'),
(168, 206, 3156, 5, '2023-07-01 12:00:00', '2023-07-01 12:00:00')");


		$this->db->query("UPDATE `" . DB_PREFIX . "country` SET `name` = '台灣', `address_format_id` = '2' WHERE `country_id` = '206'");

		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '80' WHERE `code` = 'shipping_flat' AND `key` = 'shipping_flat_cost'");


		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '206' WHERE `code` = 'config' AND `key` = 'config_country_id'");
		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '3136' WHERE `code` = 'config' AND `key` = 'config_zone_id'");
		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = 'Asia/Taipei' WHERE `code` = 'config' AND `key` = 'config_timezone'");
		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = 'zh-TW' WHERE `code` = 'config' AND `key` = 'config_language'");
		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = 'zh-TW' WHERE `code` = 'config' AND `key` = 'config_language_admin'");

		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = 'TWD' WHERE `code` = 'config' AND `key` = 'config_currency'");
		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '0' WHERE `code` = 'config' AND `key` = 'config_currency_auto'");

		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '12' WHERE `code` = 'config' AND `key` = 'config_pagination'");
		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '20' WHERE `code` = 'config' AND `key` = 'config_pagination_admin'");

		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '' WHERE `code` = 'config' AND `key` = 'config_tax_default'");
		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '' WHERE `code` = 'config' AND `key` = 'config_tax_customer'");

		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '[\"2\"]' WHERE `code` = 'config' AND `key` = 'config_processing_status'");
		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '[\"3\",\"5\",\"15\"]' WHERE `code` = 'config' AND `key` = 'config_complete_status'");
	}

	public function uninstall(): void {
		//$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_traditional_chinese`");
	}

}
