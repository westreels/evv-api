<?php

namespace App\Http\Controllers\GameControllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Specialtactics\L5Api\Http\Controllers\RestfulController as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use \App\Http\Controllers\Controller;
use outcomebet\casino25\api\client\Client;
use Illuminate\Http\Request;
use \Cache;
use \App\Models\Gametransactions;
use \App\Models\GametransactionsRaw;
use \App\Models\Gameoptions;
use \App\Models\DKSessions;
use Illuminate\Support\Facades\Crypt;


class DKGamesController extends Controller
{
    
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

   /**
     * @param $balance return to API
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalance(Request $request)
    {
            //Log::alert($request);
         

            $getUsername = $request['session_id'];
            $getSession = DKSessions::where('session_id', $getUsername)->first();
            if($getSession->mode === "demo") {
                $balance = $getSession->demo_bal;

                return response()->json([
                'result' => ([
                    'balance' => floatval($balance),
                ]),
                'id' => 0,
                'jsonrpc' => '2.0'
            ])->setStatusCode(200);

            } else {
            $explodePlayer = explode('-', $getSession->player_id);

            $currency = $explodePlayer[1];
            $playerId = $explodePlayer[0];
                
            $findoperator = Gameoptions::where('id', $getSession->casino_id)->first();

                $baseurl = $findoperator->callbackurl;
                $prefix = $findoperator->slots_prefix;

                $url = $baseurl.$prefix.'/balance?currency='.$currency.'&playerid='.$playerId;
                //Log::critical($url);
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
                    'balance' => floatval($responsecurl['result']['balance'] / 100),
                ]),
                'id' => 0,
                'jsonrpc' => '2.0'
            ])->setStatusCode(200);

        }
    }


    public function createRandomVal($val) {
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


    /**
     * @param $create C2 Gaming session
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function DKrealmoneysession($playerId, $game_id, $casino_id, $mode)
    {           

        $explodeBg = explode('_', $game_id);
        $gameId = $explodeBg[1];
        $findoperator = Gameoptions::where('id', $casino_id)->first();

        $localPlayerId = DKSessions::where('player_id', $playerId)->where('casino_id', $casino_id)->first();
        $demoBalance = "100";
        //if(!$localPlayerId) {
            $generateSessionId = self::createRandomVal(64);
            if($mode === 'demo') {
            $playerId = 'd'.rand(10000, 9999999999).'-'.$playerId;
            }
            $createPlayerId = DKSessions::create(['player_id' => $playerId, 'session_id' => $generateSessionId, 'casino_id' => $casino_id, 'mode' => $mode, 'demo_bal' => $demoBalance]);
        //}

        //$sessionId = DKSessions::where('player_id', $playerId)->where('casino_id', $casino_id)->first()->session_id;

    if($gameId !== 'cryptoprediction') {
        $encryptedPlayerid = bin2hex(self::encryptCasinoToken(($playerId.'-'.$casino_id), $generateSessionId));
        return array("url" => "https://s2.davidkohen.com/games/".$gameId."?session_id=".$generateSessionId."&game_id=".$gameId."&mode=".$mode."&user=".$encryptedPlayerid);
    } else {
        $encryptedPlayerid = bin2hex(self::encryptCasinoToken(($playerId.'-'.$casino_id), $generateSessionId));
        //$decrypt = self::decryptCasinoToken(hex2bin($encryptedPlayerid), $generateSessionId);
        return array("url" => "https://s2.davidkohen.com/markets/crypto-prediction?session_id=".$generateSessionId."&game_id=".$gameId."&mode=".$mode."&user=".$encryptedPlayerid);
    }
    }


   /**
     * @param $balance return to API
     * @return \Illuminate\Http\JsonResponse
     */
    public function bet(Request $request)
    {
            //Log::warning($request);
         

            $getUsername = $request['session_id'];
            $getSession = DKSessions::where('session_id', $getUsername)->first();

            if($getSession->mode === "demo") {
                $balance = $getSession->demo_bal;
                $withdraw = $request['bet'] ?? 0;
                $deposit = $request['win'] ?? 0;
                $getSession->update(['demo_bal' => ($getSession->demo_bal - $withdraw)]);
                $getSession->update(['demo_bal' => ($getSession->demo_bal + $deposit)]);

                return response()->json([
                'result' => ([
                    'balance' => floatval($getSession->demo_bal),
                ]),
                'id' => 0,
                'jsonrpc' => '2.0'
            ])->setStatusCode(200);

            } else {


            $explodePlayer = explode('-', $getSession->player_id);


            $currency = $explodePlayer[1];
            $playerId = $explodePlayer[0];
            $roundId = $request['round_id'];
            $final = $request['final'];
            $findoperator = Gameoptions::where('id', $getSession->casino_id)->first();
            $withdraw = intval($request['bet'] * 100);
            $deposit = intval($request['win'] * 100);
            $gameId = $request['game_id'];
            if($gameId === 'DK_european roulette') {
                $gameId = 'DK_european-roulette';
            }
            $transactionRef = $roundId.'-'.rand('100', '9999');
            $totalBet = intval($request['bet'] * 100);
            $totalWin = intval($request['win'] * 100);

            $OperatorTransactions = Gametransactions::create(['casinoid' => $findoperator->id, 'currency' => $findoperator->native_currency, 'player' => $playerId, 'ownedBy' => $findoperator->ownedBy, 'bet' => $withdraw, 'win' => $deposit, 'gameid' => $gameId, 'txid' => $transactionRef, 'roundid' => $roundId, 'type' => 'slots', 'rawdata' => '[]']);


                $baseurl = $findoperator->callbackurl;
                $prefix = $findoperator->slots_prefix;
                
                $verifySign = md5($findoperator->apikey.'-'.$roundId.'-'.$findoperator->operator_secret);


                if($final === "1") {
                $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gameId.'&roundid='.$roundId.'&playerid='.$playerId.'&bet='.$withdraw.'&win='.$deposit.'&bonusmode=0&final='.$final.'&totalBet='.$totalBet.'&totalWin='.$totalWin.'&sign='.$verifySign;

                if($deposit > 0 || $withdraw > 0) {
                $processGgr = Gametransactions::processGgr($gameId, $findoperator->id, $totalWin, $totalBet);
                }
                
                
                } else {
                $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gameId.'&roundid='.$roundId.'&playerid='.$playerId.'&bet='.$withdraw.'&win='.$deposit.'&bonusmode=0&final='.$final.'&sign='.$verifySign;
                }



                //Log::critical($url);
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
            //Log::critical($responsecurl);

            $responsecurl = json_decode($responsecurl, true); 

            return response()->json([
                'result' => ([
                    'balance' => floatval($responsecurl['result']['balance'] / 100),
                ]),
                'id' => 0,
                'jsonrpc' => '2.0'
            ])->setStatusCode(200);

    }

    }



   /**
     * @param $bet result processsing
     * @return \Illuminate\Http\JsonResponse
     */
    public function bet2(Request $request)
    {

            $getUsername = $request['session_id'];
            $getSession = DKSessions::where('session_id', $getUsername)->first();
            $explodePlayer = explode('-', $getSession->player_id);

            $currency = $explodePlayer[1];
            $playerId = $explodePlayer[0];
                
            $findoperator = Gameoptions::where('id', $getSession->casino_id)->first();






            $content = json_decode($request->getContent());
            $explode = explode('-', $content->params->playerName);
            $currency = $explode[1];
            $playerName = $explode[0];
            $gameId = $content->params->gameId;
            if($gameId === 'age_of_caesar_bng_html') {
                $gameId = 'DK_AgeOfKohen';
            }
            $deposit = $content->params->deposit ?? 0;
            $withdraw = $content->params->withdraw ?? 0;
            $transactionRef = $content->params->transactionRef;
            $chargefreegames = $content->params->chargeFreerounds ?? 0;
            $explodeoperator = explode('_', $content->params->sessionAlternativeId);
            $getoperator = $explodeoperator[1];
            $findoperator = Gameoptions::where('id', $getoperator)->first();           
            $checkfinal = $content->params->reason ?? 0;
            $roundId = $content->params->gameRoundRef;

            $final = 0;
            if($checkfinal === 'GAME_PLAY_FINAL') {
            $final = 1;
            }
            $selectGame = \App\Models\Gamelist::cachedList();
            $selectGame = $selectGame->where('extra_id', $gameId)->first();
            if($selectGame) {
                $gameId = $selectGame->game_id;
            }

            $baseurl = $findoperator->callbackurl;
            $prefix = $findoperator->slots_prefix;
                
            $OperatorTransactions = Gametransactions::create(['casinoid' => $findoperator->id, 'currency' => $findoperator->native_currency, 'player' => $playerName, 'ownedBy' => $findoperator->ownedBy, 'bet' => $withdraw, 'win' => $deposit, 'gameid' => $gameId, 'txid' => $transactionRef, 'roundid' => $roundId, 'type' => 'slots', 'rawdata' => '[]']);

            //$OperatorRaw = GametransactionsRaw::create(['casinoid' => $findoperator->id, 'player' => $playerName, 'ownedBy' => $findoperator->ownedBy, 'txid' => $transactionRef, 'roundid' => $roundId, 'rawdata' => json_encode($request->all(), JSON_UNESCAPED_UNICODE)]);
            //Log::warning($request->all());

            if($deposit > 0 || $withdraw > 0) {
            $processGgr = Gametransactions::processGgr($gameId, $getoperator, $deposit, $withdraw);
            }
            $checkBonus = $content->params->spinDetails->winType ?? 0;
            $bonusmode = 0;
            if($checkBonus === 'free') {
                $bonusmode = 1;
            }


            $verifySign = md5($findoperator->apikey.'-'.$roundId.'-'.$findoperator->operator_secret);
            if($final === 1) {
            $totalTxs = Gametransactions::where('roundid', '=', $roundId)->where('player', '=', $playerName)->get();
            $totalWin = $totalTxs->sum('win') ?? 0;
            $totalBet = $totalTxs->sum('bet') ?? 0;
            $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gameId.'&roundid='.$roundId.'&playerid='.$playerName.'&bet='.$withdraw.'&win='.$deposit.'&bonusmode='.$bonusmode.'&final='.$final.'&totalBet='.$totalBet.'&totalWin='.$totalWin.'&sign='.$verifySign;
            } else {
            $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gameId.'&roundid='.$roundId.'&playerid='.$playerName.'&bet='.$withdraw.'&win='.$deposit.'&bonusmode='.$bonusmode.'&final='.$final.'&sign='.$verifySign;
            }

            if($findoperator->extendedApi === '1') {
            $userdata = array('sign' => $verifySign, "currency" => $currency, "gameid" => $gameId, "txid" => $transactionRef, "roundid" => $roundId, "playerid" => $playerName, "bet" => $withdraw, "win" => $deposit, "bonusmode" => "0", "final" => $final);
            } else {
            $userdata = array('sign' => $verifySign, "txid" => $transactionRef, "roundid" => $roundId);
            }
            //Log::notice($url);
            $jsonbody = json_encode($userdata);
            $curlcatalog = curl_init();
            curl_setopt_array($curlcatalog, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 1,
            CURLOPT_TIMEOUT => 10,
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
 
        $freegames = 0;
        if ($responsecurl['result']['freegames'] > 0 ) {
            $freegames = $responsecurl['result']['freegames'];
            $balance = (int) $responsecurl['result']['balance'];

                return response()->json([   
                    'result' => [   
                        'newBalance' =>  (int) $responsecurl['result']['balance'],   
                        'transactionId' => $content->params->transactionRef,    
                        'freeroundsLeft' => $freegames    
                    ],  
                    'id' => $content->id,   
                    'jsonrpc' => '2.0'  
                ]);

        } else {

        return response()->json([
            'result' => [
                'newBalance' => (int) $responsecurl['result']['balance'],
                'transactionId' => $content->params->transactionRef
            ],
            'id' => $content->id,
            'jsonrpc' => '2.0'
        ]);
        }
    }

   /**
     * @param $balance return to API
     * @return \Illuminate\Http\JsonResponse
     */
    public function balance(Request $request)
    {
                $content = json_decode($request->getContent());
                $explode = explode('-', $content->params->playerName);
                $currency = $explode[1];
                $playerName = $explode[0];
                $gameId = $content->params->gameId;
                if($gameId === 'age_of_caesar_bng_html') {
                    $gameId = 'DK_AgeOfKohen';
                }
                $explodeoperator = explode('_', $content->params->sessionAlternativeId);
                $getoperator = $explodeoperator[1];
                $findoperator = \App\Models\Gameoptions::where('id', $getoperator)->first();
                $baseurl = $findoperator->callbackurl;
                $prefix = $findoperator->slots_prefix;

                $url = $baseurl.$prefix.'/balance?currency='.$currency.'&playerid='.$playerName;
                $userdata = array('playerid' => $playerName, 'currency' => $currency, 'sign' => md5($findoperator->apikey.'-'.$playerName.'-'.$findoperator->operator_secret));

                $jsonbody = json_encode($userdata);
                $curlcatalog = curl_init();
                curl_setopt_array($curlcatalog, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 1,
                CURLOPT_TIMEOUT => 10,
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
     
            $freegames = 0;
            if ($responsecurl['result']['freegames'] and $responsecurl['result']['freegames'] > 0 ) {
                $freegames = $responsecurl['result']['freegames'];
                $balance = (int) $responsecurl['result']['balance'];

            return response()->json([
                'result' => ([
                    'balance' =>  (int) $responsecurl['result']['balance'],
                    'freeroundsLeft' => (int) $freegames
                ]),
                'id' => $content->id,
                'jsonrpc' => '2.0'
            ]);

            } else {

            return response()->json([
                'result' => ([
                    'balance' =>  (int) $responsecurl['result']['balance']
                ]),
                'id' => $content->id,
                'jsonrpc' => '2.0'
            ]);
            }
    }


}
