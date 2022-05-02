<?php

namespace App\Http\Controllers\GameControllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; 
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Specialtactics\L5Api\Http\Controllers\RestfulController as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use \App\Http\Controllers\Controller;
use outcomebet\casino25\api\client\Client;
use \App\Models\Gameoptions;
use \App\Models\Gametransactions;
use \App\Models\Gamelist;
use \App\Models\Players;
use \Cache;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class EvolutionGamingController extends Controller
{



   /**
     * @param $balance return to API
     * @return \Illuminate\Http\JsonResponse
     */
    public function balance(Request $request)
    {


$ip_check = $_SERVER['REMOTE_ADDR'];

// Array of allowed IPs and subnets, both IPv4 and IPv6
$ips_allowed = array(
	'51.89.65.49'
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

		//og::alert($request);
                if($_SERVER['REMOTE_ADDR'] !== '51.89.65.49') {
                    return;
                }

            $getUsername = $request['playerid'];
            $explodeUsername = explode('!', $getUsername);


            $getSession = Players::where('player_id', $explodeUsername[1])->where('casino_id', $explodeUsername[0])->first();


            $currency = $request['currency'];
            $playerId = $explodeUsername[1];
                
            $findoperator = Gameoptions::where('id', $explodeUsername[0])->first();

                $baseurl = $findoperator->callbackurl;
                $prefix = $findoperator->livecasino_prefix;

                $url = $baseurl.$prefix.'/balance?currency='.$currency.'&playerid='.$playerId;
                Log::critical($url);
                $userdata = array('playerid' => $playerId, 'currency' => $currency, 'sign' => md5($findoperator->apikey.'-'.$playerId.'-'.$findoperator->operator_secret));

                $jsonbody = json_encode($userdata);
                $curlcatalog = curl_init();
                curl_setopt_array($curlcatalog, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $jsonbody,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
              ),
            ));

            $responsecurl = curl_exec($curlcatalog);
            curl_close($curlcatalog);
            //Log::critical($responsecurl);

            $responsecurl = json_decode($responsecurl, true); 

            return response()->json([
                'result' => ([
                    'balance' => floatval($responsecurl['result']['balance']),
                ]),
                'id' => 0,
                'jsonrpc' => '2.0'
            ])->setStatusCode(200);

    }


   /**
     * @param $balance return to API
     * @return \Illuminate\Http\JsonResponse
     */
    public function result(Request $request)
    {
            Log::warning($request);


$ip_check = $_SERVER['REMOTE_ADDR'];

// Array of allowed IPs and subnets, both IPv4 and IPv6
$ips_allowed = array(
	'51.89.65.49'
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


                if($_SERVER['REMOTE_ADDR'] !== '51.89.65.49') {
                    return;
                }

         

            $getUsername = $request['playerid'];
            $explodeUsername = explode('!', $getUsername);
            //$getSession = Players::where('player_id', $explodeUsername[1])->where('casino_id', $explodeUsername[0])->first();

            $currency = $request['currency'];
            $playerId = $explodeUsername[1];
            $roundId = $request['roundid'];
            $final = $request['final'];
            $findoperator = Gameoptions::where('id', $explodeUsername[0])->first();
            $withdraw = intval($request['bet']);
            $deposit = intval($request['win']);
            $gameId = $request['gameid'];
            $softswiss = Gamelist::where('game_id', $gameId)->first();
            $softswissID = $request['gameid'];
            $softswissGet = Gamelist::where('id_hash', $gameId)->first();
            if($softswissGet) {
                $softswissID = $softswissGet->game_id ?? $request['gameid'];
            }

            if($gameId === 'DK_european roulette') {
                $gameId = 'DK_european-roulette';
            }
            $transactionRef = $roundId.rand('100', '9999');
            $totalBet = intval($request['bet']);
            $totalWin = intval($request['win']);

            $slug_type = $findoperator->slug_type;
            if($slug_type !== '0') {
                $gameList = \App\Models\Gamelist::where('game_slug', $gameId)->first();

                if($slug_type === '1') {
                    $gameId = $gameList->game_slug ?? $gameList->id_hash;
                }
                if($slug_type === '2') {
                    $gameId = $gameList->softswiss ?? $gameList->id_hash;
                }
            }


            $OperatorTransactions = Gametransactions::create(['casinoid' => $findoperator->id, 'currency' => $findoperator->native_currency, 'player' => $playerId, 'ownedBy' => $findoperator->ownedBy, 'bet' => $withdraw, 'win' => $deposit, 'gameid' => $gameId, 'txid' => $transactionRef, 'roundid' => $roundId, 'type' => 'livecasino', 'rawdata' => '[]']);


                $baseurl = $findoperator->callbackurl;
                $prefix = $findoperator->livecasino_prefix;
                
                $verifySign = md5($findoperator->apikey.'_'.$roundId.'_'.$findoperator->operator_secret);


                if($final === "1") {
                $totalTxs = Gametransactions::where('roundid', '=', $roundId)->where('player', '=', $playerId)->get();
                $totalWin = $totalTxs->sum('win');
                $totalBet = $totalTxs->sum('bet');
                $url = $baseurl.$prefix.'/bet?currency='.$currency.'&softswiss='.$softswissID.'&gameprovider=evolution&gameid='.$gameId.'&roundid='.$roundId.'&playerid='.$playerId.'&bet='.$withdraw.'&win='.$deposit.'&bonusmode=0&final='.$final.'&totalBet='.$totalBet.'&totalWin='.$totalWin.'&sign='.$verifySign;

                //$processGgr = Gametransactions::processGgr($request['gameid'], $findoperator->id, $totalWin, $totalBet);
                
                
                } else {
                $url = $baseurl.$prefix.'/bet?currency='.$currency.'&softswiss='.$softswissID.'&gameprovider=evolution&gameid='.$gameId.'&roundid='.$roundId.'&playerid='.$playerId.'&bet='.$withdraw.'&win='.$deposit.'&bonusmode=0&final='.$final.'&sign='.$verifySign;
                }



                Log::critical($url);
                $userdata = array('playerid' => $playerId, 'currency' => $currency, 'sign' => $verifySign);

                $jsonbody = json_encode($userdata);
                $curlcatalog = curl_init();
                curl_setopt_array($curlcatalog, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $jsonbody,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
              ),
            ));


            $responsecurl = curl_exec($curlcatalog);
            curl_close($curlcatalog);
            Log::critical($responsecurl);

            $responsecurl = json_decode($responsecurl, true); 

            return response()->json([
                'result' => ([
                    'balance' => floatval($responsecurl['result']['balance']),
                ]),
                'id' => 0,
                'jsonrpc' => '2.0'
            ])->setStatusCode(200);


    }

    public static function createGame($playerId, $gameId, $casino_id, $mode, $nickname, $lang)
    {

                $ip = $_SERVER['REMOTE_ADDR'];
                $listAllowed = '141.95.108.167,141.95.106.190,89.37.173.17,168.100.9.110';
                if(!isset($ip, $listAllowed)) {
                return Response(array('status' => 'error', 'error' => 'Auth error (2: casino level)'))->setStatusCode(401);
                }


                //Disabled
                //return [];
	        //Log::notice('Bgaming Create Game: '.$playerId.$gameId.$casino_id.$mode);
	        //$ip = $_SERVER['REMOTE_ADDR'];
                //$listAllowed = '141.95.106.190';
                //if(!isset($ip, $listAllowed)) {
                //return Response(array('status' => 'error', 'error' => 'Auth error (2: casino level)'))->setStatusCode(401);
                //}



                $findoperator = Gameoptions::where('id', $casino_id)->first();
                if(!$findoperator) {
                    return;
                }
                $playerId = str_replace('!', '', $playerId);
                if($mode === 'demo') {
                    $demoMode = self::startDemo($gameId, $findoperator->operatorurl);
                    return $demoMode;

                } else {
                    $funplayState = 0;
                }


                $localPlayerId = Players::where('player_id', $playerId)->where('casino_id', $casino_id)->first();



                if(!$localPlayerId) {
                $createPlayerId = Players::create(['player_id' => $playerId, 'casino_id' => $casino_id, 'currency' => $findoperator->native_currency, 'nickname' => $nickname]);
                }
                $playerId = Players::where('player_id', $playerId)->where('casino_id', $casino_id)->first();

                $generateSessionId = self::createRandomVal(32);
                $encryptedPlayerid = bin2hex(self::encryptCasinoToken(($casino_id.'!'.$playerId->player_id), $generateSessionId));
                if($findoperator->native_currency === 'UAH' || $findoperator->native_currency === 'TRY') {
                $encryptedApikey = bin2hex(self::encryptCasinoToken('FDFEBED8CC2591A1D7040FABB313BFEF9', ($casino_id.'!'.$playerId->player_id)));
                } else {
                $encryptedApikey = bin2hex(self::encryptCasinoToken('USD:0dda2x2f-e320-4411-88ce-13bede075dab-219', ($casino_id.'!'.$playerId->player_id)));
                }
                $updatePlayers = $playerId->update(['latest_session' => $encryptedPlayerid]);

                $getGames = Gamelist::where('game_id', $gameId)->first();
                $getGamesID = $getGames->game_id ?? null;
                $category = 'top_games';
                if($getGames->gamename !== null & $getGamesID !== null) {
                $explodeTableid = explode('.', $getGames->gamename);
                $tableId = $explodeTableid[1];
                return array('status' => 'ok', 'url' => 'https://'.$findoperator->sessiondomain.'/launch?cid='.$encryptedApikey.'&uid='.$encryptedPlayerid.'&lang='.$lang.'&key='.$generateSessionId.'&table_id='.$tableId.'&category='.$category.'&game='.$getGames->game_id);
                } else {
                return array('status' => 'ok', 'url' => 'https://'.$findoperator->sessiondomain.'/launch?cid='.$encryptedApikey.'&uid='.$encryptedPlayerid.'&lang='.$lang.'&key='.$generateSessionId);
                }



    }





    public static function createRandomVal($val) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        srand((double)microtime() * 1000000);
        $i = 0;
        $pass = '';
        while ($i < $val) {
            $num = rand() % 64;
            $tmp = substr($chars, $num, 1);
            $pass = $pass . $tmp;
            $i++;
        }
        return $pass;
    }

    public static function encryptCasinoToken($plaintext, $password) 
     {
        $method = "AES-256-CBC";
        $key = hash('sha256', $password, true);
        $iv = openssl_random_pseudo_bytes(16);

        $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hash = hash_hmac('sha256', $ciphertext . $iv, $key, true);

        return $iv . $hash . $ciphertext;
    }

    public static function decryptCasinoToken($ivHashCiphertext, $password) 
    {

        $method = "AES-256-CBC";
        $iv = substr($ivHashCiphertext, 0, 16);
        $hash = substr($ivHashCiphertext, 16, 32);
        $ciphertext = substr($ivHashCiphertext, 48);
        $key = hash('sha256', $password, true);

        if (!hash_equals(hash_hmac('sha256', $ciphertext . $iv, $key, true), $hash)) return null;

        return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
    }




    }
