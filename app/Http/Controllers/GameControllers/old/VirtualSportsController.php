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
use \App\Models\Gametransactions;
use \App\Models\GametransactionsRaw;

class VirtualSportsController extends Controller

{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function virtualCallbacks(Request $request)
    {
        //Log::warning($request);
        if ($request['tag']['game'] === 'none') {
            return $this->getBalanceVirtual($request);
        } else {
            return $this->betVirtualSports($request);
            }
    }

    public function createGame($playerId, $game_id, $casino_id)
    {
            $together = $playerId.'-'.$casino_id;
            $findoperator = \App\Models\Gameoptions::where('id', $casino_id)->first();
            $vsSessionsId = \App\Models\VirtualSportsSessions::where('player_id', $playerId)->orderBy('created_at', 'desc')->first();

            if($vsSessionsId and $vsSessionsId->created_at > \Carbon\Carbon::now()->subMinutes(15)) {
                 $url = 'https://bulk1.vlobby.co/?l=en&c=USD&d='.$vsSessionsId->session_id.'&css='.md5($casino_id);
            } else {
                $md5Session = md5($playerId.'-'.$casino_id).md5(now());
                $OperatorTransactions = \App\Models\VirtualSportsSessions::create(['session_id' => $md5Session,'player_id' => $playerId,'casino_id' => $casino_id]);
                $vsSessionsId = $md5Session;
                $url = 'https://bulk1.vlobby.co/?l=en&c=USD&d='.$md5Session.'&css='.md5($casino_id);
            }
                return array('url' => $url);
    }

    public function createDemoGame($playerId, $game_id, $casino_id)
    {
                $url = 'https://bulk1.vlobby.co/?l=en&c=USD&css='.md5($casino_id);
                return array('url' => $url);
    }

    public function getBalanceVirtual(Request $request)
    {
            $token = $request->session;
            $vsSessionsId = \App\Models\VirtualSportsSessions::where('session_id', $token)->orderBy('created_at', 'desc')->first();
            $token = $vsSessionsId->player_id;

            $currency = explode('-', $token);
            $currency = $currency[1];
            $playerId = explode('-', $token);
            $playerId = $playerId[0];
            $gamedata = $request['tag']['game_id'];

            $getoperator = $vsSessionsId->casino_id;
            $findoperator = \App\Models\Gameoptions::where('id', $getoperator)->first();

            if($findoperator->active === '1') {
                $baseurl = $findoperator->callbackurl;
                $prefix = $findoperator->livecasino_prefix;
                $url = $baseurl.$prefix.'/balance?currency='.$currency.'&playerid='.$playerId;
                //Log::critical($url);
                $userdata = array('operator' => md5($playerId.'-'.$findoperator->apikey));
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

                return response()->json([
                'status' => 'OK',
                'balance' => $responsecurl['result']['balance'] / 100,
                'currency' => 'USD']);
    }
    }

    
    public function betVirtualSports(Request $request)
    {
            $token = $request->session;
            $vsSessionsId = \App\Models\VirtualSportsSessions::where('session_id', $token)->orderBy('created_at', 'desc')->first();
            $token = $vsSessionsId->player_id;
            $currency = explode('-', $token);
            $currency = $currency[1];
            $playerId = explode('-', $token);
            $playerId = $playerId[0];
            $gamedata = $request['tag']['game_id'];

            if($gamedata = '2106') {
                $gamedata = 'vs_dogs6';
            }         
            elseif($gamedata = '2116') {
                $gamedata = 'vs_horses6';
            }
            elseif($gamedata = '2120') {
                $gamedata = 'vs_dogs3d';
            }
            elseif($gamedata = '2121') {
                $gamedata = 'vs_tron3d';
            }
            elseif($gamedata = '2122') {
                $gamedata = 'vs_cycling3d';
            }
            elseif($gamedata = '2123') {
                $gamedata = 'vs_motorcycle';
            }
            elseif($gamedata = '40011') {
                $gamedata = 'vs_football';
            }      
            elseif($gamedata = '10131') {
                $gamedata = 'vs_fortune';
            }     
            elseif($gamedata = '10241') {
                $gamedata = 'vs_penalty';
            }  
            elseif($gamedata = '12041') {
                $gamedata = 'vs_roulette';
            }  
            elseif($gamedata = '10091') {
                $gamedata = 'vs_bingo37';
            }  
            elseif($gamedata = '999201') {
                $gamedata = 'vs_kenolive';
            }    

            $amount = $request['plus'];
            $bet = $request['minus'];
            $roundid = $request['tag']['round'] ?? '0';
            $virtualtxid = $request['trx_id'];
            $getoperator = $vsSessionsId->casino_id;

            $findoperator = \App\Models\Gameoptions::where('id', $getoperator)->first();
            $checkifsentalready = Gametransactions::where('txid', $virtualtxid)->first(); 
            
            if($request['tag']['round_completed'] === true) {
                $round_completed = 1;
            } else {
                $round_completed = 0;
            }
                if(!$checkifsentalready) {
                        $roundingamount = $amount * 100;
                        $roundingamount = (int)$roundingamount;
                        $roundingbet = $bet * 100;
                        $roundingbet = (int)$roundingbet;

                    $baseurl = $findoperator->callbackurl;
                    $prefix = $findoperator->slots_prefix;



                $OperatorTransactions = Gametransactions::create(['casinoid' => $findoperator->id, 'currency' => 'USD', 'player' => $playerId, 'ownedBy' => $findoperator->ownedBy, 'bet' => $roundingbet, 'win' => $roundingamount, 'gameid' => $gamedata, 'roundid' => $roundid, 'txid' => $virtualtxid, 'type' => 'virtualsports', 'rawdata' => '[]']);

                    $OperatorRaw = GametransactionsRaw::create(['casinoid' => $findoperator->id, 'player' => $playerId, 'ownedBy' => $findoperator->ownedBy, 'txid' => $virtualtxid, 'roundid' => $roundid, 'rawdata' => json_encode($request->all())]);



                if($round_completed === 1) {
                        $totalTxs = Gametransactions::where('roundid', '=', $roundid)->where('player', '=', $playerId)->get();
                        $totalWin = $totalTxs->sum('win');
                        $totalBet = $totalTxs->sum('bet');

                        $url = $baseurl.$prefix.'/bet?currency='.$currency.'&bet='.$roundingbet.'&totalWin='.$totalWin.'&totalBet='.$totalBet.'&win='.$roundingamount.'&playerid='.$playerId.'&gameid='.$gamedata.'&roundid='.$roundid.'&bonusmode=0&final='.$round_completed;
                    } else {
                        $url = $baseurl.$prefix.'/bet?currency='.$currency.'&bet='.$roundingbet.'&win='.$roundingamount.'&playerid='.$playerId.'&gameid='.$gamedata.'&roundid='.$roundid.'&bonusmode=0&final='.$round_completed;
                    }

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



                //roundingbet=bet, amount = win
                if($roundingamount > 0 || $roundingbet > 0) {
                $processGgr = Gametransactions::processGgr($gamedata, $getoperator, $roundingamount, $roundingbet);
                }

                return response()->json([
                'status' => 'OK',
                'balance' => $responsecurl['result']['balance'] / 100,
                'currency' => 'USD']);


              } 
        }

}