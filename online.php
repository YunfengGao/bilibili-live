<?php
class bilibili{  
    function __construct ($cookie, $referer) {
        $this -> ck = $cookie;
        $this -> ref = 'http://live.bilibili.com/'.$referer;
    }

    private $useragent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.101 Safari/537.36';
    private $ref;
    private $ck;

    private function getinfo(){
        $data = json_decode(self::curl('http://live.bilibili.com/User/getUserInfo'),1);
        $a = $data['data']['user_intimacy'];
        $b = $data['data']['user_next_intimacy'];
        $c = ($data['data']['vip'] == 1) ? 20 : 10;
        $d = floor(($b-$a)/($c));
        $e = strtotime("+".$d."seconds", time());
        $per = round($a/$b*100,2);
        echo "name: {$data['data']['uname']} \n";
        echo "level: {$data['data']['user_level']} \n";
        echo "exp: {$a}/{$b} {$per}%\n";
        echo "nxt:".date("Y-m-d H:m:s", $e)."\n";
    }
    public function work(){
        echo date('[Y-m-d H:i:s]',time())."\n";
        $raw = json_decode(self::curl('http://live.bilibili.com/User/userOnlineHeart'),1);
        if(!isset($raw['data'][1])) echo " > SUCCESS\n";
        else echo " > INFO: already send @ ".date('Y-m-d H:i:s',$raw['data'][1])."\n";
        self::curl('http://live.bilibili.com/eventRoom/heart?roomid='.this -> ref);
        self::getinfo();
    }
    protected function curl($url){
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$url);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl,CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl,CURLOPT_REFERER,$this->ref);
        curl_setopt($curl,CURLOPT_COOKIE,$this->ck);
        curl_setopt($curl,CURLOPT_USERAGENT,$this->useragent);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
}

$cookies =  Array('add your cookies here');

$cnt = 1;
foreach ($cookies as $ck) {
    $usr = new bilibili($ck, '11024');
    $usr -> work();
    echo "======= ".$cnt." ============\n";
    $cnt += 1;
}
