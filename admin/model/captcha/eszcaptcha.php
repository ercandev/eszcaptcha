<?php
namespace Opencart\Admin\Model\Extension\ESZCaptcha\Captcha;

class ESZCaptcha extends \Opencart\System\Engine\Model {
	
	public function install(): void {
		$this->db->query("CREATE TABLE `" . DB_PREFIX . "eszcaptcha_throttling` (`eszcaptcha_throttling_id` INT NOT NULL AUTO_INCREMENT , `ip` VARCHAR(40) NOT NULL , `key` VARCHAR(64) NOT NULL , `time` INT NOT NULL , PRIMARY KEY (`eszcaptcha_throttling_id`), INDEX (`time`), INDEX (`ip`, `key`)) ENGINE = MEMORY");
	}

	public function uninstall(): void {
		$this->db->query("DROP TABLE `" . DB_PREFIX . "eszcaptcha_throttling`");
	}
	
}