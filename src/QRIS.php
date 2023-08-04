<?php namespace Wahidin\Mutasi;
use Symfony\Component\DomCrawler\Crawler;

class Qris{
    private $base_url = "https://merchant.qris.id";
    private $user, $pass, $cookie, $today, $from_date, $to_date, $limit, $filter;

    public function __construct($user, $pass, $filter, $from_date = null, $to_date = null, $limit = 20){
        $today = date('Y-m-d',strtotime('today'));

        // check data
        if(
            !(is_null($from_date)   || (bool) preg_match('/\d{4,}-\d{2,}-\d{2,}/',$from_date)) || 
            !(is_null($to_date)     || (bool) preg_match('/\d{4,}-\d{2,}-\d{2,}/',$to_date))
        ) throw new \Exception("Harap input tanggal dengan format yang benar.\neg: {$today}", 1);
        if(is_int($limit) && !($limit >= 10 && $limit <=300)) throw new \Exception("Harap input limit dengan benar, min 10 & max 300", 1);
        if(!(is_int($filter) && $filter >= 0)) throw new \Exception("Harap input Nominal dengan bilangan bulat & minimal 0, eg: 100000", 1);
        
        // store data
        $this->today = $today;
        $this->user = $user;
        $this->pass = $pass;
        $this->cookie = md5($this->user.$this->pass)."_cookie.txt";
        // $this->cookie = 'x';

        $this->from_date = is_null($from_date) ? $this->today : $from_date;
        $this->to_date = is_null($to_date) ? date('Y-m-d', strtotime($this->from_date.' +31 day')) : $to_date;
        $this->limit = $limit;
        $this->filter = is_null($filter) ? $this->filter : $filter;
    }

    public function mutasi(){
        $try = 0;
        relogin:
        $this->url = "{$this->base_url}/m/kontenr.php?idir=pages/historytrx.php";
        $this->data = $this->filter_data();

        $res = $this->request();
        
        if(!preg_match("/Logout/",$res)){
            $try += 1;
            if($try > 3) throw new \Exception("Gagal login setelah 3x percobaan", 1);
            $this->login();
            goto relogin;
        }

        $dom = new Crawler( $res );
        $history = $dom->filter("#history > tbody > tr")->each(function (Crawler $node, $i) {
            return $node->filter("td")->each(function(Crawler $node, $i){
                return $node->text();
            });
        });

        $data = array_map(function($h){
            if(count($h)<9) return;
            return [
                'id' => (int) $h[0],
                'timestamp' => strtotime($h[1]),
                'tanggal' => $h[1],
                'nominal' => (int) $h[2],
                'status' => trim($h[3]),
                'inv_id' => (int) $h[4],
                'tanggal_settlement' => $h[5],
                'asal_transaksi' => $h[6],
                'nama_costumer' => $h[7],
                'rrn' => $h[8],
            ];
        }, $history);
        
        return array_filter($data);
    }

    private function request(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie );
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie );
        
        if(isset($this->data)){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
            $headers = array();
            $headers[] = 'Content-Type: multipart/form-data; boundary=---------------------------';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    private function filter_data(){
        $data = <<<data
            -----------------------------
            Content-Disposition: form-data; name="datexbegin"

            {$this->from_date}
            -----------------------------
            Content-Disposition: form-data; name="datexend"

            {$this->to_date}
            -----------------------------
            Content-Disposition: form-data; name="limitasidata"

            {$this->limit}
            -----------------------------
            Content-Disposition: form-data; name="searchtxt"

            {$this->filter}
            -----------------------------
            Content-Disposition: form-data; name="Filter"

            Filter
            -------------------------------
        data;

        return preg_replace('/^ +/m','',$data);
    }

    public function login(){
        unlink($this->cookie);

        // get token
        $this->url = "https://merchant.qris.id/m/login.php";
        unset($this->data);
        $raw_token = $this->request();
        preg_match('/name="secret_token" value="(.*?)">/',$raw_token,$secret_token);
        $secret_token = $secret_token[1];



        // login
        $this->url = "https://merchant.qris.id/m/login.php?pgv=go";
        $this->data =  <<<data
        -----------------------------
        Content-Disposition: form-data; name="secret_token"
        
        {$secret_token}
        -----------------------------
        Content-Disposition: form-data; name="username"
        
        {$this->user}
        -----------------------------
        Content-Disposition: form-data; name="password"
        
        {$this->pass}
        -----------------------------
        Content-Disposition: form-data; name="submitBtn"
        
        
        -----------------------------
        data;
        $raw_login = $this->request();

        if(preg_match('/\/historytrx\.php/',$raw_login)){
            return true;
        }else{
            throw new \Exception("Tidak dapat login, Harap cek kembali email & password anda", 1);
        }
    }
}
