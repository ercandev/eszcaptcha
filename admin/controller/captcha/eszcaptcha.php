<?php
namespace Opencart\Admin\Controller\Extension\ESZCaptcha\Captcha;

class ESZCaptcha extends \Opencart\System\Engine\Controller {

  public function index(): void {
    $this->load->language('extension/eszcaptcha/captcha/eszcaptcha');
    
    $this->document->setTitle($this->language->get('heading_title'));
    
    $data['breadcrumbs'] = [];
    
    $data['breadcrumbs'][] = [
        'text' => $this->language->get('text_home'),
        'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
    ];
    
    $data['breadcrumbs'][] = [
        'text' => $this->language->get('text_extension'),
        'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=captcha')
    ];
    
    $data['breadcrumbs'][] = [
        'text' => $this->language->get('heading_title'),
        'href' => $this->url->link('extension/eszcaptcha/captcha/eszcaptcha', 'user_token=' . $this->session->data['user_token'])
    ];
    
    $data['save'] = $this->url->link('extension/eszcaptcha/captcha/eszcaptcha.save', 'user_token=' . $this->session->data['user_token']);
    $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=captcha');
    
    $levels = ['easy', 'medium', 'hard'];
    
    $data['difficulties'] = [];
    
    foreach ($levels as $level) {
      $data['difficulties'][] = [
          'level' => $level,
          'name'  => $this->language->get('text_' . $level)
      ];
    }
    
    if ($this->config->has('captcha_eszcaptcha_difficulty')) {
      $data['captcha_eszcaptcha_difficulty'] = $this->config->get('captcha_eszcaptcha_difficulty');
    } else {
      $data['captcha_eszcaptcha_difficulty'] = 'medium';
    }
    
    if ($this->config->has('captcha_eszcaptcha_length')) {
      $data['captcha_eszcaptcha_length'] = $this->config->get('captcha_eszcaptcha_length');
    } else {
      $data['captcha_eszcaptcha_length'] = 6;
    }
    
    if ($this->config->has('captcha_eszcaptcha_use_lowercase')) {
    	$data['captcha_eszcaptcha_use_lowercase'] = $this->config->get('captcha_eszcaptcha_use_lowercase');
    } else {
    	$data['captcha_eszcaptcha_use_lowercase'] = 0;
    }
    
    if ($this->config->has('captcha_eszcaptcha_use_uppercase')) {
    	$data['captcha_eszcaptcha_use_uppercase'] = $this->config->get('captcha_eszcaptcha_use_uppercase');
    } else {
    	$data['captcha_eszcaptcha_use_uppercase'] = 1;
    }
    
    if ($this->config->has('captcha_eszcaptcha_use_number')) {
    	$data['captcha_eszcaptcha_use_number'] = $this->config->get('captcha_eszcaptcha_use_number');
    } else {
    	$data['captcha_eszcaptcha_use_number'] = 1;
    }
    
    if ($this->config->has('captcha_eszcaptcha_mode')) {
    	$data['captcha_eszcaptcha_mode'] = $this->config->get('captcha_eszcaptcha_mode');
    } else {
    	$data['captcha_eszcaptcha_mode'] = 'static';
    }
    
    if ($this->config->has('captcha_eszcaptcha_daily_limit')) {
    	$data['captcha_eszcaptcha_daily_limit'] = $this->config->get('captcha_eszcaptcha_daily_limit');
    } else {
    	$data['captcha_eszcaptcha_daily_limit'] = 5;
    }
    
    if ($this->config->has('captcha_eszcaptcha_limit_counter')) {
    	$data['captcha_eszcaptcha_limit_counter'] = $this->config->get('captcha_eszcaptcha_limit_counter');
    } else {
    	$data['captcha_eszcaptcha_limit_counter'] = 'generation';
    }
    
    $data['captcha_eszcaptcha_status'] = $this->config->get('captcha_eszcaptcha_status');
    
    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');
    
    $this->response->setOutput($this->load->view('extension/eszcaptcha/captcha/eszcaptcha', $data));
  }

  public function save(): void {
    $this->load->language('extension/eszcaptcha/captcha/eszcaptcha');
    
    $json = [];
    
    if (!$this->user->hasPermission('modify', 'extension/eszcaptcha/captcha/eszcaptcha')) {
      $json['error']['warning'] = $this->language->get('error_permission');
    }
    
    $required = [
    		'captcha_eszcaptcha_length' => 0,
    ];
    
    $post_info = $this->request->post + $required;
    
    if ($post_info['captcha_eszcaptcha_length'] < 5 || $post_info['captcha_eszcaptcha_length'] > 10) {
    	$json['error']['length'] = $this->language->get('error_length');
    }
    
    if (!$post_info['captcha_eszcaptcha_use_lowercase'] && !$post_info['captcha_eszcaptcha_use_uppercase'] && !$post_info['captcha_eszcaptcha_use_number']) {
    	$json['error']['character_set'] = $this->language->get('error_character_set');
    }
    
    if (!$json) {
      $this->load->model('setting/setting');
      
      $this->model_setting_setting->editSetting('captcha_eszcaptcha', $this->request->post);
      
      $json['success'] = $this->language->get('text_success');
    }
    
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function install(): void {
		if ($this->user->hasPermission('modify', 'extension/eszcaptcha/captcha/eszcaptcha')) {
			$this->load->model('extension/eszcaptcha/captcha/eszcaptcha');
			
			$this->model_extension_eszcaptcha_captcha_eszcaptcha->install();
		}
	}
	
	public function uninstall(): void {
		if ($this->user->hasPermission('modify', 'extension/eszcaptcha/captcha/eszcaptcha')) {
			$this->load->model('extension/eszcaptcha/captcha/eszcaptcha');
			
			$this->model_extension_eszcaptcha_captcha_eszcaptcha->uninstall();
		}
	}

}
