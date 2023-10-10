<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
	
	Opencart 4.0.0.0 fails on removing permissions from non-existing permission data. We have to check it ourselves
	before passing safe parameters to the default function.
	
*/
namespace extension\ka_extensions\user;
class ModelUserGrouop extends \Opencart\Admin\Model\User\UserGroup {

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