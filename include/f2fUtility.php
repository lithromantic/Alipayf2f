<?php


class f2fUtility {
	
	public $charset = 'UTF-8';
	public $bizParas = array();
	public $gatewayUrl = "https://openapi.alipay.com/gateway.do";
	
	public function setBizContent($Paras){

        if(!empty($Paras)){
            $this->bizParas['biz_content'] = json_encode($Paras,JSON_UNESCAPED_UNICODE);
        }else{
			
			$this->bizParas['biz_content'] = '';
		}

        
	}
	
	public function getBizParas() {
		   
		   return $this->bizParas;
	}
	
	public function checkEmpty($value) {
			if (!isset($value))
				return true;
			if ($value === null)
				return true;
			if (trim($value) === "")
				return true;

			return false;
	}

	public function characet($data, $targetCharset = 'UTF-8') {


			if (!empty($data)) {
			
				if (strcasecmp($this->charset, $targetCharset) != 0) {

					$data = mb_convert_encoding($data, $targetCharset);
				}
			}


			return $data;
	}


	public function rsaSign($para = array(),$rsakey, $sign_type = 'RSA') {
	
			$data = $this->getSignContent($para);
	
			if(!empty($rsakey)) {
				$res = "-----BEGIN RSA PRIVATE KEY-----\n" .
				wordwrap($rsakey, 64, "\n", true) .
				"\n-----END RSA PRIVATE KEY-----";
			}

			if ("RSA2" == $sign_type) {
				openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
			} else {
				openssl_sign($data, $sign, $res);
			}


			return base64_encode($sign);
	}
	
	
	public function urlEncode($paras = array()) {
		
		
		$str = '';

        foreach($paras as $k => $v) {
			
			   $str .= "$k=" . urlencode($this->characet($v, $this->charset)) . "&";
		}
		  return substr($str,0,-1);

	}


	public function getSignContent($params) {
			ksort($params);

			$stringToBeSigned = "";
			$i = 0;
			foreach ($params as $k => $v) {
				if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

					$v = $this->characet($v, $this->charset);

					if ($i == 0) {
						$stringToBeSigned .= "$k" . "=" . "$v";
					} else {
						$stringToBeSigned .= "&" . "$k" . "=" . "$v";
					}
					$i++;
				}
			}

			unset ($k, $v);
			return $stringToBeSigned;
	}
	
	public function verifySign($data, $sign, $pubkey, $signType = 'RSA') {

		$res = "-----BEGIN PUBLIC KEY-----\n" .
				wordwrap($pubkey, 64, "\n", true) .
				"\n-----END PUBLIC KEY-----";


		if ("RSA2" == $signType) {
			$result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
		} else {
			$result = (bool) openssl_verify($data, base64_decode($sign), $res);
		}


		return $result;
	}
	
	public function curl($url, $postFields = null) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$postBodyString = "";
		$encodeArray = Array();
		$postMultipart = false;
         $postCharset = 'UTF-8';

		if (is_array($postFields) && 0 < count($postFields)) {

			foreach ($postFields as $k => $v) {
				if ("@" != substr($v, 0, 1)) 
				{

					$postBodyString .= "$k=" . urlencode(characet($v, $this->charset)) . "&";
					$encodeArray[$k] = $this->characet($v, $this->charset);
				} else 
				{
					$postMultipart = true;
					$encodeArray[$k] = new \CURLFile(substr($v, 1));
				}

			}
			
			unset ($k, $v);
			curl_setopt($ch, CURLOPT_POST, true);
			if ($postMultipart) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeArray);
			} else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
			}
		}

		if ($postMultipart) {

			$headers = array('content-type: multipart/form-data;charset=' . $this->charset);
		} else {

			$headers = array('content-type: application/x-www-form-urlencoded;charset=' . $this->charset);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);




		$reponse = curl_exec($ch);

		if (curl_errno($ch)) {

			throw new Exception(curl_error($ch), 0);
		} else {
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (200 !== $httpStatusCode) {
				throw new Exception($reponse, $httpStatusCode);
			}
		}

		curl_close($ch);
		return $reponse;
	}

}