<?php

namespace App\Http\Controllers\GameControllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests; 
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Specialtactics\L5Api\Http\Controllers\RestfulController as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use \App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use \App\Models\Gameoptions;
use Dingo\Api\Routing\Helpers;
use Dingo\Api\Http\Response;
use Illuminate\Support\Facades\Crypt;


class SessionController extends Controller
{
    use Helpers;
             public function encryptCasinoToken($plaintext, $password) 
             {
                $method = "AES-256-CBC";
                $key = hash('sha256', $password, true);
                $iv = openssl_random_pseudo_bytes(16);

                $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
                $hash = hash_hmac('sha256', $ciphertext . $iv, $key, true);

                return $iv . $hash . $ciphertext;
            }

            public function decryptCasinoToken($ivHashCiphertext, $password) 
            {

                $method = "AES-256-CBC";
                $iv = substr($ivHashCiphertext, 0, 16);
                $hash = substr($ivHashCiphertext, 16, 32);
                $ciphertext = substr($ivHashCiphertext, 48);
                $key = hash('sha256', $password, true);

                if (!hash_equals(hash_hmac('sha256', $ciphertext . $iv, $key, true), $hash)) return null;

                return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
            }

            public function createSession(Request $request)
            {

		//Log::alert(json_encode($request->all()));
		// Log::alert($request->fullUrl());
                $apikey = $request['apikey'];
                $game = $request['game'];
                $userid = $request['userid'];
                $mode = $request['mode'] ?? 'real';
                $name = $request['nick'] ?? $userid;
                $lang = $request['lang'] ?? 'en';

                $findoperator = DB::table('gameoptions')
                ->where('apikey', '=', $apikey)
                ->where('active', '=', 1)
                ->first();

                if(!$findoperator) {
                return Response(array('status' => 'error', 'error' => 'Auth error (2: api level)'))->setStatusCode(401);
                }

                $findUser = DB::table('users')
                ->where('id', '=', $findoperator->ownedBy)
                ->first();


                $gameid = DB::table('gamelist')
                ->where('game_id', '=', $game)
                ->orWhere('id_hash', '=', $game)
                ->orWhere('game_slug', '=', $game)
                ->first();

$ip_check = $_SERVER['REMOTE_ADDR'];

// Array of allowed IPs and subnets, both IPv4 and IPv6
$ips_allowed = array(
	'85.148.48.255', '51.89.65.49', '89.37.173.17', '141.95.108.167'
);

// Flag for IP match allowed list
$ip_match = false;
$allow = 0;

foreach($ips_allowed as $ip_allow) {
	// If IP has / means CIDR notation
	if(strpos($ip_allow, '/') === false) {
		// Check Single IP
		if(inet_pton($ip_check) == inet_pton($ip_allow)) {
			$allow = true;
			break;
		}
	}
	else {
		// Check IP range
		list($subnet, $bits) = explode('/', $ip_allow);
		
		// Convert subnet to binary string of $bits length
		$subnet = unpack('H*', inet_pton($subnet)); // Subnet in Hex
		foreach($subnet as $i => $h) $subnet[$i] = base_convert($h, 16, 2); // Array of Binary
		$subnet = substr(implode('', $subnet), 0, $bits); // Subnet in Binary, only network bits
		
		// Convert remote IP to binary string of $bits length
		$ip = unpack('H*', inet_pton($ip_check)); // IP in Hex
		foreach($ip as $i => $h) $ip[$i] = base_convert($h, 16, 2); // Array of Binary
		$ip = substr(implode('', $ip), 0, $bits); // IP in Binary, only network bits
		
		// Check network bits match
		if($subnet == $ip) {
			$allow = true;
			break;
		}
	}
}
if($allow === 0) {
	die('IP not allowed');
}


		                $ip = $_SERVER['REMOTE_ADDR'];
		//return $ip;
                $listAllowed = '141.95.108.167, 141.95.106.190, 89.37.173.17, 168.100.9.110';
                if(!isset($ip, $listAllowed)) {
                return 'error ip';
                }



                if($findUser->account_active === 0) {
                    $urlMaintenance = "https://evolutiongaming.es/inactive";
                    return Response(array('status' => 'error', 'error' => 'Auth error (3:  user status has become inactive because of administration and/or billing reasons)', 'url' => $urlMaintenance))->setStatusCode(401);
                }  

                if(!$gameid) {
                return Response(array('status' => 'error', 'error' => 'Game not found'))->setStatusCode(404);
                }

                $get_casinoid = $findoperator->id;
                $get_gameid = $gameid->game_id;

                /** @param create Blue Ocean session */
                    if($gameid->provider === "evolution") {
                        Log::warning($request);


                        $url = 'https://evconnect.slotts.io/session/OMG'.$mode.'/evolutiongaming/'.$userid.'/'.$get_gameid.'/'.$get_casinoid.'/'.$mode.'/'.$name.'/'.$lang.'/';
                        Log::warning($url);
			$response = Http::get($url);

                        if($response->getStatusCode() === 500) {
                        return array('status' => 'error', 'reason' => 'Unknown error, retry. If persists contact technical support.', 'url' => 'https://evolutiongaming.es/error');
                        }
                        $return = $response['url'];

                        return array('status' => 'ok', 'url' => $return);
                    }


            }
}
