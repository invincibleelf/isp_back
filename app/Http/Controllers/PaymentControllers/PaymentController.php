<?php

namespace App\Http\Controllers\PaymentControllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Mail;
use Illuminate\Support\Facades\Validator;
use App\Transaction;
use App\Utility\Utility;
use App\Http\Controllers\PaymentControllers\DINPAY\lib\Dinpay;
use App\Http\Controllers\PaymentControllers\HYL\lib\HYL;
use Illuminate\Support\Facades\Config;
use GuzzleHttp\Client;
class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $p = new Utility();
        dd($p);
        exit;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       
    }

    

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

      public function HYLcomplete(Request $request)
    {
        
        $post = $request->all();

        // if(!empty($post)) {
        //     $url = HYLCHECK;
        //     $p = new Payment();
        //     $postData = $p->handleSPDate($post);

        //     unset($post['postUrl']);
        //     unset($post['signature']);
        //     $secretKey = "";
        //     unset($post['secretKey']);
        //     unset($post['customerId']);
        //     unset($post['cardNo']);
        //     unset($post['certType']);
        //     unset($post['certNo']);
        //     unset($post['personalMandate']);
        //     unset($post['userNo']);
        //     unset($post['name']);
        //     unset($post['CVN2']);
        //     unset($post['cardExpire']);
        //     unset($post['phoneNo']);
        //     $post['data'] = $postData;
        //     $signature = $p->signature($post,$secretKey);
        //     $Parameters = $p->handleQuery($post,$signature);
        //     $result = $p->httpConnection($url,$Parameters);
        //     file_put_contents("result.txt",print_r($result),true);
        // }
        if(!empty($post) && $post['paymentResult'] == 'SUCCESS') {

            //call remote api ensuring data authenticity
            // file_put_contents("callback.txt", print_r($request->query,true));
            // file_put_contents("post.txt", print_r($_POST,true));
            // file_put_contents("dd.txt", print_r($request->all(),true));
            // file_put_contents("test.txt", print_r($request->orderNumber,true));
            $url = Config::get('constants.HYLCONFIG.HYLCHECK');
            $p = new HYL();
            $data = $post;     
            unset($post['orderCurrency']);
            $secretKey = Config::get('constants.HYLCONFIG.SECRETKEY');
            unset($post['paymentResult']);
            unset($post['signature']);
            unset($post['orderAmount']);
            unset($post['transType']);
            unset($post['respMsg']);
            unset($post['respCode']);
            $signature = $p->signature($post,$secretKey);
            $Parameters = $p->handleQuery($post,$signature);
            $result = $p->httpConnection($url,$Parameters);
			
            if($result)
            {
				$result = json_decode($result, true);
				
				if($result['paymentResult'] == 'SUCCESS')
				{
					$transactionSn = $result['orderNumber'];
					$transaction = Transaction::where("transaction_sn", $transactionSn)->first();
					if($transaction && $transaction->status == 1)
					{
						$transaction->status = 2;
                        $transaction->payment_method_id = 2;
						$transaction->save();
					}
				}
                //TODO update system payment
            }
            // file_put_contents("result.txt",print_r($result),true);
        }
        
    }

     /**
     * Check transaction status.
     *
     * @param  int $transactionId
     * @return \Illuminate\Http\Response
     */
    function getTransactionStatus($transactionId)
    {
        // $fields = ['transactionId'];
        // // grab credentials from the request
        // $credentials = $request->only($fields);
       
        // $validator = Validator::make(
        //     $credentials,
        //     [
        //         'transactionId' => 'required',
                
        //     ]
        //     );

        // if ($validator->fails())
        // {
        //     return response($validator->messages());
        // }
        

        //TODO check user identification
        // $currentUser = $Auth::user();
        // if( $currentUser->role_id == 1)
        // {
        //     $student = Student::where("user_id", $currentUser->id)->first();
        //     $transaction = Transaction::find($request->transactionId);
        //     if($student->id = $transaction->student_id)
        //     {
        //         return response($transaction->status);
        //     }
        // }
        
        // return response()->json(['error' => "user info is not correct"], 500);
        $transaction = Transaction::find($transactionId);
        return response($transaction->status);
    }
    public function pay(Request $request){
        $returnData = "";
        if($request->has('paymentMethodName'))
        {
            switch ($request->paymentMethodName) {
                case 'HYL':
                //amount * 100 we only deal with int in payment setting
                    $fields = ['transaction_sn'];
                    // grab credentials from the request
                    $credentials = $request->only($fields);                   
                    $validator = Validator::make(
                        $credentials,
                        [
                            'transaction_sn' => 'required',                
                        ]
                    );                    
                    if ($validator->fails())
                    {
                        return response($validator->messages());
                    }
                    $transaction = Transaction::where('transaction_sn', $request->transaction_sn)->first();
                    $user = Auth::user();

                    $entity = Utility::getEntityByCurrentUser();
                    //transaction exist && transaction is only paid by the payer
                    //?? 是否有一个payer的记录 与student share 同一个 user_id         
                    
                    if($transaction != null && ($user->role_id == "1" || $user->role_id == "4") && ($user->role_id == "1" ? $transaction->student_id == $entity->id : $transaction->payer_id == $entity->id))
                    {
                        $returnData = $this->getHYL($transaction);
                    }
                                 
                    break;
                case 'DINPAY':
                    $returnData = $this->getDINPAY($trans);
                    break;
                case '':
                    echo '';
                    break;
                default:
                break;
            }
        }
        return json_encode(response($returnData));
    }

    private function getHYL($transaction){
           
        
        $p = new HYL();
        $orderTime = date('Ymdhis');       
         //prepare requested parameters
        $secretKey = Config::get('constants.HYLCONFIG.SECRETKEY');
        $merId = Config::get('constants.HYLCONFIG.MERID');
        $arr = [
            "version" => '1.0.0',            
            "charset" => 'UTF-8',
            "signMethod" => 'SHA-256',
            "secretKey" => $secretKey,
            "paymentMode" => 'gnete_personal',
            "transMode" => "f",
            "transType" => '01',
            "merId" => $merId,
            "backEndUrl" => 'http://60.242.47.187:3380/ISP_SERVER/public/api/payment/HYLcomplete',
            //"backEndUrl" => 'http://easylinkdemo.native.php.phptest.easytonetech.com/back-completed.php',
            "frontEndUrl" => 'http://60.242.47.187:3380/ISP_SERVER/public/api/payment/paymentComplete',
            //"frontEndUrl" => 'http://easylinkdemo.native.php.phptest.easytonetech.com/pay-completed.php',
            "orderTime" => $orderTime,
            //"orderTime" => "20180426151523",
            "orderNumber" => $transaction->transaction_sn,
            //"orderNumber" => '20180927081153',
            "bankNumber" => 'BCOM', //meaningless but required 
            "transAmount" => $transaction->amount,
            "transCurrency" => 'CNY',
            "transTimeout" => '',
            "customerIp" => '172.1.1.16', // TO DO this shoule be the ip address of client
            "merReserved" => '',
            "commodityCode" => '121010' //meaningless but we have to pust some value here as required
            ];

        return $p->actionPay($arr);
            

    }
    private function cardValidate($card_no){
        $url = "https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?_input_charset=utf-8&cardNo=$card_no&cardBinCheck=true";
        $host = parse_url($url);
        $site = $host['scheme'] . "://" . $host['host'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);        
        curl_setopt($ch, CURLOPT_REFERER, $site);        
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; U; en-us; KFAPWI Build/JDQ39) AppleWebKit/535.19 (KHTML, like Gecko) Silk/3.13 Safari/535.19 Silk-Accelerated=true');
        // 伪造User-Agent
        //        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0)'); // 伪造User-Agent
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('CLIENT-IP:110.76.6.93', 'X-FORWARDED-FOR:110.76.6.93', 'HTTP_HOST:' . $host['host'], 'X-FORWARDED-HOST:' . $host['host'], 'X-FORWARDED-SERVER:' . $host['host']));
        // 伪造HTTP头
        //        curl_setopt($ch, CURLOPT_HTTPHEADER , array('X-FORWARDED-FOR:1.2.4.8', 'X-FORWARDED-HOST:'.$host['host'], 'X-FORWARDED-SERVER:'.$host['host'])); // 伪造HTTP头
        
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        $cardType = ['DC' => 0, 'CC' => 1];
        if (!$result->validated) {
            $bankInfo = array('validated' => $result->validated);
        } else {
            $bankInfo = array('validated' => $result->validated, 'bank_code' => $result->bank, 'card_type' => $cardType[$result->cardType]);
        }
        return $bankInfo;
    }

    public function signQuery(Request $request){

        $fields = ['card_no', 'mobile'];
        // grab credentials from the request
        $credentials = $request->only($fields);                   
        $validator = Validator::make(
            $credentials,
            [
                'card_no' => 'required',
                'mobile' => 'required | regex:/^1[34578]\d{9}$/'                
            ]
        );                    
        if ($validator->fails())
        {
            return response($validator->messages());
        }

        $bankInfo = $this->cardValidate($request->card_no);
        if($bankInfo['validated'])
        {
            $data = [
                'service_type' => "sign_query",
                'merchant_code' => Config::get('constants.DINPAYCONFIG.merchant_code'),
                'interface_version' => "V3.0",
                'input_charset' => "UTF-8",
                'sign_type' => "RSA-S",
                'mobile' => $request->mobile,
                'bank_code' => $bankInfo['bank_code'],
                //银行卡类型  借记卡：0 信用卡：1
                'card_type' => "0",
                'card_no' => $request->card_no,
            ];
            $signData = $data;
            unset($signData["sign_type"]);
            $sign = Dinpay::sign($signData);           
            $data['sign'] = $sign;

            $res = $this->http_post($data);            
            $returnData = ['flag' => false];
            //if card is verified, we procced to next step based on card type
            if($res['is_success'] == "T")
            {
                $returnData['flag'] = true;
                $returnData['sign_status'] = $res['sign_status'];
                $returnData['card_type'] = $res['card_type'];
                $returnData['bank_code'] = $bankInfo['bank_code'];
                if($res['sign_status'] == 2 || $res['sign_status'] == 1)
                {
                    //not signed yet || terminate sign
                    $returnData['sms_type'] = "1";                
                }elseif($res['sign_status'] == 0 )
                {
                    //signed before, proceed to sms verify
                    $returnData['merchant_sign_id'] = $res['merchant_sign_id'];
                    $returnData['sms_type'] = "0";
                    //go to sms verify
                }
            }else{
                return response()->json(['flag' => false, 'Error' => "银行卡信息有误"], 200);
            }         

        }else{
            return response()->json(['flag' => false, 'Error' => "银行卡信息有误"], 200);
        }

        return response()->json($returnData);
    }

    public function getSMS(Request $request){
        $returnData = ['flag' => false];
        if($request->has(['sign_status','card_type']) )
        {
            $sign_status = $request->sign_status;
            $card_type = $request->card_type;
           
            $fields = ['card_no', 'mobile','sign_status', 'transaction_sn', 'sms_type', 'card_type','bank_code'];
            // grab credentials from the request
            $credentials = $request->only($fields);                   
            $validator = Validator::make(
                $credentials,
                [
                    'card_no' => 'required',
                    'mobile' => 'required | regex:/^1[34578]\d{9}$/',
                    'sign_status' => 'required | regex:/[012]/',
                    'transaction_sn' => 'required',
                    'sms_type' => 'required | regex:/[01]/',
                    'card_type' => 'required | regex:/[01]/',
                    'bank_code' => 'required',
                ]
            );                    
            if ($validator->fails())
            {
                return response($validator->messages());
            }
            $transaction = Transaction::where('transaction_sn',$request->transaction_sn)->first();

            //prepare general data
            $data = [
                'service_type' => "sign_pay_sms_code",
                'merchant_code' => Config::get('constants.DINPAYCONFIG.merchant_code'),
                'interface_version' => "V3.0",
                'input_charset' => "UTF-8",
                'sign_type' => "RSA-S",
                'mobile' => $request->mobile,
                'order_no' => $request->transaction_sn,
                'order_amount' => $transaction->amount,
                'send_type' => 0,
                'bank_code' => $request->bank_code,
            ];

            //签名状态，从查询页面sign_query获取
            if($sign_status=='2' && $card_type == '0')
            {
                if($request->has(['card_no', 'card_name','id_no']))
                {
                    $encrypt_data = [
                        'card_no' => $request->card_no,
                        'card_name' => $request->card_name,
                        'id_no' => $request->id_no
                    ];
                    $encrypt_info = $this->getEncryption($encrypt_data, 0);
                    $data += [
                        'card_type' => 0,
                        'sms_type' => 0,
                        'encrypt_info' => $encrypt_info
                    ];

                      
                }else{
                    $returnData['Error'] = '需要相关的储蓄卡信息：持卡人姓名, 持卡人身份证';
                    return response()->json($returnData, 200);
                }
            }elseif($sign_status=='2' && $card_type == '1')
            {
                if($request->has(['card_no', 'card_name','id_no', 'card_cvv2', 'card_exp_date']))
                {
                    $encrypt_data = [
                        'card_no' => $request->card_no,
                        'card_name' => $request->card_name,
                        'id_no' => $request->id_no,
                        'card_cvv2' => $request->card_cvv2,
                        'card_exp_date' => $request->card_exp_date,
                    ];
                    $encrypt_info = $this->getEncryption($encrypt_data, 0);
                    $data += [
                        'card_type' => 1,
                        'sms_type' => 0,
                        'encrypt_info' => $encrypt_info
                    ];

                      
                }else{
                    $returnData['Error'] = '需要相关的储蓄卡信息：持卡人姓名, 持卡人身份证, CVV号, 信用卡有效期';
                    return response()->json($returnData, 200);
                }

            }elseif($sign_status=='0' && $card_type=="0")
            {
                if($request->has('merchant_sign_id'))
                {

                    $data += [
                        'card_type' => 1,
                        'sms_type' => 1,
                        'merchant_sign_id' => $request->merchant_sign_id,                     
                        ];
                }else
                {
                        $returnData['Error'] = '需要签约的ID号';
                        return response()->json($returnData, 200);
                }            
            }elseif($sign_status=='0'&&$card_type=="1")
            {
                if($request->has(['merchant_sign_id','card_no', 'card_name','id_no', 'card_cvv2', 'card_exp_date']))
                {
                     $encrypt_data = [
                        'card_no' => $request->card_no,
                        'card_name' => $request->card_name,
                        'id_no' => $request->id_no,
                        'card_cvv2' => $request->card_cvv2,
                        'card_exp_date' => $request->card_exp_date,
                    ];
                    $encrypt_info = $this->getEncryption($encrypt_data, 1);
                    $data += [
                        'card_type' => 1,
                        'sms_type' => 1,
                        'merchant_sign_id' => $request->merchant_sign_id,
                        'encrypt_info' => $encrypt_info                    
                        ];

                }else
                {
                        $returnData['Error'] = '需要签约的ID号,需要相关的储蓄卡信息：持卡人姓名, 持卡人身份证, CVV号, 信用卡有效期';
                        return response()->json($returnData, 200);
                }            

            }
            $signData = $data;
            unset($signData["sign_type"]);
            $sign = Dinpay::sign($signData);
            $data['sign'] = $sign;
            
            //data prepared, proceed to pay
            $res = $this->http_post($data);
            dd($res);

        }else{
            $returnData['Error'] = 'Parameters should have sign_status && card_type';           
            return response()->json($returnData, 200);
        }

    }

    private function getEncryption($data, $option = 0)
    {
            $encryption_key = Config::get('constants.DINPAYCONFIG.encryption_key');
           
            $card_no = $data['card_no'];
            $card_name = $data['card_name'];
            $id_no = $data['id_no'];
            $encrypt = $card_no."|".$card_name."|".$id_no;
            if($option == 1)
            {
                $encrypt = $encrypt."|".$data['card_cvv2']."|".$data['card_exp_date'];
            }
            openssl_public_encrypt($encrypt,$info,$encryption_key);            
            $encrypt_info = base64_encode($info);//encrypt_info参数参与签名
            return $encrypt_info;            
    }

    private function http_post($data)
    {
        $url = Config::get('constants.DINPAYCONFIG.url');
        $host = parse_url($url);
        $site = $host['scheme'] . "://" . $host['host'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);            
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; U; en-us; KFAPWI Build/JDQ39) AppleWebKit/535.19 (KHTML, like Gecko) Silk/3.13 Safari/535.19 Silk-Accelerated=true');
        
        $result = curl_exec($ch);
        curl_close($ch);
        $xml = simplexml_load_string($result);
        $array = json_decode(json_encode((array)$xml), TRUE);            
        $res = $array['response'];
        return $res;
    }

}
