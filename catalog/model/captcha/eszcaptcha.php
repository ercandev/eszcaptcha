<?php
namespace Opencart\Catalog\Model\Extension\ESZCaptcha\Captcha;

class ESZCaptcha extends \Opencart\System\Engine\Model {
	
	public function addUsage(string $key): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "eszcaptcha_throttling` SET `ip`='" . $this->db->escape(oc_get_ip()) . "',`key`='" . $this->db->escape($key) . "',`time`='" . time() . "'");
	}
	
	public function requiresThrottling(string $key, int $limit, int $ttl): bool {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "eszcaptcha_throttling` WHERE `ip`='" . $this->db->escape(oc_get_ip()) . "' AND `key`='" . $this->db->escape($key) . "' AND `time` > '" . time() - $ttl . "'");
		
		if ((int)$query->row['total'] > $limit) {
			return true;
		}
		
		return false;
	}

	public function __destruct() {
		if (mt_rand(1, 100) == 1) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "eszcaptcha_throttling` WHERE `time` < '" . time() - 86400 . "'");
		}
	}
	
}
