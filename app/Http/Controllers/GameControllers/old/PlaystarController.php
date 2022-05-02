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
use outcomebet\casino25\api\client\Client;
use \Cache;
use \App\Models\PlaystarPlayers;


class PlaystarController extends Controller
{
    /**
     * @param $endpoint where callback URL & method is distributed
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticate(Request $request)
    {
        
            //Disabled
            //return [];
            Log::emergency($request);
            $token = PlaystarPlayers::where('sessionid', $_REQUEST['access_token'])->first();
            ////Log::emergency($token);
            $explodeUserID = explode('-', $token->playerid);
            $currency = $explodeUserID[1];
            $playerId = $explodeUserID[0];
                 
            $getoperator = $token->casino_id;
            $findoperator = \App\Models\Gameoptions::where('id', $getoperator)->first();

                $baseurl = $findoperator->callbackurl;
                $prefix = $findoperator->slots_prefix;
                $url = $baseurl.$prefix.'/balance?currency='.$currency.'&playerid='.$playerId;
                //log::warning($url);

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
            ////Log::emergency($responsecurl);
            $err = curl_error($curlcatalog);
            if($err) {
            ////Log::emergency('balance error: '.$err);
            }
            curl_close($curlcatalog);

            $responsecurl = json_decode($responsecurl, true); 

            $array = array('status_code' => 0, 'member_id' => $_REQUEST['access_token'], 'member_name' => $token->nick, 'balance' => $responsecurl['result']['balance']);
            //Log::emergency($array);

        echo json_encode($array);

    }
    /**
     * @param $endpoint where callback URL & method is distributed
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalance(Request $request)
    {
            //Disabled
            //return [];

            //Log::emergency($request);
            $token = PlaystarPlayers::where('sessionid', $_REQUEST['access_token'])->first();
            //Log::emergency($token);
            $explodeUserID = explode('-', $token->playerid);
            $currency = $explodeUserID[1];
            $playerId = $explodeUserID[0];
                 
            $getoperator = $token->casino_id;
            $findoperator = \App\Models\Gameoptions::where('id', $getoperator)->first();

                $baseurl = $findoperator->callbackurl;
                $prefix = $findoperator->slots_prefix;
                $url = $baseurl.$prefix.'/balance?currency='.$currency.'&playerid='.$playerId;
                //log::warning($url);

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
            //Log::emergency($responsecurl);
            $err = curl_error($curlcatalog);
            if($err) {
            //Log::emergency('balance error: '.$err);
            }
            curl_close($curlcatalog);

            $responsecurl = json_decode($responsecurl, true); 

            $array = array('status_code' => 0, 'balance' => $responsecurl['result']['balance']);
            //Log::emergency($array);

        echo json_encode($array);

    }

    public function createDemoGame($playerId, $game_id, $casino_id, $nick)
    {

            $url = 'https://datagamble-api.claretfox.com/launch/?host_id=c2bfa27633949f5d611758f151ed88eb&game_id='.$game_id.'&lang=en-US';
        return array('url' => $url);

    }

    public function createGame($playerId, $game_id, $casino_id, $nick)
    {

            
            //Disabled
            //return [];

            $localPlayerId = PlaystarPlayers::where('playerid', $playerId)->where('casino_id', $casino_id)->first();
            if(!$localPlayerId) {
            $createPlayerId = PlaystarPlayers::create(['playerid' => $playerId, 'nick' => $nick, 'casino_id' => $casino_id, 'sessionid' => md5($playerId.'-'.now())]);
            }
            $sessionid = PlaystarPlayers::where('playerid', $playerId)->where('casino_id', $casino_id)->first()->sessionid;


            $findoperator = \App\Models\Gameoptions::where('id', $casino_id)->first();           
            $url = 'https://datagamble-api.claretfox.com/launch/?host_id=c2bfa27633949f5d611758f151ed88eb&game_id='.$game_id.'&lang=en-US&access_token='.$sessionid;
            Log::emergency($url);
        return array('url' => $url);

    }

    public function bet(Request $request)
    {

            //Disabled
            //return [];
            ////Log::emergency($request);


            $getUsername = $request['access_token'];
            $explode = explode('-', $getUsername);
            $tokenDecrypt = PlaystarPlayers::where('sessionid', $request['access_token'])->first();
            $tokenDecryptExplode = explode('-', $tokenDecrypt->playerid);
            $currency = $tokenDecryptExplode[1];
            $playerName = $tokenDecryptExplode[0];
            $gameId = $request->game_id;
            $deposit = $request->total_win ?? 0;
            $withdraw = $request->total_bet ?? 0;
            $transactionRef = $request->txn_id;
            $getoperator = $tokenDecrypt->casino_id;
            $findoperator = \App\Models\Gameoptions::where('id', $getoperator)->first();           
            $final = 0;


            $baseurl = $findoperator->callbackurl;
            $prefix = $findoperator->slots_prefix;
                
            $OperatorTransactions = \App\Models\Gametransactions::create(['casinoid' => $findoperator->id, 'currency' => 'USD', 'player' => $playerName, 'ownedBy' => $findoperator->ownedBy, 'bet' => $withdraw, 'win' => $deposit, 'gameid' => $gameId, 'txid' => $transactionRef, 'type' => 'slots', 'rawdata' => '[]']);

            if($deposit > 0 || $withdraw > 0) {
            $processGgr = \App\Models\Gametransactions::processGgr($gameId, $getoperator, $deposit, $withdraw);
            }

            $baseurl = $findoperator->callbackurl;
            $prefix = $findoperator->slots_prefix;

            $verifySign = md5($findoperator->apikey.'-'.$transactionRef.'-'.$findoperator->operator_secret);

            if($final === 1) {

              $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gameId.'&roundid='.$transactionRef.'&playerid='.$playerName.'&bet='.$withdraw.'&win='.$deposit.'&bonusmode=0&final='.$final.'&sign='.$verifySign.'&totalWin='.$deposit.'&totalBet='.$withdraw;

            } else {
              $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gameId.'&roundid='.$transactionRef.'&playerid='.$playerName.'&bet='.$withdraw.'&win='.$deposit.'&bonusmode=0&final='.$final.'&sign='.$verifySign;
            }

            ////Log::emergency($url);
            if($findoperator->extendedApi === '1') {
            $userdata = array('sign' => $verifySign, "currency" => $currency, "gameid" => $gameId, "roundid" => $transactionRef, "playerid" => $playerName, "bet" => $withdraw, "win" => $deposit, "bonusmode" => "0", "final" => $final);
            } else {
            $userdata = array('sign' => $verifySign, "roundid" => $transactionRef);
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
        //Log::emergency($responsecurl);
        $responsecurl = json_decode($responsecurl, true);

        $array = array('status_code' => 0, 'member_id' => $getUsername, 'balance' => $responsecurl['result']['balance']);
        return response()->json($array);

        }

    }
