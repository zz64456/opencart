<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/admin/model/user/user_group.php
*/
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
	
	Opencart 4.0.0.0 fails on removing permissions from non-existing permission data. We have to check it ourselves
	before passing safe parameters to the default function.
	
*/
namespace extension\ka_extensions\user;
require_once(__DIR__ . '/usergroup.1.kamod.php');

class ModelUserGrouop extends \Opencart\Admin\Model\User\UserGroup_kamod  {

	public function removePermission(int $user_group_id, string $type, string $route): void {
		$user_group = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "user_group` 
			WHERE `user_group_id` = '" . (int)$user_group_id . "'
		")->row;

		if (empty($user_group['permission'])) {
			return;
		}
		
		parent::removePermission($user_group_id, $type, $route);
	}
}