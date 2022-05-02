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
use App\Http\Controllers\PokerHelper;
use App\Http\Controllers\PokerApi;

class PokerController extends Controller
{
    


    public static function session($playerId, $currency, $casinoid) {

    $login = $playerId.'-'.$currency.'-'.$casinoid;

    $api = new PokerApi(PokerHelper::getPokerAffID(), PokerHelper::getPokerApikey(), '217.182.195.96', 4000);
    $api->connect();
    $playerid = $api->getIdByLogin($login);


    if($playerid === '0') {
    $password =  'teDAASAASDDSA22st';
    $new = $api->createPlayer($login, $password, $login, $login, $login, '1','USA');
    $playerid = $api->getIdByLogin($login);
    }

    $getRunLink = $api->getRunLink($login); 
    $url = $getRunLink['uogetuserrunlink']['@attributes']['runlink'];
    $explode = explode('/', $url);
    $link = 'https://rxc.bulk.poker/alogin/'.$explode[4].'/';
    echo $link;
}

    // Callbacks //
    public function simpleXmlToArray($xmlObject, $out = array ())
    {
        foreach ($xmlObject as $index => $node ){
            if(count($node) === 0){
                $out[$node->getName()] = $node->__toString ();
            }else{
                $out[$node->getName()][] = $this->simpleXmlToArray($node);
            }
        }

        return $out;
    }
    public function endpoint(Request $request)
    {
        //Log::notice($request);
        $content = json_decode($request->getContent());
        //Log::notice($content);
        $xmlstr = <<<XML
        <balanceget id="1" Code="200" AmountPlay="123" AmountReal="345"
        AmountBonus="456" PlayerId="613a3f23efea" />
        XML;

        header('Content-type: text/xml; charset=utf-8');
        $xmlDoc = new \SimpleXMLElement($xmlstr);
        echo $xmlDoc->asXML();
    }


    }
