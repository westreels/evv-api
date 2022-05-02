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
use \App\Models\Gametransactions;
use \App\Models\GametransactionsRaw;

class UpgamesController extends Controller
{



    public function endpoint(Request $request)
    {
        //Log::notice('Live casino: '.$request);

        if ($request['action'] === 'balance') {
            return $this->balance($request);
        }
        if ($request['action'] === 'debit') {
            return $this->bet($request);
        }
        if ($request['action'] === 'credit') {
            return $this->bet($request);
        }
 
    }

    /**
     *     @param $create Live Game
     */
    public function createGame($playerId, $game_id, $casino_id, $name)
     {
            $livecasino_apikey = '=E2YiVWYwYWMmNTZzEmZllDZ2cDNyYmM3ImNhFDOmJjN6cTOwkDN1QTN';
            $findoperator = Gameoptions::where('id', $casino_id)->first();

            $id = $playerId;
            $idreplace = preg_replace("/[^0-9]/", "", $id );
            $user = $playerId.'-'.$findoperator->id;

                $userdata = array('userId' => substr($idreplace, 0,12), 'username' => $user, 'nick' => $name, 'currency' => $findoperator->native_currency);
                $jsonbody = json_encode($userdata);
                $curlcatalog = curl_init();
                curl_setopt_array($curlcatalog, array(
                CURLOPT_URL => 'https://gateway.ssl256bit.com/catalog_service/set_user_data',
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
                "X-CASINO-TOKEN: ".$livecasino_apikey,
                "Content-Type: application/json"
              ),
            ));
            $responsecurl = curl_exec($curlcatalog);
            curl_close($curlcatalog);
            $responsecurl = json_decode($responsecurl);

            if ($game_id == 'up_baccarat') {
                $gameid = 'g01.ssl256bit.com/_Games/Baccarat/?gameId=OS_Baccarat';
            }
            elseif ($game_id == 'blackjack' || $game_id == 'up_blackjack') {
                $gameid = 'g01.ssl256bit.com/_Games/blackjack_live/#/?gameId=OS_Blackjack';
            }
            elseif ($game_id == 'blackjack2' || $game_id == 'up_blackjack2') {
                $gameid = 'g01.ssl256bit.com/_Games/blackjack_live/clients/Blackjack2/#/?gameId=OS_Blackjack_2';
            }
            elseif ($game_id == 'blackjack3' || $game_id == 'up_blackjack3') {
                $gameid = 'g01.ssl256bit.com/_Games/blackjack_live/clients/Blackjack3/#/?gameId=OS_Blackjack_3';
            }
            elseif ($game_id == 'viproulette' || $game_id == 'up_viproulette') {
                $gameid = 'g01.ssl256bit.com/_Games/roulette/clients/OriginalSpirit/?gameId=OS_Roulette_2';
            }
            elseif ($game_id == 'autowheel' || $game_id == 'up_autowheel') {
                $gameid = 'g01.ssl256bit.com/_Games/roulette/clients/OriginalSpirit/?gameId=OS_Roulette_3';
            }
            elseif ($game_id == 'autoroulette' || $game_id == 'up_autoroulette') {
                $gameid = 'g01.ssl256bit.com/_Games/roulette/clients/OriginalSpirit/?gameId=OS_Roulette_3';
            }

            elseif ($game_id == 'rapidroulette' || $game_id == 'up_rapidroulette') {
                $gameid = 'g01.ssl256bit.com/_Games/roulette/clients/OriginalSpirit/?gameId=OS_Roulette_4';
            }

            $url = 'https://'.$gameid.'&clientId=&mode=Real&gameToken='.$responsecurl->sessionToken.'&casinoId=54549116&lobbyUrl=https%3A%2F%2Fg01.ssl256bit.com%2F_Apps%2Flobby%2F%3FcatalogId%3D100092_3685299&sessionToken='.$responsecurl->sessionToken.'&url=https%3A%2F%2F'.$findoperator->operatorurl;
            return array('url' => $url);
    }

    public function balance(Request $request)
     {
            $token = $request['username'];
            $explode = explode('-', $token);
            $currency = $explode[1];
            $playerId = $explode[0];
                
            $getoperator = $explode[2];
            $findoperator = Gameoptions::where('id', $getoperator)->first();
            $baseurl = $findoperator->callbackurl;
            $prefix = $findoperator->livecasino_prefix;
            $url = $baseurl.$prefix.'/balance?currency='.$currency.'&playerid='.$playerId;
            $userdata = array('playerId' => $playerId, "currency" => $currency);
            $jsonbody = json_encode($userdata);
            $curlcatalog = curl_init();
            curl_setopt_array($curlcatalog, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 30,
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
                            'balance' => number_format(($responsecurl['result']['balance'] / 100), 2, '.', '')
                    ])->setStatusCode(200);
    }


    

    /**
     * @param $bet return
     */
    public function bet(Request $request)
    {
            //$content = json_encode($request->getContent());
            //Log::critical($request);

            $decode = json_decode($request, true);
            $token = $request['username'];
            $currency = explode('-', $token);
            $currency = $currency[1];
            $playerId = explode('-', $token);
            $playerId = $playerId[0];
                
            $getoperator = explode('-', $token);
            $getoperator = $getoperator[2];
            $findoperator = Gameoptions::where('id', $getoperator)->first();

            if($request['action'] === 'debit'){
            $bet = $request['amount'];
            $win = '0.00';

            $final = 0;

            } elseif($request['action'] === 'credit') {
            $win = $request['amount'];
            $bet = '0.00';
            $final = 1;
            }
            $roundid = $request['round_id'];
            $subgame = $request['game_id'];
            $gamedata = $request['game_id'];

            $txid = $request['transaction_id'];


            if($gamedata === 100178) {
                $gameid = 'up_rapidroulette';
            } elseif($gamedata === '100179') {
                $gameid = 'up_blackjack';
            } elseif($gamedata === '100180') {
                $gameid = 'up_blackjack2';
            } elseif($gamedata === '100180') {
                $gameid = 'up_blackjack';
            } elseif($gamedata === '100176') {
                $gameid = 'up_baccarat';
            } elseif($gamedata === '100169') {
                $gameid = 'up_viproulette';
            } elseif($gamedata === '100166') {
                $gameid = 'up_autowheel';
            } else {
                $gameid = 'up_blackjack';
            }

                $roundingb = $bet * 100;
                $roundingintb = (int)$roundingb;

                $roundingw = $win * 100;
                $roundingintw = (int)$roundingw;

                    $baseurl = $findoperator->callbackurl;
                    $prefix = $findoperator->slots_prefix;
                    
                    $verifySign = md5($findoperator->apikey.'-'.$roundid.'-'.$findoperator->operator_secret);

                    $OperatorTransactions = Gametransactions::create(['casinoid' => $findoperator->id, 'currency' => $findoperator->native_currency, 'player' => $playerId, 'ownedBy' => $findoperator->ownedBy, 'bet' => $roundingintb, 'win' => $roundingintw, 'gameid' => $gameid, 'txid' => $txid, 'roundid' => $roundid, 'type' => 'slots', 'rawdata' => '[]']);

                    $OperatorRaw = GametransactionsRaw::create(['casinoid' => $findoperator->id, 'player' => $playerId, 'ownedBy' => $findoperator->ownedBy, 'txid' => $txid, 'roundid' => $roundid, 'rawdata' => json_encode($request->all(), JSON_UNESCAPED_UNICODE)]);

                    if($final === 1) {
                    $totalTxs = Gametransactions::where('roundid', '=', $roundid)->where('player', '=', $playerId)->get();
                    $totalWin = $totalTxs->sum('win');
                    $totalBet = $totalTxs->sum('bet');

                    $url = $baseurl.$prefix.'/bet?currency='.$currency.'&sign='.$verifySign.'&gameid='.$gameid.'&roundid='.$roundid.'&playerid='.$playerId.'&bet='.$roundingintb.'&totalWin='.$totalWin.'&totalBet='.$totalBet.'&win='.$roundingintw.'&final='.$final.'&bonusmode=0';

                    } else {
                    $url = $baseurl.$prefix.'/bet?currency='.$currency.'&sign='.$verifySign.'&gameid='.$gameid.'&roundid='.$roundid.'&playerid='.$playerId.'&bet='.$roundingintb.'&win='.$roundingintw.'&final='.$final.'&bonusmode=0';
                    }
                    //Log::alert($url);
                    $userdata = array('playerId' => $playerId, "currency" => $currency);
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


                if($responsecurl['result']['balance']) {
                   $processGgr = Gametransactions::processGgr($gameid, $findoperator->id, $roundingintw, $roundingintb);
                /*
                try {
                $OperatorTransactions = OperatorTransactions::create(['operator' => $getoperator,'casinoid' => $findoperator->casinoid,'playerid' => $playerId, 'currency' => $currency, 'bet' => $roundingintb, 'win' => $roundingintw, 'gameid' => $gamedata, 'transactionid' => $roundid, 'callback_state' => '0', 'type' => 'slots', 'callback_tries' => '0', 'rawdata' => '0'
                ]);

                } catch (\Exception $exception) {
                    //Error trying to create operator transaction
                }
                */

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