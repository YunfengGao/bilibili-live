<?php  
include 'captcha.php';

class bilibili{  

    function __construct ($cookie, $referer) {
        $this -> ck = $cookie;
        $this -> ref = 'http://live.bilibili.com/'.$referer;
    }

    private $useragent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.101 Safari/537.36';
    private $ref;
    private $ck;


    private function get_kit() {
        for ($try = 0; $try < 2; $try++) {
            $tp=self::curl('http://live.bilibili.com/FreeSilver/getCurrentTask');
            $raw=json_decode($tp ,1);
            #echo $tp;
            if ($raw['code'] == -10017) {
                echo "All silver coins get.\n";
                return 1;
            } else if ($raw['code'] == -99) {
                continue;
            } else if ($raw['code'] == -101) {
                echo "Not signed.\n";
                return 1;
            }

            $wait_time = intval($raw['data']['minute']) * 60 + 30;
            $ts = $raw['data']['time_start'];
            $te = $raw['data']['time_end'];
            echo "real wait time ".$wait_time."\n";
            #echo "Wait ".$raw['data']['minute']." minutes.\n";
            sleep($wait_time);
            #echo "Get_captcha.\n";
            $img = self::curl("http://live.bilibili.com/freeSilver/getCaptcha?ts=".time());
            $file_dir = "1.jpg";  
            if($fp = fopen($file_dir,'w')){  
                if(fwrite($fp,$img))  fclose($fp);      
            }
            $captcha_value = get_ans($file_dir);
            $query = 'http://live.bilibili.com/freeSilver/getAward?time_start='.$ts.'&time_end='.$te.'&captcha='.$captcha_value;
            #echo $query."\n";
            $m=self::curl($query);
            #echo $m."\n";
            $status = json_decode($m, 1);
            if ($status['msg'] == 'ok') 
                return $status['data']['isEnd'];
        }
        return 1;
    }

    private function get_token() {
        $key = 'LIVE_LOGIN_DATA';
        $len = strlen($key) + 1;
        $start_pos = strpos($this->ck, $key);
        $start_pos += $len;
        $token = '';
        while ($this->ck[$start_pos] != ";") {

            $token .= $this->ck[$start_pos];
            $start_pos += 1;
        }
        #echo $token;
        return $token;
    }

    private function get_userid() {


    }

    private function send_gift() {
        $daily_gift = 'http://live.bilibili.com/giftBag/sendDaily';
        $my_bag = 'http://live.bilibili.com/gift/playerBag';
        $send_url = 'http://live.bilibili.com/giftBag/send'; 
        $roomid = '11024';
        $ruid = '4442718';
        $giftId = 0;
        $num = 0;
        $coinType = 'gold';
        $Bag_id = 0;
        $timestamp = time();
        $rnd = time() - 20;
        $token = self::get_token();
        self::curl($daily_gift);
        $show_my_bag = self::curl($my_bag);
        #echo $show_my_bag."\n";
        $deal_my_bag = json_decode($show_my_bag, 1);
        $fir = 1;
        for ($i = 0; $i < 2; $i++) {
            if ($fir == 0 && $deal_my_bag['code'] == 0) {
                echo "All gifts has been sent.\n";
                return 0;
            }
            $fir = 0;
            foreach ($deal_my_bag['data'] as $each_item) {
                foreach ($each_item as $key => $value) {
                    #echo $key."->".$value." ";
                    if ($key == 'gift_num') $num = $value;
                    if ($key == 'gift_id') $giftId = $value;
                    if ($key == 'id') $Bag_id = $value;
                    if ($key == 'gift_type') {
                        if (empty($value))
                            $coinType = 'silver';
                    }
                }
                $send_query = "giftId=".$giftId;
                $send_query .= "&roomid=".$roomid;
                $send_query .= "&ruid=".$ruid;
                $send_query .= "&num=".$num;
                $send_query .= "&coinType=".$coinType;
                $send_query .= "&Bag_id=".$Bag_id;
                $send_query .= "&timestamp=".$timestamp;
                $send_query .= "&rnd=".$rnd;
                $send_query .= "&token=".$token;
                #echo $send_query."\n";
                $result = self::curl($send_url, $send_query);
                #echo $result."\n";
            }
        }
        return 1;
    }

    private function send_msg($msg) {
        $msg_url = 'http://live.bilibili.com/msg/send';
        $color = "16777215";
        $fontsize = "25";
        $mode = "1";
        $rnd = time();
        $roomid = "535511";
        $data = Array(
            "color" => $color,
            "fontsize" => $fontsize,
            "mode" => $mode,
            "msg" => $msg,
            "rnd" => $rnd,
            "roomid" => $roomid
        );

        # foreach ($data as $key => $value)
        #    echo '\''.$key.'\''.":".'\''.$value."',";

        $d = 'color='.$color;
        $d .= '&fontsize='.$fontsize;
        $d .= '&mode='.$mode;
        $d .= '&msg='.$msg;
        $d .= '&rnd='.$rnd;
        $d .= '&roomid='.$roomid;
        $res = self::curl($msg_url, $data);
        #echo $res."\n";
    }

    private function sign() {
        self::curl('http://live.bilibili.com/sign/doSign');
        $status = json_decode(self::curl('http://live.bilibili.com/sign/GetSignInfo'), 1); 
        if ($status['code'] == 0)
            echo "Success Signed.\n";
        else
            echo "Failed signed.\n";
    }

    public function work(){
        self::get_token();
        self::sign();
        if (self::send_gift() == 1) echo "Send gifts error.\n";
        while (self::get_kit() == 0) sleep(10);
    }

    private function curl($url, $post_data = NULL){
        $curl=curl_init();
        curl_setopt($curl,CURLOPT_URL,$url);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl,CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl,CURLOPT_REFERER,$this->ref);
        $hader = Array(
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Origin: http://live.bilibili.com',
            'X-Requested-With: XMLHttpRequest',
            'Accept-Language: en-US,en;q=0.8'
        ); 
        curl_setopt($curl, CURLOPT_HTTPHEADER, $hader);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($curl,CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl,CURLOPT_COOKIE, $this -> ck);
        curl_setopt($curl,CURLOPT_USERAGENT,$this->useragent);
        $result=curl_exec($curl);
        curl_close($curl);
        return $result;
    }
}

$cookies =  Array('add your cookie here');

echo "=====================\n";
echo Date('[Y-m-d H:m:s]', time())."\n";

$cnt = 1;
foreach ($cookies as $ck) {
    $usr = new bilibili($ck, '11024');
    $usr -> work();
    echo "======= ".$cnt." ============\n";
    $cnt += 1;
}
