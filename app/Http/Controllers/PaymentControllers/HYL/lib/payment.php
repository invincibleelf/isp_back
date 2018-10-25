<?php
namespace App\Http\Controllers\PaymentControllers;
//defined('HYLSITE') or define('HYLSITE', 'https://elgw.gnete.com.hk:2221/easylink-mall-api');//local site 通知地址 
class Payment {

    public function handleErr($post) {
        if(empty($post)) {
             echo '數據不能為空';exit;
         }
         if(!isset($post['secretKey']) || empty($post['secretKey'])) {
             echo '簽名秘鑰不能為空';exit;
         }
    }
    
    public function actionPay($arr) {
        $post = $arr;
        $this->handleErr($post);
        $arr = $this->handleSignature($post);
        $risk = $this->riskRateInfo($post);
        $signature = $this->signature($arr,$post['secretKey'],$risk);
        $str = '';
        foreach($arr as $key=>$val) {
          $str .= $key.'='.$val.'&'; 
        }
        $str .= 'riskRateInfo='.$risk.'&signature='.$signature;
        $url = HYLSITE;
        return $this->postRun($url,$arr,$risk,$signature); 
    }
    
    public function actionQuery() {
         $post = $_POST;
         if(!empty($post)) {
            $url = $this->handleUrl($post['postUrl'],'query');
            unset($post['postUrl']);
            unset($post['signature']);
            $secretKey = $post['secretKey'];
            unset($post['secretKey']);
            $signature = $this->signature($post,$secretKey);
            $Parameters = $this->handleQuery($post,$signature);
            $result = $this->httpConnection($url,$Parameters);
            $model = json_decode($result);
            $is_query=1;
            include_once './queryview.php';
           }else{
             header("location:".DOMAINNAME."/query.php");exit;
           }
    }

    /**
     * 
     * @return type
     */
    public function actionRefund() {
         $post = $_POST;
         if(!empty($post)) {
            $url = $this->handleUrl($post['postUrl'],'refund');
            unset($post['postUrl']);
            unset($post['signature']);
            $secretKey = $post['secretKey'];
            unset($post['secretKey']);
            unset($post['customerId']);
            $signature = $this->signature($post,$secretKey);
            $Parameters = $this->handleQuery($post,$signature);
            $result = $this->httpConnection($url,$Parameters);
            $model = json_decode($result);
            $is_refund = 1;
            include_once './queryview.php';

         }else{
             header("location:".DOMAINNAME."/refund.php");exit;
           }
    }
    
    /**
     * 真實性材料補充
     */
    public function actionSupplementPayment() {
         $post = $_POST;
         if(!empty($post)) {
            $url = $this->handleUrl($post['postUrl'],'b2cSupplementPayment');
            unset($post['postUrl']);
            unset($post['signature']);
            $secretKey = $post['secretKey'];
            unset($post['secretKey']);
            unset($post['customerId']);
            $signature = $this->signature($post,$secretKey);
            $Parameters = $this->handleQuery($post,$signature);
            $result = $this->httpConnection($url,$Parameters);
            $model = json_decode($result);
            $is_supplement_payment = 1;
             include_once './queryview.php';
          
         }else{
             header("location:".DOMAINNAME."/supplement-payment.php");exit;
           }
    }
    
    public function handleSPDate($arr) {
        $returnArr = array();
        
        if(isset($arr['cardNo']) && !empty($arr['cardNo'])) {
            $returnArr['cardNo'] = $arr['cardNo'];
        }
        
        if(isset($arr['certType']) && !empty($arr['certType'])) {
            $returnArr['certType'] = $arr['certType'];
        }
        
       if(isset($arr['certNo']) && !empty($arr['certNo'])) {
            $returnArr['certNo'] = $arr['certNo'];
        }
        
       if(isset($arr['personalMandate']) && !empty($arr['personalMandate'])) {
            $returnArr['personalMandate'] = $arr['personalMandate'];
        }
        
        if(isset($arr['name']) && !empty($arr['name'])) {
            $returnArr['name'] = $arr['name'];
        }
        
        if(isset($arr['CVN2']) && !empty($arr['CVN2'])) {
            $returnArr['CVN2'] = $arr['CVN2'];
        }
        
        if(isset($arr['cardExpire']) && !empty($arr['cardExpire'])) {
            $returnArr['cardExpire'] = $arr['cardExpire'];
        }
        
        if(isset($arr['phoneNo']) && !empty($arr['phoneNo'])) {
            $returnArr['phoneNo'] = $arr['phoneNo'];
        }
        
        return json_encode($returnArr);
    }
    
     public function actionVerification() {
         $post = $_POST;
         if(!empty($post)) {
            $url = $this->handleUrl($post['postUrl'],'accountVerification');
            $postData = $this->handleSPDate($post);
            unset($post['postUrl']);
            unset($post['signature']);
            $secretKey = $post['secretKey'];
            unset($post['secretKey']);
            unset($post['customerId']);
            unset($post['cardNo']);
            unset($post['certType']);
            unset($post['certNo']);
            unset($post['personalMandate']);
            unset($post['userNo']);
            unset($post['name']);
            unset($post['CVN2']);
            unset($post['cardExpire']);
            unset($post['phoneNo']);
            $post['data'] = $postData;
            $signature = $this->signature($post,$secretKey);
            $Parameters = $this->handleQuery($post,$signature);
            $result = $this->httpConnection($url,$Parameters);
            $model = json_decode($result);
            $verification = 1;
            include_once './queryview.php';
         }else{
             header("location:".DOMAINNAME."/verification.php");exit;
           }
    }
    
    public function handleQuery($arr,$signature) {
           $str = '';
           foreach($arr as $key=>$val) {
               $str .= $key.'='.$val.'&'; 
            }
           $str .='signature='.$signature;
           return $str; 
    }
            
    public function postRun($url,$arr,$risk='',$signature) {
        $str = "<form  id='form1' name='form1' method='POST' enctype='multipart/form-data' action='$url'>";
                foreach($arr as $key=>$val) {
                    $str .= "<input name='$key' type='text' value='$val'/>";    
                }
                if(!empty($risk)) {
                    $str .= "<input name='riskRateInfo' type='text' value='$risk'/>";
                }
                    $str .= "<input name='signature' type='text' value='$signature'/>";
                    $str .="<button  type='submit' class='btn btn-success'>提交</button>";
        $str .= "</form><script type='text/javascript'></script>";
        
        return $str;
    }
    
      
    public function httpConnection($url, $Param) {
        $ch = curl_init();
        $this_header = array("content-type: application/x-www-form-urlencoded; charset=UTF-8");
        curl_setopt($ch, CURLOPT_URL, trim($url));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 180);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $Param);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:14.0)  Gecko/20100101 Firefox/14.0.1");
        $content = curl_exec($ch);
        return $content;
    }
    
    public function handleUrl($str,$position='pay') {
        $url = '';
        switch ($str) {
            case 'dev site':
              $url = DEVSITE . '/'.$position;
              break;  
            case 'local site':
              $url = LOCALSITE . '/'.$position;
              break;
            case 'pre site':
              $url = PRESITE . '/'.$position;
              break;  
            case 'pro site':
              $url = PROSITE . '/'.$position;
              break;  
            }
           return $url;
    }
    public function handleRiskRateInfo($arr) {
        $unArr = array(
            'postUrl','version','charset','signMethod','signature','merId','backEndUrl',
            'frontEndUrl','orderTime','orderNumber','bankNumber','paymentMode','transAmount','transCurrency',
            'transTimeout','customerIp','merReserved',
            'secretKey','transType'
            );
        foreach($unArr as $key=>$val) {
            if(isset($arr[$val])) {
                unset($arr[$val]);
            }
        }
        return $arr;
    }
    
    /**
     * 
     * @param type $arr
     * 18個參數
     */
    public function riskRateInfo($arr) {
        $arr = $this->handleRiskRateInfo($arr);
        $str = '';
        ksort($arr);
        foreach($arr as $key=>$val) {
          $str .= $key.'='.$val.'&'; 
        }
        $str = '{'.substr($str,0,strlen($str)-1).'}';

        $str = base64_encode($str); 
        return $str;
    }
    /**
     * 
     * @param type $arr
     * @return type
     * 17個字段
     */
    public function signature($arr,$secretKey,$risk='') {
            $str = '';
            if(!empty($risk)) {
               $arr['riskRateInfo'] = $risk;
            }
            ksort($arr);
            foreach($arr as $key=>$val) {
               $str .= $key.'='.$val.'&'; 
            }
           $str .= $secretKey; 

           $sha256 = hash("sha256",$str);

           return $sha256;
    }
    
    public function handleSignature($arr) {
         $unArr = array(
            'postUrl','transMode','commodityCode','commodityName','commodityUrl','commodityUnitPrice','commodityQuantity',
             'transferFee','commodityDiscount','recipientName','recipientTel','deliveryDate','recipientAddress','deliveryMode',
             'customerName','customerCardNumber','customerTel','certificateType','certificateNo',
             'secretKey','transType'
            );
        foreach($unArr as $key=>$val) {
            if(isset($arr[$val])) {
                unset($arr[$val]);
            }
        }
          return $arr;
    }
    
}
