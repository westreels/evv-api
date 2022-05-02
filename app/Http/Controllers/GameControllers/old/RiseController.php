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

class RiseController extends Controller
{
    /**
     *     @param $create Live Game
     */
    public static function createLive($provider, $subgame, $userid, $operator, $name)
     {
            $url = 'http://93.115.26.100/shell/games/casino_new.php?usid='.$userid.'-'.$operator.'&provider='.$provider.'&gameid='.$subgame.'&name='.$name;
            Log::notice($url);
            //$userdata = array('data' => json_encode($request));
            $userdata = '';
            $jsonbody = json_encode($userdata);
            $curlcatalog = curl_init();
            curl_setopt_array($curlcatalog, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                
                CURLOPT_TIMEOUT => 15,
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
        return array('url' => $responsecurl);
    }

    /**
     *     @param $create Slot Game
     */
    public static function createSlots($provider, $subgame, $userid, $operator, $name)
    {
            $url = 'http://93.115.26.100/shell/games/casino_new.php?usid='.$userid.'-'.$operator.'&provider='.$provider.'&gameid='.$subgame.'&name='.$name;
            Log::notice($url);
            //$userdata = array('data' => json_encode($request));
            $userdata = '';
            $jsonbody = json_encode($userdata);
            $curlcatalog = curl_init();
            curl_setopt_array($curlcatalog, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                
                CURLOPT_TIMEOUT => 15,
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
        return array('url' => $responsecurl);
    }

    /**
     * @param $balance return
     */
    public function balance(Request $request)
    {
            $decode = json_decode($request, true);
            $token = $request['pid'];
            $currency = explode('-', $token);
            $currency = $currency[1];
            $playerId = explode('-', $token);
            $playerId = $playerId[0];
                
            $getoperator = explode('-', $token);
            $getoperator = $getoperator[2];
            $findoperator = \App\Models\Gameoptions::where('id', $getoperator)->first();

                $baseurl = $findoperator->callbackurl;
                $prefix = $findoperator->livecasino_prefix;

                $url = $baseurl.$prefix.'/balance?currency='.$currency.'&playerid='.$playerId;
                $userdata = array('playerid' => $playerId, 'currency' => $currency, 'sign' => md5($findoperator->apikey.'-'.$playerId.'-'.$findoperator->operator_secret));

                $jsonbody = json_encode($userdata);
                $curlcatalog = curl_init();
                curl_setopt_array($curlcatalog, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                
                CURLOPT_TIMEOUT => 15,
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

        return response()->json([
                'balance' => $responsecurl['result']['balance'] / 100
        ])->setStatusCode(200);
    }

    /**
     * @param $bet return
     */
    public function bet(Request $request)
    {
            $content = json_encode($request->getContent());
            //Log::notice($content);

            $decode = json_decode($request, true);
            $token = $request['pid'];
            $currency = explode('-', $token);
            $currency = $currency[1];
            $playerId = explode('-', $token);
            $playerId = $playerId[0];
                
            $getoperator = explode('-', $token);
            $getoperator = $getoperator[2];
            $findoperator = \App\Models\Gameoptions::where('id', $getoperator)->first();

            $bet = $request['bet'];
            $win = $request['win'];
            $roundid = $request['rid'];
            $provider = $request['product'];
            $subgame = $request['gameid'];

            $final = 1;

            $gamedata = $subgame;
            $roundingb = $bet * 100;
            $roundingintb = (int)$roundingb;

            $roundingw = $win * 100;
            $roundingintw = (int)$roundingw;

            if($provider === 'Evolution') {
                $gamedata = 'evo_lobby';
                if($request['type'] === 'BET') {
                    $final = 0;
                }
            }

            if($provider === 'vivolive') {
                $gamedata = 'vivo_lobby';
                if($request['type'] === 'BET') {
                    $final = 0;
                }
            }

            $baseurl = $findoperator->callbackurl;
            $prefix = $findoperator->slots_prefix;
            $verifySign = md5($findoperator->apikey.'-'.$roundid.'-'.$findoperator->operator_secret);
            $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gamedata.'&roundid='.$roundid.'&playerid='.$playerId.'&bet='.$roundingintb.'&win='.$roundingintw.'&bonusmode=0&final='.$final;
            //Log::critical($url);
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
                    
                    CURLOPT_TIMEOUT => 15,
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


                if($responsecurl['result']['balance']) {
                    $OperatorTransactions = \App\Models\Gametransactions::create(['casinoid' => $findoperator->id, 'currency' => 'USD', 'player' => $playerId, 'ownedBy' => $findoperator->ownedBy, 'bet' => $roundingintb, 'win' => $roundingintw, 'gameid' => $gamedata, 'txid' => $roundid, 'type' => 'slots', 'rawdata' => '[]']);


                    //roundingintb=bet, roundingintw = win
                    if($roundingintw > 0 || $roundingintb > 0) {
                        $processGgr = \App\Models\Gametransactions::processGgr($gamedata, $findoperator->id, $roundingintw, $roundingintb);
                    }

                return response()->json([
                        'balance' => $responsecurl['result']['balance'] / 100
                ])->setStatusCode(200);
              }          
            else {
                return response()->json([
                    'status' => 'error',
                    'error' => ([
                        'scope' => "user",
                        'no_refund' => "1",
                        'message' => "Not enough money"
                    ])
                ]);
            }



}
}