<?php

include_once './lib/config.php';

class Sig {
    
    public $sinStr;

    public function signature($arr) {
        $str = '';
        ksort($arr);
        foreach ($arr as $key => $val) {
            if(!empty($arr[$key])) {
               $str .= $key . '=' . $val . '&';
            }
        }
        $str .= SECRETKEY;
        $this->sinStr = $str;
        $sha256 = hash("sha256", $str);
        return $sha256;
    }
}

if (!empty($_POST)) {
    $sig = new Sig();
    $signature = $_POST['signature'];
    unset($_POST['signature']);
//    unset($_POST['signMethod']);
    $result = $sig->signature($_POST);
    if ($signature == $result) {
        echo '成功校驗簽名！ 處理訂單狀態邏輯寫在此處。';
    } else {
        echo '校驗簽名失敗！ 請對照下列信息';
        echo '<pre>';
        var_dump('支付完成返回簽名:' . $signature);
        var_dump('程序加密簽名結果:' . $result);
        var_dump("加密前的字符拼接：" . $sig->sinStr);
        exit;
    }
} else {
    var_dump($_POST);
}
