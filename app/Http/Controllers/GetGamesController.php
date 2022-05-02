<?php

namespace App\Http\Controllers;

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
use \App\Models\Gamelist;
use \App\Models\Providers;
use \App\Models\GamelistPublic;
use Carbon\Carbon;
use \App\Models\GamelistTest;
use \App\Models\ProvidersDisable;
use \App\Models\GamelistPublicInsaneBet;

use \App\Models\ProvidersInsane;

class GetGamesController extends Controller
{
        use Helpers;
            public function providers(Request $request)
            {
                $apikey = $request['apikey'];
                $ggr = $request['ggr'] ?? 0;
                $internal = $request['internal'] ?? 0;
                $extra = $request['extra'] ?? 0;
                $public = $request['public'] ?? 0;
                $colorcode = $request['colorcode'] ?? 0;

                if(!$apikey) {
                return Response(array('status' => 'error', 'error' => 'Auth error (no apikey specified)'))->setStatusCode(503);
                }
                
                if($extra !== 0) {
                    $Providers = Providers::orderBy('index_rating', 'DESC')->where('provider', '!=', 'rollback')->get();
                    $arrayList = array();
                    foreach ($Providers as $provider) {
                        $arrayList[] = array('provider' => $provider->provider, 'ggr' => $provider->ggr + $extra, 'games' => Gamelist::where('game_provider', '=', $provider->provider)->count(), 'img' => 'https://provider.cdn4.dk/gameproviders/'.$provider->provider.'.png?duotone=FFFFFF,b6b6b6&w=150');
                        }
                    return ($arrayList);
                }

                if($colorcode !== 0) {
                    $Providers = Providers::orderBy('index_rating', 'ASC')->where('provider', '!=', 'rollback')->get();
                    $arrayList = array();

                    foreach ($Providers as $provider) {
                    $gamescount = Gamelist::where('game_provider', '=', $provider->provider)->count();

                    if($gamescount === 0) {
                        $gamescount = rand(10, 30);
                    }

                        $arrayList[] = array('provider' => $provider->provider, 'slug' => $provider->provider, 'ggr' => $provider->ggr + $extra, 'type' => $provider->type, 'games' => $gamescount, 'img' => 'https://cdn2.davidkohen.com/v1/gameproviders/'.$provider->provider.'.png?duotone='.$colorcode.','.$colorcode.'&w=200', 'img_light' => 'https://cdn2.davidkohen.com/v1/gameproviders/'.$provider->provider.'.png?duotone=9fa3bd,9fa3bdd6&w=200');
                        }
                    return ($arrayList);
                }


                if($public === '1') {
                    $Providers = Providers::orderBy('index_rating', 'DESC')->where('provider', '!=', 'rollback')->get();
                    $arrayList = array();
                    foreach ($Providers as $provider) {
                        $arrayList[] = array('provider' => $provider->provider, 'games' => Gamelist::where('game_provider', '=', $provider->provider)->count(), 'img' => 'https://provider.cdn4.dk/gameproviders/'.$provider->provider.'.png?duotone=FFFFFF,b6b6b6&w=150');
                        }
                    return ($arrayList);
                }


                if($internal === '1') {
                    $Providers = Providers::orderBy('index_rating', 'DESC')->where('provider', '!=', 'rollback')->get();
                    $arrayList = array();
                    foreach ($Providers as $provider) {
                        $arrayList[] = array('provider' => $provider->provider, 'ggr_price_base' => $provider->ggr, 'ggr_price_cost' => $provider->ggr_cost, 'ggr_markup' => ($provider->ggr - $provider->ggr_cost), 'games' => Gamelist::where('game_provider', '=', $provider->provider)->count());
                        }
                    return ($arrayList);
                }

                $findoperator = DB::table('gameoptions')
                ->where('apikey', '=', $apikey )
                ->first();

                if(!$findoperator) {
                return Response(array('status' => 'error', 'error' => 'Auth error (1)'))->setStatusCode(503);
                }


                    $Providers = Providers::orderBy('index_rating', 'DESC')->where('provider', '!=', 'rollback')->get();
                    $arrayList = array();
                    foreach ($Providers as $provider) {
                        $arrayList[] = array('provider' => $provider->provider, 'img' => 'https://provider.cdn4.dk/gameproviders/'.$provider->provider.'.png?duotone=FFFFFF,b6b6b6&w=150', 'ggr' => $provider->ggr, 'games' => Gamelist::where('game_provider', '=', $provider->provider)->count());
                        }
                    return ($arrayList);



            }

            public function getRecentCount(Request $request)
            {

                    $countToday = \App\Models\Gametransactions::whereDate('created_at', '>', Carbon::now()->subDay())->count();
                    $totalBet = \App\Models\Gametransactions::whereDate('created_at', '>', Carbon::now()->subDay())->sum('bet');
                    $totalWin = \App\Models\Gametransactions::whereDate('created_at', '>', Carbon::now()->subDay())->sum('win');

                    $countWeek = \App\Models\Gametransactions::whereDate('created_at', '>', Carbon::now()->subDays(7))->count();
                    $totalBetWeek = \App\Models\Gametransactions::whereDate('created_at', '>', Carbon::now()->subDays(7))->sum('bet');
                    $totalWinWeek = \App\Models\Gametransactions::whereDate('created_at', '>', Carbon::now()->subDays(7))->sum('win');
                    
                    return array('todayPlayed' => $countToday, 'betTotalToday' => round($totalBet / 100, 2).'$', 'winTotalToday' => round($totalWin / 100, 2).'$', 'weekPlayed' => $countWeek, 'betTotalWeek' => round($totalBetWeek / 100, 2).'$', 'winTotalWeek' => round($totalWinWeek / 100, 2).'$');
            }



            public function providerPerCsv(Request $request)
            { 

                    $extra = $request['extra'] ?? 0;
                    $fileName = 'providerList-ggr.csv';
                    header('Content-Type: application/excel');
                    header('Content-Disposition: attachment; filename="' . $fileName . '"');
                    $Providers = Providers::where('provider', '!=', 'rollback')->get();
                    $arrayList = array();
                    $arrayList[] = array('provider' => 'Provider', 'ggr' => 'GGR(%)', 'games' => 'Games (Amount)', 'img' => 'Image');
                        foreach ($Providers as $provider) {
                            $arrayList[] = array('provider' => $provider->provider, 'ggr' => $provider->ggr + $extra, 'games' => Gamelist::where('game_provider', '=', $provider->provider)->count(), 'img' => 'https://provider.cdn4.dk/gameproviders/'.$provider->provider.'.png?duotone=FFFFFF,b6b6b6&w=150');
                    }
                    $fp = fopen('php://output', 'w');

                    foreach ($arrayList as $row) {
                        fputcsv($fp, $row);
                    }
                    fclose($fp);
            }

            public function gamesCSV(Request $request)
            { 

                    $provider = $request['provider'] ?? 'all';
                    $fileName = 'gamesList.csv';
                    header('Content-Type: application/excel');
                    header('Content-Disposition: attachment; filename="' . $fileName . '"');

                    if($provider === 'all') {
                    $Games = Gamelist::all(); 
                    }   else {
                    $Games = Gamelist::where('game_provider', '=', $provider)->get(); 
                    }
                    $arrayList = array();
                    $arrayList[] = array('id' => 'ID', 'gamename' => 'Name', 'gameid' => 'Game ID', 'gamedesc' => 'Desc', 'gameprovider' => 'Provider', 'category' => 'Category', 'disabled' => 'Disabled', 'availableOnStaging' => 'Staging Access', 'image_id' => 'Image Prefix', 'default_image' => 'Image Link');

                    foreach ($Games as $game) {
                        if($game->api_ext === 'c2') {
                            $stagingMode = true;
                        } else {
                            $stagingMode = false;
                        }

                        $arrayList[] = array('id' => $game->id, 'gamename' => $game->game_name, 'gameid' => $game->game_id, 'gamedesc' => $game->game_desc, 'gameprovider' => $game->game_provider, 'category' => $game->type, 'disabled' => $game->disabled, 'availableOnStaging' => $stagingMode, 'image_id' => $game->image, 'default_image' => 'https://games.cdn4.dk/games'.$game->image.'?auto=format&crop=fit&w=300&height=200&sharp=10&usm=10&q=98');
                    }

                    $fp = fopen('php://output', 'w');

                    foreach ($arrayList as $row) {
                        fputcsv($fp, $row);
                    }
                    fclose($fp);
            }


            public function gameList(Request $request)
            {
                $apikey = $request['apikey'];
                $provider = $request['provider'] ?? 'all';
                $image = $request['image'] ?? '1';
                $apiext = $request['api_ext'] ?? '0';
                $framework = $request['framework'] ?? '0';
                $sorted = $request['sorted'] ?? '0';
                $fudgefactor = $request['fudge'] ?? '15';
                $legacy = $request['legacy'] ?? '0';
                $softswiss = $request['softswiss'] ?? '0';

                if(!$apikey) {
                return Response(array('status' => 'error', 'error' => 'Auth error (no apikey specified)'))->setStatusCode(503);
                }

                $findoperator = DB::table('gameoptions')
                ->where('apikey', '=', $apikey )
                ->where('active', '=', 1)
                ->first();

                if(!$findoperator) {
                return Response(array('status' => 'error', 'error' => 'Auth error (1)'))->setStatusCode(503);
                }

                if($apiext != '0') {
                    $Games = Gamelist::where('api_ext', '=', $apiext)->get(); 
                    return $Games;
                }


                if($framework !== '1') {
              
                    $Games = GamelistPublic::orderBy('index_rating', 'DESC')->where('game_id', '!=', 'DK_LuckyRxc')->get(); 
                   $arrayList = array();


                    foreach ($Games as $game) {
                        if($game->game_id !== null) {
                        $arrayList[] = array('id' => $game->game_slug, 'softswiss' => $game->game_id, 'id_alt' => $game->id_hash, 'name' => $game->name, 'desc' => $game->desc, 'provider' => $game->provider, 'demo' => '0', 'd' => '0', 'rtp' => $game->rtp, 'category' => $game->type, 'image_wide' => 'https://img.evogames.eu/evo/image_wide/'.$game->game_id.'.png', 'image_square' => 'https://img.evogames.eu/evo/image_square/'.$game->game_id.'.png', 'image_long' => 'https://img.evogames.eu/evo/image_long/'.$game->game_id.'.png');
                    }
                }

                    return $arrayList;
                }


                if($framework === '1') {
              
                    $Games = GamelistPublic::orderBy('index_rating', 'DESC')->where('game_id', '!=', 'DK_LuckyRxc')->get(); 
                   $arrayList = array();


                    foreach ($Games as $game) {
                        if($game->game_id !== null) {
                        $arrayList[] = array('id' => $game->game_id, 'id_hash' => $game->id_hash, 'name' => $game->name, 'desc' => $game->desc, 'provider' => $game->provider, 'demo' => '0', 'd' => '0', 'rtp' => $game->rtp, 'category' => $game->type, 'image' => $game->image, 'image_sq' => $game->image_sq);
                    }
                }

                    return $arrayList;
                }


















                if($legacy === '1' && $provider != 'all' && $image === '0') {
                    $Games = Gamelist::where('game_provider', '=', $provider)->get(); 
                    $arrayList = array();
                    foreach ($Games as $game) {

                        if($game->api_ext === 'blueocean' || $game->game_provider === 'pragmatic') {
                        $arrayList[] = array('id' => $game->id, 'gameid' => $game->game_id, 'gamename' => $game->game_name, 'gamedesc' => $game->game_desc, 'gameprovider' => $game->game_provider, 'disabled' => $game->disabled, 'type' => $game->type, 'image_id' => $game->image, 'image_formats' => '2', 'default_image' => 'https://games.cdn4.dk/games/bu/'.$game->image, 'image_2' => 'https://games.cdn4.dk/games/bu/'.$game->image);

                        } else {

                        $arrayList[] = array('id' => $game->id, 'gameid' => $game->game_id, 'gamename' => $game->game_name, 'gamedesc' => $game->game_desc, 'gameprovider' => $game->game_provider, 'disabled' => $game->disabled, 'type' => $game->type, 'image_id' => $game->game_id.'.png', 'image_formats' => '1,2,3,4', 'default_image' =>  'https://games.cdn4.dk/games/'.$game->game_provider.'/2/'.$game->game_id.'.png', 'image_1' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/1/'.$game->game_id.'.png',  'image_2' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/2/'.$game->game_id.'.png', 'image_3' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/3/'.$game->game_id.'.png', 'image_4' => 'https://cdn2.bulk.bet/i/long/jpg/'.$game->game_id.'.jpg');
                        }
                    }
                    return ($arrayList);

                    }


                 if($legacy === '1' && $provider != 'all' && $image === '0') {
                    $Games = Gamelist::where('game_provider', '=', $provider)->get(); 
                    $arrayList = array();
                    foreach ($Games as $game) {

                        if($game->api_ext === 'blueocean' || $game->game_provider === 'pragmatic') {
                        $arrayList[] = array('id' => $game->id, 'gameid' => $game->game_id, 'gamename' => $game->game_name, 'gamedesc' => $game->game_desc, 'gameprovider' => $game->game_provider, 'disabled' => $game->disabled, 'type' => $game->type, 'image_id' => $game->image, 'image_formats' => '2', 'default_image' => 'https://games.cdn4.dk/games/bu/'.$game->image, 'image_2' => 'https://games.cdn4.dk/games/bu/'.$game->image);

                        } else {

                        $arrayList[] = array('id' => $game->id, 'gameid' => $game->game_id, 'gamename' => $game->game_name, 'gamedesc' => $game->game_desc, 'gameprovider' => $game->game_provider, 'disabled' => $game->disabled, 'type' => $game->type, 'image_id' => $game->game_id.'.png', 'image_formats' => '1,2,3,4', 'default_image' =>  'https://games.cdn4.dk/games/'.$game->game_provider.'/2/'.$game->game_id.'.png', 'image_1' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/1/'.$game->game_id.'.png',  'image_2' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/2/'.$game->game_id.'.png', 'image_3' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/3/'.$game->game_id.'.png', 'image_4' => 'https://cdn2.bulk.bet/i/long/jpg/'.$game->game_id.'.jpg');
                        }
                    }
                    return ($arrayList);

                    }

                if($legacy === '1' && $findoperator->virtualsports_enabled === '0' ) {
                    $Games = Gamelist::where('game_provider', '!=', 'virtualsports')->get(); 
                    return $Games;
                }

                if($legacy === '1' && $provider === 'all' && $image === '0') {
                    $Games = Gamelist::all(); 
                    return $Games;
                }

                if($legacy === '1' && $provider != 'all' && $image === '0') {
                    $Games = Gamelist::where('game_provider', '=', $provider)->get(); 
                    $arrayList = array();
                    foreach ($Games as $game) {

                        if($game->api_ext === 'blueocean' || $game->game_provider === 'pragmatic') {
                        $arrayList[] = array('id' => $game->id, 'gameid' => $game->game_id, 'gamename' => $game->game_name, 'gamedesc' => $game->game_desc, 'gameprovider' => $game->game_provider, 'disabled' => $game->disabled, 'type' => $game->type, 'image_id' => $game->image, 'image_formats' => '2', 'default_image' => 'https://games.cdn4.dk/games/bu/'.$game->image, 'image_2' => 'https://games.cdn4.dk/games/bu/'.$game->image);

                        } else {

                        $arrayList[] = array('id' => $game->id, 'gameid' => $game->game_id, 'gamename' => $game->game_name, 'gamedesc' => $game->game_desc, 'gameprovider' => $game->game_provider, 'disabled' => $game->disabled, 'type' => $game->type, 'image_id' => $game->game_id.'.png', 'image_formats' => '1,2,3,4', 'default_image' =>  'https://games.cdn4.dk/games/'.$game->game_provider.'/2/'.$game->game_id.'.png', 'image_1' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/1/'.$game->game_id.'.png',  'image_2' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/2/'.$game->game_id.'.png', 'image_3' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/3/'.$game->game_id.'.png', 'image_4' => 'https://cdn2.bulk.bet/i/long/jpg/'.$game->game_id.'.jpg');
                        }
                    }
                    return ($arrayList);

                    }


                if($framework === '1' && $sorted === '1') {
                    $Games = Gamelist::all(); 
                    $arrayList = array();
                    foreach ($Games as $game) {
                        $providerRating = Providers::where('provider', '=', $game->game_provider)->first()->index_rating;
                        if($game->type === 'live-table' && $game->index_rating === '1') {
                            if($game->game_provider === 'evolution') {
                                $indexRating = $game->index_rating + $providerRating - '10' - '45';
                            } else

                             {
                                $indexRating = $game->index_rating + $providerRating - '10' - '30';
                            }
                        } else {
                            $randomFudge = rand('1', $fudgefactor);
                            $indexRating = $game->index_rating + $providerRating + $randomFudge - '30';

                        }

                        if($game->api_ext === 'blueocean' || $game->game_provider === 'pragmatic') {
                        $arrayList[] = array('id' => $game->game_id, 'name' => $game->game_name, 'desc' => $game->game_desc, 'provider' => $game->game_provider, 'order' => $indexRating, 'd' => $game->disabled, 'category' => $game->type, 'image' => '/bu/'.$game->image);

                        } else {

                        $arrayList[] = array('id' => $game->game_id, 'name' => $game->game_name, 'desc' => $game->game_desc, 'provider' => $game->game_provider, 'order' => $indexRating,  'd' => $game->disabled, 'category' => $game->type, 'image' => '/'.$game->game_provider.'/2/'.$game->game_id.'.png');
                        }
                    }
                    
                    $array = collect($arrayList)->sortBy('order')->reverse()->toArray();


                    return $array;
                }



                if($provider === 'all' && $image === '1' ) {
                    $Games = Gamelist::all();
                    $arrayList = array();
                    foreach ($Games as $game) {

                        if($game->api_ext === 'blueocean') {
                        $arrayList[] = array('id' => $game->id, 'gameid' => $game->game_id, 'gamename' => $game->game_name, 'gamedesc' => $game->game_desc, 'gameprovider' => $game->game_provider, 'disabled' => $game->disabled, 'type' => $game->type, 'image_formats' => '2', 'default_image' => 'https://games.cdn4.dk/games/bu/'.$game->image, 'image_2' => 'https://games.cdn4.dk/games/bu/'.$game->image);

                        } else {

                        $arrayList[] = array('id' => $game->id, 'gameid' => $game->game_id, 'gamename' => $game->game_name, 'gamedesc' => $game->game_desc, 'gameprovider' => $game->game_provider, 'disabled' => $game->disabled, 'type' => $game->type, 'image_formats' => '1,2,3,4', 'default_image' =>  'https://games.cdn4.dk/games/'.$game->game_provider.'/2/'.$game->game_id.'.png', 'image_1' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/1/'.$game->game_id.'.png',  'image_2' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/2/'.$game->game_id.'.png', 'image_3' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/3/'.$game->game_id.'.png', 'image_4' => 'https://cdn2.bulk.bet/i/long/jpg/'.$game->game_id.'.jpg');
                        }
                    }
                    return ($arrayList);
                }

                if($provider !== 'all' && $image === '1') {
                    $Games = Gamelist::where('game_provider', '=', $provider)->get(); 
                    $arrayList = array();
                    foreach ($Games as $game) {

                        if($game->api_ext === 'blueocean') {
                        $arrayList[] = array('id' => $game->id, 'gameid' => $game->game_id, 'gamename' => $game->game_name, 'gamedesc' => $game->game_desc, 'gameprovider' => $game->game_provider, 'disabled' => $game->disabled, 'type' => $game->type, 'image_formats' => '2', 'default_image' => 'https://games.cdn4.dk/games/bu/'.$game->image, 'image_2' => 'https://games.cdn4.dk/games/bu/'.$game->image);

                        } else {

                        $arrayList[] = array('id' => $game->id, 'gameid' => $game->game_id, 'gamename' => $game->game_name, 'gamedesc' => $game->game_desc, 'gameprovider' => $game->game_provider, 'disabled' => $game->disabled, 'type' => $game->type, 'image_formats' => '1,2,3,4', 'default_image' =>  'https://games.cdn4.dk/games/'.$game->game_provider.'/2/'.$game->game_id.'.png', 'image_1' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/1/'.$game->game_id.'.png',  'image_2' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/2/'.$game->game_id.'.png', 'image_3' => 'https://games.cdn4.dk/games/'.$game->game_provider.'/3/'.$game->game_id.'.png', 'image_4' => 'https://cdn2.bulk.bet/i/long/jpg/'.$game->game_id.'.jpg');
                        }
                    }
                    return ($arrayList);
                }
                }
            }
