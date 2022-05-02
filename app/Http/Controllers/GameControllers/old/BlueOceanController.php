<?php

namespace App\Http\Controllers\GameControllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests; 
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Specialtactics\L5Api\Http\Controllers\RestfulController as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use \App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \App\Models\Livecasinocallbacks;
use outcomebet\casino25\api\client\Client;
use \App\Models\Gametransactions;
use \App\Models\GametransactionsRaw;
use \App\Models\BlueOceanPlayers;
use \App\Models\Gameoptions;

class BlueOceanController extends Controller
{

    /**
     * @param $endpoint where callback URL & method is distributed
     * @return \Illuminate\Http\JsonResponse
     */
    public function endpoint(Request $request)
    {



    Log::warning(json_encode($request->all()));

        if($request['action'] === 'balance') {
            $getUsername = $request['username'];
            $getUsername = explode('-', $getUsername);
            $token = BlueOceanPlayers::where('id', $getUsername[0])->first()->playerid;
            $currency = explode('-', $token);
            $currency = $currency[1];
            $playerId = explode('-', $token);
            $playerId = $playerId[0];
                
            $getoperator =  BlueOceanPlayers::where('id', $getUsername[0])->first()->casino_id;
            $findoperator = Gameoptions::where('id', $getoperator)->first();

                $baseurl = $findoperator->callbackurl;
                $prefix = $findoperator->slots_prefix;

                $url = $baseurl.$prefix.'/balance?currency='.$currency.'&playerid='.$playerId;
                ////Log::warning($url);
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
            $responsecurl = json_decode($responsecurl, true); 

            try {
                return response()->json([
                        'status' => '200', 'balance' => $responsecurl['result']['balance'] / 100
                ])->setStatusCode(200);

                } catch (\Exception $exception) {
                    return response()->json([
                            'status' => '403', 'error' => 'bluecheese - balance too small'
                    ])->setStatusCode(403);
                }
        } 


        if($request['action'] === 'debit' || $request['action'] === 'credit' || $request['action'] === 'rollback') {

        $salt = 'b3ji4rTGeA';
        $param = 'key';
        $url = $_SERVER['QUERY_STRING'];
        $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*$/', '', $url);
        $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*&/', '$1', $url);
        $query = $url;

        $key = sha1($salt.$query);

        if($key !== $request['key']) {
        return response()->json([
                'status' => '500', 'error' => 'invalid salt key'
        ])->setStatusCode(500);
        }
            $getUsername = $request['username'];
            $getUsername = explode('-', $getUsername);
            $token = BlueOceanPlayers::where('id', $getUsername[0])->first()->playerid;
            $currency = explode('-', $token);
            $currency = $currency[1];
            $playerId = explode('-', $token);
            $playerId = $playerId[0];
                
            $getoperator =  BlueOceanPlayers::where('id', $getUsername[0])->first()->casino_id;

            $findoperator = Gameoptions::where('id', $getoperator)->first();

            $roundid = $request['round_id'] ?? 0;
            $gamedata = $request['game_id_hash'];
            $final = $request['gameplay_final'];

            if($request['action'] === 'debit') {
                $bet = $request['amount'];
                $win = '0';
            } elseif($request['action'] === 'credit') {
                $bet = '0';
                $win = $request['amount'];
            } elseif($request['action'] === 'rollback') {


                //Disable rollback
                //Log::info(json_encode($request));
                return response()->json([
                        'status' => '404', 'error' => 'bluecheese - txId doesnt exist'
                ])->setStatusCode(404);

                $getgame = Gametransactions::where('txid', $request['transaction_id'])->where('player', '=', $playerId)->where('bet', '>', '0')->first();
                $getgameWin = Gametransactions::where('txid', $request['transaction_id'])->where('player', '=', $playerId)->where('win', '>', '0')->first();
                
                $win = '0';
                $bet = '0';

                if($getgame){
                $win = $getgame->bet / 100 ?? 0;
                }

                if($getgameWin) {
                $bet = $getgameWin->win / 100 ?? 0;
                }

                if(!$getgameWin and !$getgame) {
                return response()->json([
                        'status' => '404', 'error' => 'bluecheese - txId doesnt exist'
                ])->setStatusCode(404);
                }


                $gamedata = 'rollback';
                $final = 0;
            }

            if($bet < '0' or $win < '0') {
                return response()->json([
                        'status' => '500', 'error' => 'bluecheese'
                ])->setStatusCode(500);
            }

            $checkifsentalready = Gametransactions::where('txid', $request['transaction_id'])->where('player', '=', $playerId)->first(); 
            if($checkifsentalready and $request['action'] !== 'rollback') {
                return response()->json([
                        'status' => '200', 'error' => 'bluecheese - txId exist already'
                ])->setStatusCode(200);
            }


            $roundingb = $bet * 100;
            $roundingintb = (int)$roundingb;

            $roundingw = $win * 100;
            $roundingintw = (int)$roundingw;

            $baseurl = $findoperator->callbackurl;
            $prefix = $findoperator->slots_prefix;
            $verifySign = md5($findoperator->apikey.'-'.$roundid.'-'.$findoperator->operator_secret);


            $OperatorTransactions = Gametransactions::create(['casinoid' => $findoperator->id, 'currency' => 'USD', 'player' => $playerId, 'ownedBy' => $findoperator->ownedBy, 'bet' => $roundingintb, 'win' => $roundingintw, 'gameid' => $gamedata, 'txid' => $request['transaction_id'], 'roundid' => $roundid, 'type' => 'slots', 'rawdata' => '[]']);

            //$OperatorRaw = GametransactionsRaw::create(['casinoid' => $findoperator->id, 'player' => $playerId, 'ownedBy' => $findoperator->ownedBy, 'txid' => $request['transaction_id'], 'roundid' => $roundid, 'rawdata' => json_encode($request->all(), JSON_UNESCAPED_UNICODE)]);


            if($final === '1') {
            $totalTxs = Gametransactions::where('roundid', '=', $roundid)->where('player', '=', $playerId)->get();
            $totalWin = $totalTxs->sum('win');
            $totalBet = $totalTxs->sum('bet');

            $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gamedata.'&roundid='.$roundid.'&playerid='.$playerId.'&bet='.$roundingintb.'&win='.$roundingintw.'&bonusmode=0&final='.$final.'&totalWin='.$totalWin.'&totalBet='.$totalBet.'&sign='.$verifySign;
            } else {
            $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gamedata.'&roundid='.$roundid.'&playerid='.$playerId.'&bet='.$roundingintb.'&win='.$roundingintw.'&bonusmode=0&final='.$final.'&sign='.$verifySign;
            }
                ////Log::warning($url);




            ////Log::warning($url);
            if($findoperator->extendedApi === '1') {
                $userdata = array('sign' => $verifySign, "currency" => $currency, "gameid" => $gamedata, "roundid" => $roundid, "playerid" => $playerId, "bet" => $roundingintb, "win" => $roundingintw, "bonusmode" => "0", "final" => $final);
            } else {
                $userdata = array('sign' => $verifySign, "roundid" => $roundid);
            }                 
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
                $responsecurl = json_decode($responsecurl, true);


                try {
                    if($roundingintw > 0 || $roundingintb > 0) {
                        $processGgr = Gametransactions::processGgr($gamedata, $findoperator->id, $roundingintw, $roundingintb);
                }

                } catch (\Exception $exception) {
                    Log::emergency('Processing GGR error: '.$gamedata);
                }


                try {
                return response()->json([
                        'status' => '200', 'balance' => $responsecurl['result']['balance'] / 100
                ])->setStatusCode(200);

                } catch (\Exception $exception) {
                    return response()->json([
                            'status' => '403', 'error' => 'bluecheese - balance too small'
                    ])->setStatusCode(403);

                }
        }
}


    public function getGamesList(Request $request)
    {
                $url = 'https://api.thegameprovider.com/api/seamless/provider';
                $jsonbody = json_encode(array(
                    "api_password" => 'WGkB96jKgJVuyi1CNZ',
                    "api_login" => 'casinotv_mc_s',
                    "method" => "getGameList",
                    "show_systems" => 0, //if false, parameter is not needed
                    "show_additional" => false, //if false, parameter is not needed
                    "currency" => "USD"
                ));
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
            echo $responsecurl;

            $responsecurl = json_decode($responsecurl, true);


    }

    public static function createPlayer($userid, $casino_id, $name, $currency)
    {
                $url = 'https://api.thegameprovider.com/api/seamless/provider';
                $jsonbody = json_encode(array(
                    "api_password" => 'WGkB96jKgJVuyi1CNZ',
                    "api_login" => 'casinotv_mc_s',
                    "method" => "createPlayer",
                    "user_username" => $userid.'-'.$casino_id, //if false, parameter is not needed
                    "user_nickname" => $name, //if false, parameter is not needed
                    "user_password" => "defaultPassword",
                    "currency" => $currency
                ));
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
            //echo $responsecurl;

            $responsecurl = json_decode($responsecurl, true);
    }

    public static function loginPlayer($userid, $casino_id, $name, $currency)
    {
                $url = 'https://api.thegameprovider.com/api/seamless/provider';
                $jsonbody = json_encode(array(
                    "api_password" => 'WGkB96jKgJVuyi1CNZ',
                    "api_login" => 'casinotv_mc_s',
                    "method" => "loginPlayer",
                    "user_username" => $userid.'-'.$casino_id, //if false, parameter is not needed
                    "user_nickname" => $name, //if false, parameter is not needed
                    "user_password" => "defaultPassword",
                    "currency" => $currency
                ));
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

            return json_decode($responsecurl, true);
    }


    public static function createGame($playerId, $gameId, $casino_id, $mode, $name)
    {
                if($mode === 'demo') {
                    $funplayState = 1;
                } else {
                    $funplayState = 0;
                }

                $playerId = $playerId.'-'.$casino_id;

                $localPlayerId = BlueOceanPlayers::where('playerid', $playerId)->where('casino_id', $casino_id)->first();


                $selectCasino = Gameoptions::where('id', $casino_id)->first();
                //$localPlayerId = BlueOceanPlayers::where('playerid', $playerId)->first();

                if(!$localPlayerId) {
                $createPlayerId = BlueOceanPlayers::create(['playerid' => $playerId, 'casino_id' => $casino_id]);
                }
                $playerId = BlueOceanPlayers::where('playerid', $playerId)->first()->id;
                //Log::warning($playerId);
                $createUser = self::createPlayer($playerId, $casino_id, $name, $selectCasino->native_currency);
                $loginUser = self::loginPlayer($playerId, $casino_id, $name, $selectCasino->native_currency);
                $returnUrl = $selectCasino->operatorurl;

                //Log::warning($createUser);
                //Log::warning($loginUser);

                $url = 'https://api.thegameprovider.com/api/seamless/provider';
                $jsonbody = json_encode(array(
                    "api_password" => 'WGkB96jKgJVuyi1CNZ',
                    "api_login" => 'casinotv_mc_s',
                    "method" => "getGame",
                    "user_username" => $playerId.'-'.$casino_id, //if false, parameter is not needed
                    "user_nickname" => $name, //if false, parameter is not needed
                    "user_password" => "defaultPassword",
                    "gameid" => $gameId,
                    "homeurl" => $returnUrl,
                    "cashierurl" => $returnUrl,
                    "play_for_fun" => $funplayState,
                    "currency" => $selectCasino->native_currency
                ));
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
            //Log::warning(json_encode($responsecurl));
            curl_close($curlcatalog);
            $responsecurl = json_decode($responsecurl, true);
            

            try {
            return array('url' => $responsecurl['response']) ?? 'error';

                } catch (\Exception $exception) {
                    return 'error';                    
            }

    }






    }
