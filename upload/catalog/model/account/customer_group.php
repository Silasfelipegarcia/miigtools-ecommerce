<?php
namespace Opencart\Catalog\Model\Account;
/**
 * Class Customer Group
 *
 * Can be called using $this->load->model('account/customer_group');
 *
 * @package Opencart\Catalog\Model\Account
 */
class CustomerGroup extends \Opencart\System\Engine\Model {
	/**
	 * Get Customer Group
	 *
	 * Get the record of the customer group record in the database.
	 *
	 * @param int $customer_group_id primary key of the customer group record
	 *
	 * @return array<string, mixed> customer group record that has customer group ID
	 *
	 * @example
	 *
	 * $this->load->model('account/customer_group');
	 *
	 * $customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);
	 */
	public function getCustomerGroup(int $customer_group_id): array {
		$language_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "customer_group` `cg` LEFT JOIN `" . DB_PREFIX . "customer_group_description` `cgd` ON (`cg`.`customer_group_id` = `cgd`.`customer_group_id`) WHERE `cg`.`customer_group_id` = '" . (int)$customer_group_id . "' AND `cgd`.`language_id` = '" . $language_id . "'");

		if (!$query->row && $language_id !== 1) {
			$query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "customer_group` `cg` LEFT JOIN `" . DB_PREFIX . "customer_group_description` `cgd` ON (`cg`.`customer_group_id` = `cgd`.`customer_group_id`) WHERE `cg`.`customer_group_id` = '" . (int)$customer_group_id . "' AND `cgd`.`language_id` = '1'");
		}

		return $query->row;
	}

	/**
	 * Get Customer Groups
	 *
	 * Get the record of the customer group records in the database.
	 *
	 * @return array<int, array<string, mixed>> customer group records
	 *
	 * @example
	 *
	 * $this->load->model('account/customer_group');
	 *
	 * $results = $this->model_account_customer_group->getCustomerGroups();
	 */
	public function getCustomerGroups(): array {
		$language_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_group` `cg` LEFT JOIN `" . DB_PREFIX . "customer_group_description` `cgd` ON (`cg`.`customer_group_id` = `cgd`.`customer_group_id`) WHERE `cgd`.`language_id` = '" . $language_id . "' ORDER BY `cg`.`sort_order` ASC, `cgd`.`name` ASC");

		if (!$query->rows && $language_id !== 1) {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_group` `cg` LEFT JOIN `" . DB_PREFIX . "customer_group_description` `cgd` ON (`cg`.`customer_group_id` = `cgd`.`customer_group_id`) WHERE `cgd`.`language_id` = '1' ORDER BY `cg`.`sort_order` ASC, `cgd`.`name` ASC");
		}

		return $query->rows;
	}
}
