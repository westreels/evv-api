<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PokerHelper extends Controller
{

    private $addr;
    private $port;      
        
    public function __construct($addr, $port) {
        $this->addr = $addr;
        $this->port = $port;
        
        $this->createConnection();
    }
    
    public function __destruct() {
        fclose($this->fp);
    }
    
    private function createConnection() {
        $this->fp = fsockopen($this->addr, $this->port, $errno, $errstr);
        if (!$this->fp) {
            throw new Exception('No connection');
        }
    }

    public function request($request) {
        $message = $this->wrap($request);
        fwrite($this->fp, $message);
        $result = '';
        $f = true;
        while ($f) {
            $result .= fgets($this->fp, 2);
            if(strpos($result, '&2&2&2') !== false) {
                $f = false;
            }
        }
        return $this->unwrap($result);
    }

    private function wrap($message) {
        return '&1&1&1' . $message . '&2&2&2';
    }

    private function unwrap($message) {
        if(substr($message,0,6) == '&1&1&1') {
            $message = substr($message,6);
        }
        if(substr($message, -6) == '&2&2&2') {
            $message = substr($message,0,-6);
        }


        return json_decode(json_encode(simplexml_load_string($message)), true);
    }

    public static function getToken($length) {
        $token = "";
        //$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet = "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        $max = strlen($codeAlphabet) - 1;
        for ($i=0; $i < $length; $i++) {
            $token .= $codeAlphabet[rand(0, $max)];
        }
        return $token;
    }

    public static function getPokerAffID() {
            return '652';
         }


    public static function getPokerApikey() {
     return 'h1jo30rn-k2fw-j6ru-bqqc-2iqutknaljri';
    }



    public static function getGUID() {
        $guid = self::getToken(8).'-'.self::getToken(4).'-'.self::getToken(4).'-'.self::getToken(4).'-'.self::getToken(12);

        return $guid;
    }



    
}
