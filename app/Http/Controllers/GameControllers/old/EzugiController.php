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
use Illuminate\Support\Facades\Crypt;
use \Cache;
use \App\Models\PlaystarPlayers;
use Carbon\Carbon;
use \App\Models\EzugiSessions;


class EzugiController extends Controller
{


    /**
     * @param $endpoint where callback URL & method is distributed
     * @return \Illuminate\Http\JsonResponse
     */
    public function rollback(Request $request)
    {

        Log::info('Rollback: '.$request);



        return [];


    }


    /**
     * @param $endpoint where callback URL & method is distributed
     * @return \Illuminate\Http\JsonResponse
     */
    public function method(Request $request)
    {

        Log::info($request);



        return [];


    }

    /**
     * @param $endpoint where callback URL & method is distributed
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalance(Request $request)
    {
            Log::info($request);
            $token = EzugiSessions::where('sessionid', $request['token'])->first();
            $currency = explode('-', $token->playerid);
            $currency = $currency[1];
            $playerId = explode('-', $token->playerid);
            $playerId = $playerId[0];
                 
            $getoperator = $token->casino_id;
            $findoperator = \App\Models\Gameoptions::where('id', $getoperator)->first();

                $baseurl = $findoperator->callbackurl;
                $prefix = $findoperator->livecasino_prefix;
                $url = $baseurl.$prefix.'/balance?currency='.$currency.'&playerid='.$playerId;
                log::info($url);

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
            Log::info($responsecurl);
            $err = curl_error($curlcatalog);
            if($err) {
            Log::info('balance error: '.$err);
            }
            curl_close($curlcatalog);

            $responsecurl = json_decode($responsecurl, true); 

            $balance = number_format(($responsecurl['result']['balance'] / 100), 2, '.', '');
            $array = array('operatorId' => 10490001, 'uid' => $playerId, 'nickName' => $playerId, 'token' => $playerId.'-'.$request['token'], 'playerTokenAtLaunch' => $request['token'], 'balance' => $balance, 'currency' => 'USD', 'language' => 'en', 'date' => Carbon::now()->toDateTimeString(), 'clientIP' => '51.83.95.241', 'VIP' => '0', 'timestamp' => time(), 'errorCode' => 0, 'errorDescription' => 'ok');

            Log::info($array);

        return response()->json($array);

    }

    public function createGame($playerId, $game_id, $casino_id)
    {
            $sessionIdGen = md5($playerId.'-'.now());
            $createPlayerId = EzugiSessions::create(['playerid' => $playerId, 'casino_id' => $casino_id, 'sessionid' => $sessionIdGen]);
            $sessionid = $sessionIdGen;


            $findoperator = \App\Models\Gameoptions::where('id', $casino_id)->first();         


            


            $url = 'https://playint.tableslive.com/auth/?token='.$sessionid.'&operatorId=10490001&language=en&clientType=html5&openTable='.$game_id.'&homeUrl='.$findoperator->operatorurl;
            Log::emergency($url);
        return array('url' => $url);

    }

    public function debit(Request $request)
    {

            Log::info('Debit EZUGI: '.$request);

            $content = json_decode($request->getContent());

            $getUsername = $request['token'];
            $explode = explode('-', $getUsername);
            $tokenDecrypt = EzugiSessions::where('sessionid', $explode[1])->first();
            $tokenDecryptExplode = explode('-', $tokenDecrypt->playerid);
            $currency = $tokenDecryptExplode[1];
            $playerName = $tokenDecryptExplode[0];
            $gameId = \App\Models\Gamelist::where('api_ext', 'ezugi')->where('extra_id', $content->tableId)->first()->game_id;
            $deposit = number_format(0, 0, '.', '');
            $playerId = $tokenDecryptExplode[0];
            $withdraw = number_format(($content->debitAmount * 100 ?? 0), 0, '.', '');
            $transactionRef = $content->transactionId;
            $roundId = $content->roundId;
            $getoperator = $tokenDecrypt->casino_id;
            $findoperator = \App\Models\Gameoptions::where('id', $getoperator)->first();           
            $final = 0;


            $baseurl = $findoperator->callbackurl;
            $prefix = $findoperator->slots_prefix;
                
            $OperatorTransactions = \App\Models\Gametransactions::create(['casinoid' => $findoperator->id, 'currency' => 'USD', 'player' => $playerId, 'ownedBy' => $findoperator->ownedBy, 'bet' => $withdraw, 'win' => $deposit, 'gameid' => $gameId, 'txid' => $transactionRef, 'roundid' => $roundId, 'type' => 'slots', 'rawdata' => '[]']);

            if($deposit > 0 || $withdraw > 0) {
            $processGgr = \App\Models\Gametransactions::processGgr($gameId, $getoperator, $deposit, $withdraw);
            }

            $baseurl = $findoperator->callbackurl;
            $prefix = $findoperator->slots_prefix;

            $verifySign = md5($findoperator->apikey.'-'.$transactionRef.'-'.$findoperator->operator_secret);

            if($final === 1) {

              $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gameId.'&roundid='.$transactionRef.'&playerid='.$playerId.'&bet='.$withdraw.'&win='.$deposit.'&bonusmode=0&final='.$final.'&sign='.$verifySign.'&totalWin='.$deposit.'&totalBet='.$withdraw;

            } else {
              $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gameId.'&roundid='.$transactionRef.'&playerid='.$playerId.'&bet='.$withdraw.'&win='.$deposit.'&bonusmode=0&final='.$final.'&sign='.$verifySign;
            }

            Log::emergency($url);
            if($findoperator->extendedApi === '1') {
            $userdata = array('sign' => $verifySign, "currency" => $currency, "gameid" => $gameId, "roundid" => $transactionRef, "playerid" => $playerId, "bet" => $withdraw, "win" => $deposit, "bonusmode" => "0", "final" => $final);
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
        Log::emergency($responsecurl);
        $responsecurl = json_decode($responsecurl, true);
            $balance = number_format(($responsecurl['result']['balance'] / 100), 2, '.', '');

            $array = array('operatorId' => 10490001, 'uid' => $playerName, 'nickName' => $playerName, 'token' => $getUsername, 'balance' => $balance, 'currency' => 'USD', 'language' => 'en', 'roundId' => $roundId, 'transactionId' => $transactionRef, 'bonusAmount' => 0, 'timestamp' => time(), 'errorCode' => 0, 'errorDescription' => 'ok');


        return response()->json($array);

        }


        public function credit(Request $request)
    {

            Log::info('Credit EZUGI: '.$request);

            $content = json_decode($request->getContent());

            $getUsername = $request['token'];
            $explode = explode('-', $getUsername);
            $tokenDecrypt = EzugiSessions::where('sessionid', $explode[1])->first();
            $tokenDecryptExplode = explode('-', $tokenDecrypt->playerid);
            $currency = $tokenDecryptExplode[1];
            $playerName = $tokenDecryptExplode[0];
            $playerId = $tokenDecryptExplode[0];
            $gameId = \App\Models\Gamelist::where('api_ext', 'ezugi')->where('extra_id', $content->tableId)->first()->game_id;
            $deposit = number_format(($content->creditAmount * 100 ?? 0), 0, '.', '');
            $withdraw = number_format(0, 0, '.', '');
            $transactionRef = $content->transactionId;
            $roundId = $content->roundId;
            $getoperator = $tokenDecrypt->casino_id;
            $findoperator = \App\Models\Gameoptions::where('id', $getoperator)->first();           
            $final = 0;

            if($content->isEndRound === true) {
                $final = 1;
            }

            $baseurl = $findoperator->callbackurl;
            $prefix = $findoperator->slots_prefix;
                
            $OperatorTransactions = \App\Models\Gametransactions::create(['casinoid' => $findoperator->id, 'currency' => 'USD', 'player' => $playerId, 'ownedBy' => $findoperator->ownedBy, 'bet' => $withdraw, 'win' => $deposit, 'gameid' => $gameId, 'txid' => $transactionRef, 'roundid' => $roundId, 'type' => 'slots', 'rawdata' => '[]']);

            if($deposit > 0 || $withdraw > 0) {
            $processGgr = \App\Models\Gametransactions::processGgr($gameId, $getoperator, $deposit, $withdraw);
            }

            $baseurl = $findoperator->callbackurl;
            $prefix = $findoperator->slots_prefix;

            $verifySign = md5($findoperator->apikey.'-'.$transactionRef.'-'.$findoperator->operator_secret);

            if($final === 1) {
            $totalTxs = \App\Models\Gametransactions::where('roundid', '=', $roundId)->where('player', '=', $playerId)->get();
            $totalWin = $totalTxs->sum('win');
            $totalBet = $totalTxs->sum('bet');

              $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gameId.'&roundid='.$transactionRef.'&playerid='.$playerId.'&bet='.$withdraw.'&win='.$deposit.'&bonusmode=0&final='.$final.'&sign='.$verifySign.'&totalWin='.$totalWin.'&totalBet='.$totalBet;

            } else {
              $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gameId.'&roundid='.$transactionRef.'&playerid='.$playerId.'&bet='.$withdraw.'&win='.$deposit.'&bonusmode=0&final='.$final.'&sign='.$verifySign;
            }

            Log::emergency($url);
            if($findoperator->extendedApi === '1') {
            $userdata = array('sign' => $verifySign, "currency" => $currency, "gameid" => $gameId, "roundid" => $transactionRef, "playerid" => $playerId, "bet" => $withdraw, "win" => $deposit, "bonusmode" => "0", "final" => $final);
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
        Log::emergency($responsecurl);
        $responsecurl = json_decode($responsecurl, true);
            $balance = number_format(($responsecurl['result']['balance'] / 100), 2, '.', '');

            $array = array('operatorId' => 10490001, 'uid' => $playerId, 'nickName' => $playerName, 'token' => $getUsername, 'balance' => $balance, 'currency' => 'USD', 'language' => 'en', 'roundId' => $roundId, 'transactionId' => $transactionRef, 'bonusAmount' => 0, 'timestamp' => time(), 'errorCode' => 0, 'errorDescription' => 'ok');


        return response()->json($array);

        }

    }
