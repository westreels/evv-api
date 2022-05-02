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
use \App\Models\Livecasinocallbacks;

class HollywoodController extends Controller
{

    /**
     * @param $endpoint where callback URL & method is distributed
     * @return \Illuminate\Http\JsonResponse
     */
    public function endpoint(Request $request)
    {

        return [];
        $content = json_decode($request->getContent());

        if($content->gameId === 'TVBET_CasinoWars') {
            Log::info(json_encode($content));
        }
        //Log::warning(json_encode($content->gameId));
        //Disabled


        if($content->gameId === 'TVBET_CasinoWars') {

        //$livecasinocallbacks = \App\Models\Livecasinocallbacks::where('game', $content->gameId)->get();

        //foreach($livecasinocallbacks as $game) {
                $url = 'https://insane.bet/api/callback/warelements/';
                $jsonbody = json_encode($content);
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
        //}

        return [];


        } else {

        return [];
        }


            return response()->json([]);
        }
    }
