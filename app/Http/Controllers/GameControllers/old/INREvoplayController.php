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
use Dingo\Api\Routing\Helpers;
use \App\Models\Gameoptions;
use \App\Models\Gametransactions;
use \App\Models\Gamelist;
use \App\Models\GametransactionsRaw;

class INREvoplayController extends Controller
{
    use Helpers;

    private $system_id = '1103';
    private $secret_key = '9174ce77d8dd4d55acdd982efa548675';
    private $version = '1';
    private $currency = 'INR';

    public function list()
    {
        $signature = $this->system_id.'*'.$this->version.'*'.$this->secret_key;
        $response = file_get_contents('http://api.production.games/Game/getList?project='.$this->system_id.'&version=1&signature='.md5($signature).'');
        return response($response)->header('Content-Type', 'application/json');
    }
    /**
     * @param $endpoint where callback URL & method is distributed
     * @return \Illuminate\Http\JsonResponse
     */
    public function endpoint(Request $request)
    {
        //Log::critical($request);
        if ($request->name === 'init') {
            return $this->balance($request);
        } elseif ($request->name === 'balance') {
            return $this->balance($request);
        } elseif ($request->name === 'bet') {
            return $this->bet($request);
        } elseif ($request->name === 'win') {
            return $this->bet($request);
        } else {
            return $this->balance($request);
        }
    }

    /**
     *     @param $create Slot Game
     */
    public function createSlots($playerId, $game_id, $casino_id, $mode)
    {
        $evoexplode = explode('-', $playerId);
        $operatorevo = $casino_id;
        $unique = uniqid();
        $getevouid = (Gamelist::where('game_id', $game_id)->first()->extra_id);
        $findoperator = Gameoptions::where('id', $casino_id)->first();
        if($mode === 'demo') {
           $token = 'demo';
        } else {
           $token = $unique . '-' . $playerId . '@' . $game_id .'@'. $casino_id;
        }

        $gameevo = $getevouid;
        $args = [ 
            $token, 
            $gameevo, 
            [ 
                $playerId, 
                $findoperator->operatorurl, //exit_url 
                $findoperator->operatorurl, //cash_url
                '1' //https
            ], 
            '1', //denomination
            'INR', //currency
            '1', //return_url_info
            '2' //callback_version
        ]; 

        $signature = self::getSignature($this->system_id, $this->version, $args, $this->secret_key);
        $response = json_decode(file_get_contents('http://api.production.games/Game/getURL?project='.$this->system_id.'&version=1&signature='.$signature.'&token='.$token.'&game='.$gameevo.'&settings[user_id]='.$playerId.'&settings[exit_url]='.$findoperator->operatorurl.'&settings[cash_url]='.$findoperator->operatorurl.'&settings[https]=1&denomination=1&currency=INR&return_url_info=1&callback_version=2'), true);
            //Log::notice(json_encode($response));

        $url = $response['data']['link'];
        header('Access-Control-Allow-Origin: *');
        header('Content-type: application/json');

        return array('url' => $url);
    }


    /**
     *     @param $create Free Spins Sessions
     */
    public function createFreeSlots($playerId, $game_id, $casino_id, $spins, $spinsvalue)
    {
        $evoexplode = explode('-', $playerId);
        $operatorevo = $casino_id;
        $unique = uniqid();
        $getevouid = (Gamelist::where('game_id', $game_id)->first()->extra_id);
        $findoperator = Gameoptions::where('id', $casino_id)->first();
        $token = $unique . '-' . $playerId . '@' . $game_id .'@'. $casino_id;
        $gameevo = $getevouid;
        $args = [ 
            $token, 
            $gameevo, 
            [ 
                $playerId, 
                $findoperator->operatorurl, //exit_url 
                $findoperator->operatorurl, //cash_url
                '1' //https
            ], 
            '1', //denomination
            'INR', //currency
            '1', //return_url_info
            '2' //callback_version
        ]; 
        $bonusargs = [ 
                    $token, 
                    $gameevo, 
                    [
                        $spins,
                        $spinsvalue,
                        $playerId, 
                        $findoperator->operatorurl, //exit_url 
                        $findoperator->operatorurl, //cash_url
                        '1' ////https
                    ], 
                    '1', //denomination
                    'INR', //currency
                    '1', //return_url_info
                    '2' //callback_version
                ]; 

        $signature = self::getSignature($this->system_id, $this->version, $bonusargs, $this->secret_key);

        $response = json_decode(file_get_contents('http://api.production.games/Game/getURL?project='.$this->system_id.'&version=1&signature='.$signature.'&token='.$token.'&game='.$gameevo.'&settings[extra_bonuses][bonus_spins][spins_count]='.$spins.'&settings[extra_bonuses][bonus_spins][bet_in_money]='.$spinsvalue.'&settings[user_id]='.$playerId.'&settings[exit_url]='.$findoperator->operatorurl.'&settings[cash_url]='.$findoperator->operatorurl.'&settings[https]=1&denomination=1&currency=INR&return_url_info=1&callback_version=2'), true);

        $url = $response['data']['link'];
        header('Access-Control-Allow-Origin: *');
        header('Content-type: application/json');
        return array('url' => $url);
    }
    public function createSuperFreeSlots($playerId, $game_id, $casino_id, $spins, $spinsvalue)
    {
        $evoexplode = explode('-', $playerId);
        $operatorevo = $casino_id;
        $unique = uniqid();
        $getevouid = (Gamelist::where('game_id', $game_id)->first()->extra_id);
        $findoperator = Gameoptions::where('id', $casino_id)->first();
        $token = $unique . '-' . $playerId . '@' . $game_id .'@'. $casino_id;
        $gameevo = $getevouid;
        $args = [ 
            $token, 
            $gameevo, 
            [ 
                $playerId, 
                $findoperator->operatorurl, //exit_url 
                $findoperator->operatorurl, //cash_url
                '1' //https
            ], 
            '1', //denomination
            'INR', //currency
            '1', //return_url_info
            '2' //callback_version
        ]; 
        $bonusargs = [ 
                        $token, 
                        $gameevo, 
                        [
                            $spins,
                            $spinsvalue,
                            $playerId, 
                            $findoperator->operatorurl, //exit_url 
                            $findoperator->operatorurl, //cash_url
                            '1' ////https
                        ], 
                        '1', //denomination
                        'INR', //currency
                        '1', //return_url_info
                        '2' //callback_version
                    ]; 

        $signature = self::getSignature($this->system_id, $this->version, $bonusargs, $this->secret_key);

        $response = json_decode(file_get_contents('http://api.production.games/Game/getURL?project='.$this->system_id.'&version=1&signature='.$signature.'&token='.$token.'&game='.$gameevo.'&settings[extra_bonuses][freespins_on_start][freespins_count]='.$spins.'&settings[extra_bonuses][freespins_on_start][bet_in_money]='.$spinsvalue.'&settings[user_id]='.$playerId.'&settings[exit_url]='.$findoperator->operatorurl.'&settings[cash_url]='.$findoperator->operatorurl.'&settings[https]=1&denomination=1&currency=INR&return_url_info=1&callback_version=2'), true);
        //Log::info($critical);
        header('Access-Control-Allow-Origin: *');
        header('Content-type: application/json');

        return array('url' => $url);
    }

    /**
     *     @param $get balance 
     */
    public function balance(Request $request)
    {
            $token = $request['token'];
            $currency = explode('-', $token);
            $currency = $currency[2];
            $currency = explode('@', $currency);
            $currency = $currency[0];
            $playerId = explode('-', $token);
            $playerId = $playerId[1];

            $getoperator = explode('@', $token);
            $getoperator = $getoperator[2];
            //Log::notice($getoperator);
            $findoperator = Gameoptions::where('id', $getoperator)->first();

            if($findoperator->active == '1') {
            $baseurl = $findoperator->callbackurl;
            $prefix = $findoperator->slots_prefix;

            $url = $baseurl.$prefix.'/balance?currency='.$currency.'&playerid='.$playerId;
            //Log::notice($url);
            $userdata = array('operator' => $getoperator, 'playerId' => $playerId, "currency" => $currency);
            $userdata = '';
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
                'status' => 'ok',
                'data' => ([
                    'balance' => round($responsecurl['result']['balance'] / 100, 2),
                    'currency' => 'INR'    
                ])
            ]);
    } else {
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
    
    public function bet(Request $request)
    {
        $token = $request['token'];
        $explode = explode('-', $token);
        $explode = $explode[2];
        $currency = explode('@', $explode);
        $currency = $currency[0];
        $playerId = explode('-', $token);
        $playerId = $playerId[1];
        $gamedata = explode('@', $token);
        $gamedata = $gamedata[1];
        $reqdata = $request['data'];

        if($request->name === 'win') {
        $bet = 0;
        $win = $reqdata['amount'];
        } else {
        $bet = $reqdata['amount'];
        $win = 0;
        }
        $roundingBet = $bet * 100;
        $roundingBet = (int)$roundingBet;
        $roundingWin = $win * 100;
        $roundingWin = (int)$roundingWin;

        $roundid = $reqdata['round_id'];
        $details = $reqdata['details'];


        $decodeddetails = json_decode($details);
        $finalaction = $reqdata['final_action'] ?? 0;
        $transactionid = $request['callback_id'] ?? 0;
        $bonusmode = $decodeddetails->game_mode_code ?? 0;
        $getoperator = explode('@', $token);
        $getoperator = $getoperator[2];
        $findoperator = Gameoptions::where('id', $getoperator)->first();

        $checkifExist = Gametransactions::where('txid', $transactionid)->where('player', $playerId)->first();

        if($checkifExist) {
                return response()->json([
                    'status' => 'error',
                    'error' => ([
                        'scope' => "user",
                        'no_refund' => "1",
                        'message' => "Not enough money"
                    ])
                ]);
        }

            $baseurl = $findoperator->callbackurl;
            $prefix = $findoperator->slots_prefix;

            $verifySign = md5($findoperator->apikey.'-'.$roundid.'-'.$findoperator->operator_secret);

                try {

                    $OperatorTransactions = Gametransactions::create(['casinoid' => $findoperator->id, 'currency' => 'INR', 'player' => $playerId, 'ownedBy' => $findoperator->ownedBy, 'bet' => $roundingBet, 'win' => $roundingWin, 'gameid' => $gamedata, 'txid' => $transactionid, 'roundid' => $roundid, 'type' => 'slots', 'rawdata' => '[]']);

                    $OperatorRaw = GametransactionsRaw::create(['casinoid' => $findoperator->id, 'player' => $playerId, 'ownedBy' => $findoperator->ownedBy, 'txid' => $transactionid, 'roundid' => $roundid, 'rawdata' => json_encode($request->all(), JSON_UNESCAPED_UNICODE)]);
                    if($roundingBet > 0 || $roundingWin > 0) {

                    $processGgr = Gametransactions::processGgr($gamedata, $findoperator->id, $roundingWin, $roundingBet);
                
                    }       

                } catch (\Exception $exception) {
                    //Error trying to create operator transaction
                }


            if($finalaction === '1') {
            $totalTxs = Gametransactions::where('roundid', '=', $roundid)->where('player', '=', $playerId)->get();
            $totalWin = $totalTxs->sum('win');
            $totalBet = $totalTxs->sum('bet');

            $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gamedata.'&roundid='.$roundid.'&playerid='.$playerId.'&bet='.$roundingBet.'&win='.$roundingWin.'&bonusmode='.$bonusmode.'&totalBet='.$totalBet.'&totalWin='.$totalWin.'&final='.$finalaction.'&sign='.$verifySign;

            } else {
                $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gamedata.'&roundid='.$roundid.'&playerid='.$playerId.'&bet='.$roundingBet.'&win='.$roundingWin.'&bonusmode='.$bonusmode.'&final='.$finalaction.'&sign='.$verifySign;
            }


            if($findoperator->extendedApi === '1') {
            $userdata = array('sign' => $verifySign, "currency" => $currency, "gameid" => $gamedata, "roundid" => $roundid, "playerid" => $playerId, "bet" => "0", "win" => $rounding, "bonusmode" => $bonusmode, "final" => $finalaction);
            } else {
            $userdata = array('sign' => $verifySign, "roundid" => $roundid);
            }

                //Log::notice($url);
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
              ), ));
        $responsecurl = curl_exec($curlcatalog);
        curl_close($curlcatalog);
        //Log::critical($responsecurl);
        $responsecurl = json_decode($responsecurl, true);
                try {
                if($responsecurl['result']['balance']) {
                        return response()->json([
                            'status' => 'ok',
                            'data' => ([
                                'balance' => round($responsecurl['result']['balance'] / 100, 2),
                                'currency' => 'INR'    
                            ])
                        ]);
                    } 

                } catch (\Exception $exception) {
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

    public function win(Request $request)
    {
        $token = $request['token'];
        $explode = explode('-', $token);
        $explode = $explode[2];
        $currency = explode('@', $explode);
        $currency = $currency[0];
        
        $playerId = explode('-', $token);
        $playerId = $playerId[1];
        $gamedata = explode('@', $token);
        $gamedata = $gamedata[1];
        $reqdata = $request['data'];
        $amount = $reqdata['amount'];
        $roundid = $reqdata['round_id'];
        $details = $reqdata['details'];
        $decodeddetails = json_decode($details);
        $finalaction = 0;
        if($decodeddetails->final_action) {
        $finalaction = $decodeddetails->final_action ?? 0;
        }
        $getoperator = explode('@', $token);
        $getoperator = $getoperator[2];
        $findoperator = Gameoptions::where('id', $getoperator)->first();
    
            $baseurl = $findoperator->callbackurl;
            $prefix = $findoperator->slots_prefix;
            $rounding = $amount * 100;
            $rounding = (int)$rounding;
             try{
            
            if($rounding > 0) {
                $OperatorTransactions = Gametransactions::create(['casinoid' => $findoperator->id, 'currency' => 'INR', 'player' => $playerId, 'ownedBy' => $findoperator->ownedBy, 'bet' => '0', 'win' => $rounding, 'gameid' => $gamedata, 'txid' => $roundid, 'type' => 'slots', 'rawdata' => json_encode(['data' => $request->getContent()])]);
                if($rounding > 0) {
                $processGgr = Gametransactions::processGgr($gamedata, $findoperator->id, $roundingBet, '0');
                }
            }

            } catch (\Exception $exception) {
                //Error trying to create operator transaction
            }

            $verifySign = md5($findoperator->apikey.'-'.$roundid.'-'.$findoperator->operator_secret);
            $url = $baseurl.$prefix.'/bet?currency='.$currency.'&gameid='.$gamedata.'&roundid='.$roundid.'&playerid='.$playerId.'&sign='.$verifySign.'&bet=0&win='.$rounding.'&bonusmode='.$decodeddetails->game_mode_code.'&final='.$finalaction.'&sign='.$verifySign;
            if($findoperator->extendedApi === '1') {
            $userdata = array('sign' => $verifySign, "currency" => $currency, "gameid" => $gamedata, "roundid" => $roundid, "playerid" => $playerId, "bet" => "0", "win" => $rounding, "bonusmode" => $decodeddetails->game_mode_code, "final" => $finalaction);
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
        if($responsecurl['status'] == 'ok') {
        return response()->json([
            'status' => 'ok',
            'data' => ([
                'balance' => round($responsecurl['result']['balance'] / 100, 2),
                'currency' => 'INR'    
            ])
        ]);  
    }

    }



            public function getSignature($system_id, $version, array $args, $secret_key)
            {
                $md5 = array();
                        $md5[] = $system_id;
                        $md5[] = $version;
                        foreach ($args as $required_arg) {
                                $arg = $required_arg;
                                if(is_array($arg)){
                                        if(count($arg)) {
                                                $recursive_arg = '';
                                                array_walk_recursive($arg, function($item) use (& $recursive_arg) { if(!is_array($item)) { $recursive_arg .= ($item . ':');} });
                                                $md5[] = substr($recursive_arg, 0, strlen($recursive_arg)-1); // get rid of last colon-sign
                                        } else {
                                        $md5[] = '';
                                        }
                                } else {
                        $md5[] = $arg;
                        }
                };
                $md5[] = $secret_key;
                $md5_str = implode('*', $md5);
                $md5 = md5($md5_str);
                return $md5;
            }


}
