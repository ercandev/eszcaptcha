<?php
namespace Opencart\Catalog\Controller\Extension\ESZCaptcha\Captcha;

class ESZCaptcha extends \Opencart\System\Engine\Controller {

	private const LOWERS = "abcdefghijklmnopqrstuvwxyz";
	private const UPPERS = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	private const NUMBERS = "0123456789";
	private const SIMILARS = ['0', 'O', 'Q', 'I', 'l', '1', 'Z', '2', '5', 'B', '8', 'G', '6'];
	
	public function index(): string {
		$this->load->language('extension/eszcaptcha/captcha/eszcaptcha');

		$data['route'] = (string)$this->request->get['route'];

		$mode = $this->config->get('captcha_eszcaptcha_mode');
		
		if ($mode == 'dynamic') {
			$this->load->model('extension/eszcaptcha/captcha/eszcaptcha');
			
			$this->model_extension_eszcaptcha_captcha_eszcaptcha->addUsage('generation');
			
			$limit = $this->config->get('captcha_eszcaptcha_daily_limit');
			$counter = $this->config->get('captcha_eszcaptcha_limit_counter');
			
			if (($counter == 'generation' && !$this->model_extension_eszcaptcha_captcha_eszcaptcha->requiresThrottling('generation', $limit, 86400)) 
					|| ($counter == 'validation' && !$this->model_extension_eszcaptcha_captcha_eszcaptcha->requiresThrottling('validation', $limit, 86400))) {
				$this->session->data['eszcaptcha_safe'] = true;
				return '';
			}
		}

		$data['show_copyright'] = $this->config->get('captcha_eszcaptcha_show_copyright');
		
		return $this->load->view('extension/eszcaptcha/captcha/eszcaptcha', $data);
	}

	public function validate(): string {
		$this->load->language('extension/eszcaptcha/captcha/eszcaptcha');
		
		$mode = $this->config->get('captcha_eszcaptcha_mode');
		
		if ($mode == 'dynamic') {
			$this->load->model('extension/eszcaptcha/captcha/eszcaptcha');
			
			$limit = $this->config->get('captcha_eszcaptcha_daily_limit');
			$counter = $this->config->get('captcha_eszcaptcha_limit_counter');
      
			if ((($counter == 'generation' && !$this->model_extension_eszcaptcha_captcha_eszcaptcha->requiresThrottling('generation', $limit, 86400))
					|| ($counter == 'validation' && !$this->model_extension_eszcaptcha_captcha_eszcaptcha->requiresThrottling('validation', $limit, 86400)))
					&& isset($this->session->data['eszcaptcha_safe'])) {
				
				unset($this->session->data['eszcaptcha_safe']);
				
				$this->model_extension_eszcaptcha_captcha_eszcaptcha->addUsage('validation');
				
				return '';
			}
			
			$this->model_extension_eszcaptcha_captcha_eszcaptcha->addUsage('validation');
		}

		if (!isset($this->session->data['eszcaptcha']) || !isset($this->request->post['captcha']) || ($this->session->data['eszcaptcha'] != $this->request->post['captcha'])) {
			return $this->language->get('error_captcha');
		} else {
			return '';
		}
	}

	public function captcha(): void {
		$length = $this->config->get('captcha_eszcaptcha_length');
		$difficulty = $this->config->get('captcha_eszcaptcha_difficulty');
		
		$chars = '';
		if ($this->config->get('captcha_eszcaptcha_use_lowercase')) {
			$chars .= self::LOWERS;
		}
		
		if ($this->config->get('captcha_eszcaptcha_use_uppercase')) {
			$chars .= self::UPPERS;
		}
		
		if ($this->config->get('captcha_eszcaptcha_use_number')) {
			$chars .= self::NUMBERS;
		}
		
		if ($this->config->get('captcha_eszcaptcha_exclude_similar')) {
			$chars = str_replace(self::SIMILARS, '', $chars);
		}
		
		$text = $this->getRandomText($chars, $length);
		
		$this->session->data['eszcaptcha'] = $text;
		
		$this->generateImage($text, $difficulty);
	}
	
	private function generateImage(string $text, string $difficulty) {
		$width = 25 * oc_strlen($text);
		$height = 50;	
		$font = __DIR__ . '/Font/captcha'.$this->rand(0, 3).'.ttf';
		$image   = imagecreatetruecolor($width, $height);
		
		// use a color fill as a background
		$bg = imagecolorallocate($image, 255, 255, 255);
		imagefill($image, 0, 0, $bg);
		
		$effects = 0;
		
		switch ($difficulty) {
			case 'hard':
				$effects = 6;
				break;
			case 'medium':
				$effects = 4;
				break;
			default:
				$effects = 2;
		}
		
		// background lines
		for ($e = 0; $e < $effects; $e++) {
			$this->drawLine($image, $width, $height);
		}
		
		// Write CAPTCHA text
		$color = $this->writePhrase($image, $text, $font, $width, $height);
		
		// front lines
		for ($e = 0; $e < $effects + 2; $e++) {
			$this->drawLine($image, $width, $height, $color);
		}
		
		$image = $this->distort($image, $width, $height, $bg);
		
		header('Content-type: image/png');
		header('Cache-Control: no-cache');
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		
		imagepng($image);
		
		imagedestroy($image);
		exit();
	}

	private function writePhrase($image, $phrase, $font, $width, $height) {
	  $length = mb_strlen($phrase);
	  
	  // Gets the text size and start position
	  $size = (int) round($width / $length) - $this->rand(0, 3) - 1;
	  $box = \imagettfbbox($size, 0, $font, $phrase);
	  $textWidth = $box[2] - $box[0];
	  $textHeight = $box[1] - $box[7];
	  $x = (int) round(($width - $textWidth) / 2);
	  $y = (int) round(($height - $textHeight) / 2) + $size;
	  
	  $textColor = array($this->rand(0, 150), $this->rand(0, 150), $this->rand(0, 150));
	  $col = \imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
	  
	  // Write the letters one by one, with random angle
	  for ($i=0; $i<$length; $i++) {
	    $symbol = mb_substr($phrase, $i, 1);
	    $box = \imagettfbbox($size, 0, $font, $symbol);
	    $w = $box[2] - $box[0];
	    $angle = $this->rand(-8, 8);
	    $offset = $this->rand(-10, 10);
	    \imagettftext($image, $size, $angle, $x, $y + $offset, $col, $font, $symbol);
	    $x += $w + 4;
	  }
	  
	  return $col;
	}
	
	private function distort($image, $width, $height, $bg) {
	  $contents = imagecreatetruecolor($width, $height);
	  $X          = $this->rand(0, $width);
	  $Y          = $this->rand(0, $height);
	  $phase      = $this->rand(0, 10);
	  $scale      = 1.1 + $this->rand(0, 10000) / 30000;
	  for ($x = 0; $x < $width; $x++) {
	    for ($y = 0; $y < $height; $y++) {
	      $Vx = $x - $X;
	      $Vy = $y - $Y;
	      $Vn = sqrt($Vx * $Vx + $Vy * $Vy);
	      
	      if ($Vn != 0) {
	        $Vn2 = $Vn + 4 * sin($Vn / 30);
	        $nX  = $X + ($Vx * $Vn2 / $Vn);
	        $nY  = $Y + ($Vy * $Vn2 / $Vn);
	      } else {
	        $nX = $X;
	        $nY = $Y;
	      }
	      $nY = $nY + $scale * sin($phase + $nX * 0.2);
	      
        $p = $this->interpolate(
            $nX - floor($nX),
            $nY - floor($nY),
            $this->getColorAt($image, floor($nX), floor($nY), $bg),
            $this->getColorAt($image, ceil($nX), floor($nY), $bg),
            $this->getColorAt($image, floor($nX), ceil($nY), $bg),
            $this->getColorAt($image, ceil($nX), ceil($nY), $bg)
            );
	      
	      if ($p == 0) {
	        $p = $bg;
	      }
	      
	      imagesetpixel($contents, $x, $y, $p);
	    }
	  }
	  
	  return $contents;
	}
	
	private function interpolate($x, $y, $nw, $ne, $sw, $se) {
	  list($r0, $g0, $b0) = $this->getRGB($nw);
	  list($r1, $g1, $b1) = $this->getRGB($ne);
	  list($r2, $g2, $b2) = $this->getRGB($sw);
	  list($r3, $g3, $b3) = $this->getRGB($se);
	  
	  $cx = 1.0 - $x;
	  $cy = 1.0 - $y;
	  
	  $m0 = $cx * $r0 + $x * $r1;
	  $m1 = $cx * $r2 + $x * $r3;
	  $r  = (int) ($cy * $m0 + $y * $m1);
	  
	  $m0 = $cx * $g0 + $x * $g1;
	  $m1 = $cx * $g2 + $x * $g3;
	  $g  = (int) ($cy * $m0 + $y * $m1);
	  
	  $m0 = $cx * $b0 + $x * $b1;
	  $m1 = $cx * $b2 + $x * $b3;
	  $b  = (int) ($cy * $m0 + $y * $m1);
	  
	  return ($r << 16) | ($g << 8) | $b;
	}
	
	private function getRGB($col) {
	  return array(
	      (int) ($col >> 16) & 0xff,
	      (int) ($col >> 8) & 0xff,
	      (int) ($col) & 0xff,
	  );
	}
	
	private function getColorAt($image, $x, $y, $background) {
	  $L = imagesx($image);
	  $H = imagesy($image);
	  if ($x < 0 || $x >= $L || $y < 0 || $y >= $H) {
	    return $background;
	  }
	  
	  return imagecolorat($image, $x, $y);
	}
	
	private function postEffect($image) {
	  if (!function_exists('imagefilter')) {
	    return;
	  }
	  
	  // Negate ?
	  if ($this->rand(0, 1) == 0) {
	    imagefilter($image, IMG_FILTER_NEGATE);
	  }
	  
	  // Edge ?
	  if ($this->rand(0, 10) == 0) {
	    imagefilter($image, IMG_FILTER_EDGEDETECT);
	  }
	  
	  // Contrast
	  imagefilter($image, IMG_FILTER_CONTRAST, $this->rand(-50, 10));
	  
	  // Colorize
	  if ($this->rand(0, 5) == 0) {
	    imagefilter($image, IMG_FILTER_COLORIZE, $this->rand(-80, 50), $this->rand(-80, 50), $this->rand(-80, 50));
	  }
	}
	
	
	private function drawLine($image, $width, $height, $color = null) {
	  if ($color === null) {
	    $red = $this->rand(100, 255);
	    $green = $this->rand(100, 255);
	    $blue = $this->rand(100, 255);
	    $color = imagecolorallocate($image, $red, $green, $blue);
	  }

	  if ($this->rand(0, 1)) { // Horizontal
	    $Xa   = $this->rand(0, $width/2);
	    $Ya   = $this->rand(0, $height);
	    $Xb   = $this->rand($width/2, $width);
	    $Yb   = $this->rand(0, $height);
	  } else { // Vertical
	    $Xa   = $this->rand(0, $width);
	    $Ya   = $this->rand(0, $height/2);
	    $Xb   = $this->rand(0, $width);
	    $Yb   = $this->rand($height/2, $height);
	  }
	  imagesetthickness($image, $this->rand(1, 2));
	  imageline($image, $Xa, $Ya, $Xb, $Yb, $color);
	}
	
  private function rand(int $min, int $max): int {
    return mt_rand($min, $max);
  }

  private function getRandomText(string $chars, int $length): string {
		$max = mb_strlen($chars) - 1;
		
		$token = '';
		
		for ($i = 0; $i < $length; $i++) {
			$token .= $chars[$this->rand(0, $max)];
		}
		
		return $token;
  }
	
}
