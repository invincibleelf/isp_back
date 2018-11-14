<?php
namespace App\Http\Controllers\PaymentControllers\DINPAY\lib;
//defined('HYLSITE') or define('HYLSITE', 'https://elgw.gnete.com.hk:2221/easylink-mall-api');//local site 通知地址 
use Illuminate\Support\Facades\Config;
class Dinpay {

	public static function sign ($data){
		$merchant_private_key = Config::get('constants.DINPAYCONFIG.merchant_private_key');
		$merchant_private_key = openssl_pkey_get_private($merchant_private_key);

		
		// $encryption_key = Config::get('constants.DINPAYCONFIG.encryption_key');
		// $encryption_key = openssl_get_publickey($encryption_key);
		ksort($data);
		$signStr = "";
		foreach($data as $key=>$val) {
			$signStr .= $key.'='.$val.'&'; 
		}
		$signStr = rtrim($signStr,"&");
		
		openssl_sign($signStr,$sign_info,$merchant_private_key,OPENSSL_ALGO_MD5);
		$sign = base64_encode($sign_info);
        return $sign;
	}

}
?>
