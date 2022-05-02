<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use \App\Models\User;
use \App\Models\Gameoptions;
use \App\Models\BillingPerProvider;
use \App\Models\Providers;

class Gametransactions extends Model
{
    use HasFactory;

    public $timestamps = true;
    
    protected $table = 'gametransactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'casinoid', 'player', 'ownedBy', 'bet', 'win', 'currency', 'gameid', 'txid', 'created_at', 'type', 'rawdata', 'roundid', 'updated_at'
    ];

    public static function gameListGgr()
    {
       $result = Cache::remember('gamelist', 30, function () {
       return \App\Models\Gamelist::all();
       });
       return $result;
    }


    public static function processGgr($gameId, $operatorId, $deposit, $withdraw)
    {


            $getUser = Gameoptions::where('id', $operatorId)->first()->ownedBy;
            $findoperator = User::where('id', $getUser)->first();
            $getCurrency = Gameoptions::where('id', $operatorId)->first();

            $selectOperatorSettings = Gameoptions::where('id', $operatorId)->first();

            if($selectOperatorSettings->native_currency !== "USD") {
                $selectPrice = \App\Models\CurrencyPrices::where('currency', $selectOperatorSettings->native_currency)->first()->price;
                if($deposit > 0) {
                    $deposit = number_format(floatval($deposit / $selectPrice), 4, '.', '');
                }
                if($withdraw > 0) {
                    $withdraw = number_format(floatval($withdraw / $selectPrice), 4, '.', '');
                }
            }

            
            $selectGame = self::gameListGgr();
            $selectGame = $selectGame->where('game_slug', $gameId)->first();
            if(!$selectGame) {
                $selectGame = self::gameListGgr();
                $selectGame = $selectGame->where('id_hash', $gameId)->first()->provider;
            } else {
                $selectGame = $selectGame->provider;
            }
            $selectCycle = BillingPerProvider::where('provider', $selectGame)->where('user', $getUser)->first();
            if(!$selectCycle) {
                DB::table('billing_providers')->insert([
                    'provider' => $selectGame,
                    'user' => $getUser,
                    'revenueBet' => 0,
                    'revenueWin' => 0,
                    'profitCycle' => 0,
                    'profitDue' => 0
                ]);
            $selectCycle = BillingPerProvider::where('provider', $selectGame)->where('user', $getUser)->first();
            }

            $ggrCost = Providers::where('provider', $selectGame)->first()->ggr;

            if($findoperator->ggr_multiplier > 0) {
                $ggrCost = $ggrCost + $findoperator->ggr_multiplier;
            }

            if($findoperator->ggr_multiplier < 0) {
                $ggrCost = $ggrCost - $findoperator->ggr_multiplier;
            }

            $ggrProfit = Providers::where('provider', $selectGame)->first()->ggr_cost;
            $ggrProfit = $ggrCost - $ggrProfit;
            


            $profitCycle = $selectCycle->profitCycle;
            $revenueWin = floatval($deposit / 100);
            $revenueBet =  floatval($withdraw / 100);
            $newcycle = floatval($profitCycle - $revenueWin);
            $newcycle = floatval($newcycle + $revenueBet);
            $ggrDue = floatval(($newcycle / 100) * $ggrCost);
            $ggrOurProfit = floatval(($newcycle / 100) * $ggrProfit);


            if($ggrOurProfit < 0.001) {
                $ggrOurProfit = 0;
            }
            
            if($ggrDue < 0.001) {
                $ggrDue = 0;
            }


            $updateProfit = BillingPerProvider::where('provider', $selectGame)->where('user', $getUser)->update([
                'profitCycle' => $newcycle, 'profitDue' => $ggrDue, 'revenueBet' => $selectCycle->revenueBet + $revenueBet, 'revenueWin' => $selectCycle->revenueWin + $revenueWin, 'ourProfit' => $ggrOurProfit
            ]);

            //Log::notice('Game ID: '.$gameId.' - Provider: '.$selectGame.'('.$dueGgr.'%) - Due Cycle: '.$newDue.' - Profit Cycle '.$newcycle.' - Profit Today: '.$newToday.' End');

    }

}

