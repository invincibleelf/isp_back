<?php

namespace App\Http\Controllers\PaymentControllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Mail;
use Illuminate\Support\Facades\Validator;
use App\Transaction;
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
        print_r("2222");
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
        //
        //
        $fields = ['transAmount', 'paymentMethodName'];
        // grab credentials from the request
        $credentials = $request->only($fields);
       
        $validator = Validator::make(
            $credentials,
            [
                'transAmount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                
            ]
            );

        if ($validator->fails())
        {
            return response($validator->messages());
        }



        /**
        This is only suitable for easyLink(好易联) payment setup.
         **/
        $transAmount = round($request->transAmount * 100);
        $transaction = new Transaction();
        $rand = substr(md5(1),rand(0,26),4);
        $time = time();
        $date = date('Y-m-d H:i:s',time());
        $transaction->transaction_sn = $rand.$time;
        $transaction->amount = $transAmount;
        $transaction->merchant_id = 1;
        $transaction->merchant_service_id = 1;
        $transaction->student_id = 1;
        $transaction->payer_id = 1;
        $transaction->create_time = $date;
        $transaction->payment_method_id = 1;
        $transaction->status = 1;

        $transaction->save();
        
        $returnData = "";

        switch ($request->paymentMethodName) {
            case 'HYL':
            //amount * 100 we only deal with int in payment setting
                $returnData = $this->getHYL($transAmount, $transaction->id);
                break;
            case '':
                
                break;
            case '':
                echo '';
                break;
            default:
            break;
        }
        return json_encode(response($returnData));
    }

    function getHYL($amount, $transactionId){
           
        include_once '../app/Http/Controllers/PaymentControllers/HYL/lib/config.php';
        include_once '../app/Http/Controllers/PaymentControllers/HYL/lib/payment.php';
        $p = new Payment();
        $orderTime = date('Ymdhis');
        $orderNumber = mt_rand(10,900000000); //TO DO  replace this to trancation id In ISP
         //prepare requested parameters
        
        $arr = [
            "version" => '1.0.0',            
            "charset" => 'UTF-8',
            "signMethod" => 'SHA-256',
            "secretKey" => SECRETKEY,
            "paymentMode" => 'gnete_personal',

            "transType" => '01',
            "merId" => MERID,
            "backEndUrl" => 'http://60.242.47.187:3380/ISP_SERVER/public/api/payment/paymentComplete',
            //"backEndUrl" => 'http://easylinkdemo.native.php.phptest.easytonetech.com/back-completed.php',
            "frontEndUrl" => 'http://60.242.47.187:3380/ISP_SERVER/public/api/payment/paymentComplete',
            //"frontEndUrl" => 'http://easylinkdemo.native.php.phptest.easytonetech.com/pay-completed.php',
            "orderTime" => $orderTime,
            "orderTime" => "20180426151523",
            "orderNumber" => $transactionId,
            //"orderNumber" => '20180927081153',
            "bankNumber" => 'BCOM', //meaningless but required 
            "transAmount" => $amount,
            "transCurrency" => 'CNY',
            "transTimeout" => '',
            "customerIp" => '172.1.1.16', // TO DO this shoule be the ip address of client
            "merReserved" => '',
            "commodityCode" => '121010' //meaningless but we have to pust some value here as required
            ];

        return $p->actionPay($arr);
            

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
        include_once '../app/Http/Controllers/PaymentControllers/HYL/lib/config.php';
        include_once '../app/Http/Controllers/PaymentControllers/HYL/lib/payment.php';
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
            $url = HYLCHECK;
            $p = new Payment();
            $data = $post;     
            unset($post['orderCurrency']);
            $secretKey = SECRETKEY;
            unset($post['paymentResult']);
            unset($post['signature']);
            unset($post['orderAmount']);
            unset($post['transType']);
            unset($post['respMsg']);
            unset($post['respCode']);           
            $signature = $p->signature($post,$secretKey);
            $Parameters = $p->handleQuery($post,$signature);
            $result = $p->httpConnection($url,$Parameters);
            if($result == 1)
            {
                $transactionId = $data['orderNumber'];
                $transaction = Transaction::find($transactrionId);
                $transaction->status = 2;
                $transaction->save();
                //TODO update system payment
            }
            // file_put_contents("result.txt",print_r($result),true);
        }
        // file_put_contents("callback.txt", print_r($request->query,true));
        // file_put_contents("post.txt", print_r($_POST,true));
        // file_put_contents("dd.txt", print_r($request->all(),true));
        // file_put_contents("test.txt", print_r($request->orderNumber,true));
    }

}
