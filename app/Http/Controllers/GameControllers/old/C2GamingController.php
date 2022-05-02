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

class C2GamingController extends Controller
{
    
    /** @var Client */
    protected $client;


    private $c2gaming_demo_bankgroup = 'dtc';
    private $mascotgaming_demo_bankgroup = 'dtc';
    private $c2gaming_demo_sessiondomain = '.casino-module.com';
    private $c2gaming_demo_statichost = 'static.casino-module.com';
    private $c2gaming_demo_startvalue = 10000;
    private $mascotgaming_demo_startvalue = 10000;
    private $davidkohen_demo_statichost = 'davidkohen.static.bet';
    private $davidkohen_demo_sessiondomain = '.gambleapi.com';
    private $davidkohen_demo_bankgroup = 'dkgames';


    /**
     * C27Controller constructor.
     * @throws \outcomebet\casino25\api\client\Exception
     */
    public function __construct()
    {
        $this->client = new Client(array(
            'url' => 'https://api.c27.games/v1/',
            'sslKeyPath' => env('c27_path'),
        ));
        $this->client->dkgames = new Client(array(
            'url' => 'https://api.c27.games/v1/',
            'sslKeyPath' => env('dkgames_path'),
        ));
        $this->client->mascot = new Client(array(
            'url' => 'https://api.mascot.games/v1/',
            'sslKeyPath' => env('mascot_path'),
        ));
    }


    /**
     * @param $endpoint where callback URL & method is distributed
     * @return \Illuminate\Http\JsonResponse
     */
    public function endpoint(Request $request)
    {
        $content = json_decode($request->getContent());
        //Log::notice(json_encode($content));
        //die;
        if ($content->method === 'getBalance') {
            return $this->balance($request);
        } elseif ($content->method === 'withdrawAndDeposit') {
            return $this->bet($request);
        } elseif ($content->method === 'rollbackTransaction') {
            return response()->json([
                'result' => (json_decode ("{}")),
                'id' => 0,
                'jsonrpc' => '2.0'
            ]);
        } else {
            return response()->json([
                'result' => (json_decode ("{}")),
                'id' => 0,
                'jsonrpc' => '2.0'
            ]);
        }
    }


    /**
     * @param $create DavidKohen C2 Gaming session
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function DavidKohenDemo($game_id)
    {           
         $game = $this->client->dkgames->createDemoSession(   
                [   
                    'GameId' => $game_id,
                    'StaticHost' => $this->davidkohen_demo_statichost,
                    'BankGroupId' => $this->davidkohen_demo_bankgroup,
                    'StartBalance' => $this->c2gaming_demo_startvalue
                ]   
            );  

            return array('url' => 'https://'.$game['SessionId'].$this->davidkohen_demo_sessiondomain) ?? 'error';
    }

    /**
     * @param $create DavidKohenRealmoney session
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function DavidKohenRealmoney($playerId, $game_id, $casino_id, $bankgroup, $statichost)
    {           
        $currency = explode('-', $playerId);
        $currency = $currency[1];
        $player = explode('-', $playerId);
        $player = $currency[0];
        $findoperator = \App\Models\Gameoptions::where('id', $casino_id)->first();
        $sessiondomain = $findoperator->sessiondomain;
        if($findoperator->native_currency !== "USD") {
            $bankgroup = $bankgroup.'-'.$findoperator->native_currency;
        }

                $this->client->dkgames->setPlayer(['Id' => $playerId, 'BankGroupId' => 'dkgames']);
                $game = $this->client->dkgames->createSession(
                [
                    'GameId' => $game_id,
                    'StaticHost' => 'davidkohen.static.bet',
                    'PlayerId' => $playerId,
                    'AlternativeId' => time() . '_'.$casino_id,
                    'RestorePolicy' => 'Last'
                ]
            );
        return array("url" => "https://".$game['SessionId'].$sessiondomain);
    }


    /**
     * @param $create C2 Gaming session
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function C2GamingDemo($game_id)
    {           
         $game = $this->client->createDemoSession(   
                [   
                    'GameId' => $game_id,
                    'StaticHost' => $this->c2gaming_demo_statichost,  
                    'BankGroupId' => $this->c2gaming_demo_bankgroup,  
                    'StartBalance' => $this->c2gaming_demo_startvalue
                ]   
            );  

            return array('url' => 'https://'.$game['SessionId'].$this->c2gaming_demo_sessiondomain) ?? 'error';
    }

    /**
     * @param $create Mascot Demo session
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function MascotDemo($game_id)
    {           
         $game = $this->client->mascot->createDemoSession(   
                [   
                    'GameId' => $game_id,
                    'BankGroupId' => $this->mascotgaming_demo_bankgroup,
                    'StartBalance' => $this->mascotgaming_demo_startvalue
                ]   
            );  
            return array('url' => $game['SessionUrl']) ?? 'error';
    }



    /**
     * @param $create C2 Gaming session
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function C2GamingRealmoney($playerId, $game_id, $casino_id, $bankgroup, $statichost)
    {           
        $currency = explode('-', $playerId);
        $currency = $currency[1];
        $player = explode('-', $playerId);
        $player = $currency[0];
        $findoperator = \App\Models\Gameoptions::where('id', $casino_id)->first();

        if($findoperator->native_currency !== "USD") {
            $bankgroup = $bankgroup.'-'.$findoperator->native_currency;
        }
        $sessiondomain = $findoperator->sessiondomain;

                $this->client->setPlayer(['Id' => $playerId, 'BankGroupId' => $bankgroup]);
                $game = $this->client->createSession(
                [
                    'GameId' => $game_id,
                    'StaticHost' => $statichost,
                    'PlayerId' => $playerId,
                    'AlternativeId' => time() . '_'.$casino_id,
                    'RestorePolicy' => 'Last'
                ]
            );
        return array("url" => "https://".$game['SessionId'].$sessiondomain);
    }

    /**
     * @param $create Mascot Gaming session
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function MascotRealmoney($playerId, $game_id, $casino_id, $bankgroup, $statichost)
    {           
        $currency = explode('-', $playerId);
        $currency = $currency[1];
        $player = explode('-', $playerId);
        $player = $currency[0];
        $findoperator = \App\Models\Gameoptions::where('id', $casino_id)->first();
        if($findoperator->native_currency !== "USD") {
            $bankgroup = $bankgroup.'-'.$findoperator->native_currency;
        }
        
            $this->client->mascot->setPlayer(['Id' => $playerId, 'BankGroupId' => $bankgroup]);
            $game = $this->client->mascot->createSession(
            [
                'GameId' => $game_id,
                'PlayerId' => $playerId,
                'AlternativeId' => time() . '_'.$casino_id,
                'RestorePolicy' => 'Last'
            ]
        );
        Log::notice($game);
                
        return array("url" => $game['SessionUrl']);
    }


   /**
     * @param $bet result processsing
     * @return \Illuminate\Http\JsonResponse
     */
    public function bet(Request $request)
    {
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
