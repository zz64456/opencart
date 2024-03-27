<?php

use Journal3\Utils\Str;

class ModelJournal3ImportExport extends Model {

	public function all($filters = array()) {
		$result = array();
		$count = 0;

		$files = glob(DIR_SYSTEM . 'library/journal3/data/import_export/*.sql');

		natsort($files);

		foreach ($files as $file) {
			$size = filesize($file);

			$i = 0;

			$suffix = array(
				'B',
				'KB',
				'MB',
				'GB',
				'TB',
				'PB',
				'EB',
				'ZB',
				'YB',
			);

			while (($size / 1024) > 1) {
				$size = $size / 1024;

				$i++;
			}

			$count++;

			$result[] = array(
				'id'   => $count,
				'name' => basename($file),
				'size' => round(substr($size, 0, strpos($size, '.') + 4), 2) . $suffix[$i],
			);
		}

		return array(
			'count' => $count,
			'items' => $result,
		);
	}

	public function export($tables) {
		$output = '';

		foreach ($tables as $table) {
			if ($this->journal3_db->dbTableExists($table)) {
				$output .= 'TRUNCATE TABLE `oc_' . $this->journal3_db->escape($table) . '`;' . "\n\n";

				$output .= $this->exportTable($table);
			}
		}

		// active skin
		if (in_array('journal3_skin', $tables) && !in_array('journal3_setting', $tables)) {
			$output .= $this->exportTable('journal3_setting', " WHERE `setting_group` = 'active_skin'");
		}

		// blog settings
		if (in_array('journal3_blog_post', $tables) && !in_array('journal3_setting', $tables)) {
			$output .= $this->exportTable('journal3_setting', " WHERE `setting_group` = 'blog'");
		}

		return $output;
	}

	public function exportTable($table, $conditions = '') {
		$output = '';

		$query = $this->db->query("SELECT * FROM `" . $this->journal3_db->prefix($table) . "`" . $conditions);

		foreach ($query->rows as $result) {
			$fields = '';

			foreach (array_keys($result) as $value) {
				$fields .= '`' . $value . '`, ';
			}

			$values = '';

			foreach (array_values($result) as $value) {
				$value = $this->escape($value);

				$values .= '\'' . $value . '\', ';
			}

			if ($table === 'journal3_variable') {
				$duplicate = ' ON DUPLICATE KEY UPDATE `variable_value` = ' . '\'' . $this->escape($result['variable_value']) . '\', `serialized` = ' . '\'' . $this->escape($result['serialized']) . '\'';
			} else if ($table === 'journal3_style') {
				$duplicate = ' ON DUPLICATE KEY UPDATE `style_value` = ' . '\'' . $this->escape($result['style_value']) . '\', `serialized` = ' . '\'' . $this->escape($result['serialized']) . '\'';
			} else if ($table === 'journal3_setting') {
				$duplicate = ' ON DUPLICATE KEY UPDATE `setting_value` = ' . '\'' . $this->escape($result['setting_value']) . '\', `serialized` = ' . '\'' . $this->escape($result['serialized']) . '\'';
			} else {
				$duplicate = '';
			}

			$output .= 'INSERT INTO `oc_' . $this->journal3_db->escape($table) . '` (' . preg_replace('/, $/', '', $fields) . ') VALUES (' . preg_replace('/, $/', '', $values) . ')' . $duplicate . ';' . "\n";
		}

		$output .= "\n\n";

		return $output;
	}

	public function import($content) {
		if ($this->journal3_opencart->is_oc4) {
			$this->import_oc4($content);
		} else if ($this->journal3_opencart->is_oc3) {
			$this->import_oc3($content);
		} else {
			$this->import_oc2($content);
		}
	}

	private function import_oc4($content) {
		$product = null;
		$order = null;
		$seo_url = null;

		foreach (explode(";\n", $content) as $sql) {
			$sql = trim($sql);

			if ($sql) {
				$sql = str_replace('`oc_', '`' . DB_PREFIX, $sql);

				if (Str::contains($sql, '`' . $this->journal3_db->prefix('product_recurring') . '`')) {
					continue;
				}

				if (Str::contains($sql, '`' . $this->journal3_db->prefix('url_alias') . '`')) {
					continue;
				}

				if (Str::contains($sql, '`' . $this->journal3_db->prefix('order_recurring') . '`')) {
					continue;
				}

				if (Str::contains($sql, '`' . $this->journal3_db->prefix('order_recurring_transaction') . '`')) {
					continue;
				}

				if (Str::contains($sql, '`' . $this->journal3_db->prefix('order_shipment') . '`')) {
					continue;
				}

				if (Str::contains($sql, '`' . $this->journal3_db->prefix('product') . '`') && $product === null) {
					$product = !$this->journal3_db->dbTableHasColumn('product', 'viewed');

					if ($product) {
						$this->db->query("
							ALTER TABLE `{$this->journal3_db->prefix('product')}`
							ADD `viewed` INT AFTER `status`
						");
					}
				}

				if (Str::contains($sql, '`' . $this->journal3_db->prefix('order') . '`') && $order === null) {
					$order = !$this->journal3_db->dbTableHasColumn('order', 'fax');

					if ($order) {
						$this->db->query("
							ALTER TABLE `{$this->journal3_db->prefix('order')}`
							ADD `fax` VARCHAR(255) AFTER `telephone`
						");
					}
				}

				if (Str::contains($sql, '`' . $this->journal3_db->prefix('seo_url') . '`') && $seo_url === null) {
					$seo_url = !$this->journal3_db->dbTableHasColumn('seo_url', 'query');

					if ($seo_url) {
						$this->db->query("
							ALTER TABLE `{$this->journal3_db->prefix('seo_url')}`
							ADD `query` VARCHAR(255) AFTER `keyword`
						");
					}
				}

				$this->db->query($sql);
			}
		}

		if ($product !== null) {
			$this->db->query("TRUNCATE TABLE `{$this->journal3_db->prefix('product_viewed')}`");

			$this->db->query("
				INSERT INTO `{$this->journal3_db->prefix('product_viewed')}` (`product_id`, `viewed`)
				SELECT `product_id`, `viewed` FROM `{$this->journal3_db->prefix('product')}`
			");

			if ($product) {
				$this->db->query("
					ALTER TABLE `{$this->journal3_db->prefix('product')}`
					DROP COLUMN `viewed` 
				");
			}
		}

		if ($order !== null) {
			if (!$order) {
				$this->db->query("
					ALTER TABLE `{$this->journal3_db->prefix('order')}`
					DROP COLUMN `fax` 
				");
			}
		}

		if ($seo_url !== null) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url");

			foreach ($query->rows as $row) {
				if (!empty($row['query'])) {
					$seo_query = explode('=', $row['query']);
					$this->db->query("UPDATE " . DB_PREFIX . "seo_url SET `key` = '" . $this->db->escape($seo_query[0] ?? '') . "', value = '" . $this->db->escape($seo_query[1] ?? '') . "' WHERE seo_url_id = '" . (int)$row['seo_url_id'] . "'");
				}
			}

			if (!$seo_url) {
				$this->db->query("
					ALTER TABLE `{$this->journal3_db->prefix('seo_url')}`
					DROP COLUMN `query` 
				");
			}
		}
	}

	private function import_oc3($content) {
		foreach (explode(";\n", $content) as $sql) {
			$sql = trim($sql);

			if ($sql) {
				$sql = str_replace('`oc_', '`' . DB_PREFIX, $sql);

				if (Str::contains($sql, '`' . $this->journal3_db->prefix('url_alias') . '`')) {
					continue;
				}

				$this->db->query($sql);
			}
		}
	}

	private function import_oc2($content) {
		foreach (explode(";\n", $content) as $sql) {
			$sql = trim($sql);

			if ($sql) {
				$sql = str_replace('`oc_', '`' . DB_PREFIX, $sql);

				if (Str::contains($sql, '`' . $this->journal3_db->prefix('order_shipment') . '`')) {
					continue;
				}

				if (Str::contains($sql, '`' . $this->journal3_db->prefix('seo_url') . '`')) {
					continue;
				}

				$this->db->query($sql);
			}
		}
	}

	private function escape($value) {
		if (!$value) {
			return $value;
		}

		$value = str_replace(array("\x00", "\x0a", "\x0d", "\x1a"), array('\0', '\n', '\r', '\Z'), $value);
		$value = str_replace(array("\n", "\r", "\t"), array('\n', '\r', '\t'), $value);
		$value = str_replace('\\', '\\\\', $value);
		$value = str_replace('\'', '\\\'', $value);
		$value = str_replace('\\\n', '\n', $value);
		$value = str_replace('\\\r', '\r', $value);
		$value = str_replace('\\\t', '\t', $value);

//		if (strpos($prefixed_table, DB_PREFIX . 'journal3') === 0) {
//			$value = str_replace('\n', '\\\n', $value);
//			$value = str_replace('\t', '\\\t', $value);
//		}

		return $value;
	}

}

class_alias('ModelJournal3ImportExport', '\Opencart\Admin\Model\Journal3\ImportExport');
