<?php
/**
 * Created by UP Desenvolvimento.
 * Programmer: Bruno Oliveira
 * Date: 08/05/2022
 * Time: 07:57
**/

class Bombeja
{
    public $Version = '1.0';
    
    public $url   = "https://bombeja.com.br/api/v2";
    
    /// Requisitar Saldo
    public function api($key, $action, $service, $link, $quantity){
        $url = $this->url;
        
        $fields = array('key'=>$key, 'action'=>$action, 'service'=>$service, 'link'=>$link, 'quantity'=>$quantity);
        $method="POST";
        $file=null;

        $useragent = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko';
        $timeout= 240;
        $dir = dirname(__FILE__);
        $_SERVER["REMOTE_ADDR"] = $_SERVER["REMOTE_ADDR"] ?? '127.0.0.1';
        $cookie_file    = $dir . '/cookies/' . md5($_SERVER['REMOTE_ADDR']) . '.txt';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);    
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt($ch, CURLOPT_ENCODING, "" );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch, CURLOPT_AUTOREFERER, true );
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10 );
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.google.com/');
        if($file!=null){
            if (!curl_setopt($ch, CURLOPT_FILE, $file)){ // Handle error
                    die("curl setopt bit the dust: " . curl_error($ch));
            }
            //curl_setopt($ch, CURLOPT_FILE, $file);
            $timeout= 3600;
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout );
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout );
        if($fields!=null){
            $postvars = http_build_query($fields); // build the urlencoded data
            if($method=="POST"){
                // set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
            }
            if($method=="GET"){
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $url = $url.'?'.$postvars;
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        $content = curl_exec($ch);
        if (!$content){
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            //die("cURL request failed, error = {$error}; info = " . print_r($info, true));
            
            return $error;
            
        }
        if(curl_errno($ch)){
            echo 'error:' . curl_error($ch);
        } else {
            return json_decode($content, true);       
        }
        curl_close($ch);
    }
}