<?php

namespace App\Http\Controllers\PaymentControllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Specialtactics\L5Api\Http\Controllers\RestfulController as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use \App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use \App\Models\TatumOptions;
use \App\Models\TatumTransactions;
use \App\Models\TatumWallets;
use \App\Models\Providers;
use \App\Models\GamelistPublic;
use Carbon\Carbon;

class TatumController extends Controller
{

        use Helpers;


            public static function floattostr($val)
            {
                preg_match( "#^([\+\-]|)([0-9]*)(\.([0-9]*?)|)(0*)$#", trim($val), $o );
                return $o[1].sprintf('%d',$o[2]).($o[3]!='.'?$o[3]:'');
            }

            public function callback(Request $request)
            {
                Log::emergency($request);

                $currency = $request->currency;
                if($currency === 'BSC') {
                    $currency = 'BNB';
                    $amount = $request->amount;
                }
                if($currency === 'BTC' || $currency === 'SOLANA' || $currency === 'SOL') {
                    $amount = $request->amount;
                }

                if($currency === 'RXCG' || $currency === 'GAME1') {
                    $amount = self::floattostr($request->amount / 10000000000);
                }

                $tatumOptions = TatumOptions::where('accountid', $request->account_id)->first();
                $tatumWallet = TatumWallets::where('wallet', $request->to)->first();

                TatumWallets::where('wallet', $request->to)->update([
                    'balance' => $tatumWallet->balance + $amount,
                    'deposited' => $tatumWallet->deposited + $amount,
                ]);

                $tatumDoubleCheck = TatumTransactions::where('txid', $request->tx_id)->first();

                if(!$tatumDoubleCheck) {
                TatumTransactions::create([
                    'currency' => $currency,
                    'address' => $request->to,
                    'type' => 'deposit',
                    'amount' => $amount,
                    'ownedBy' => $tatumOptions->ownedBy,
                    'txid' => $request->tx_id,
                    'callback' => 0,
                    'data' => json_encode($request->all(), JSON_UNESCAPED_UNICODE),
                ]);

                $ch = curl_init($tatumOptions->callbackurl);
                $payload = json_encode(['payment_status' => 'finished', 'order_description' => $tatumWallet->hash, 'dkapi_address' => $request->to, 'dkapi_txid' => $request->tx_id, 'actually_paid' => $amount, 'currency' => $currency]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'Authorization: '.$tatumWallet->hash]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                Log::emergency('httpcode '.$httpcode);         
                curl_close($ch);

                if($httpcode === 200) {
                TatumTransactions::where('txid', $request->tx_id)->update([
                    'callback' => true,
                ]);
                }
                }

                return 'test';
            }

            public function createwallet($accountid, $hash)
            {

                $tatumOptions = TatumOptions::where('accountid', $accountid)->first();


                if($tatumOptions->chain === 'sol') {

                        $curl = curl_init();

                        curl_setopt_array($curl, [
                          CURLOPT_URL => "https://api-eu1.tatum.io/v3/solana/wallet",
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_ENCODING => "",
                          CURLOPT_MAXREDIRS => 10,
                          CURLOPT_TIMEOUT => 30,
                          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                          CURLOPT_CUSTOMREQUEST => "GET",
                          CURLOPT_HTTPHEADER => [
                            "x-api-key: aa897cde-702b-48a8-ac30-52a36704180c_100"
                          ],
                        ]);

                        $response = curl_exec($curl);
                        $err = curl_error($curl);

                        curl_close($curl);

                        if ($err) {
                          echo "cURL Error #:" . $err;
                        } else {
                          //echo $response;
                        }
                        $response = json_decode($response);

                        TatumWallets::create([
                            'hash' => $hash,
                            'currency' => 'SOLANA',
                            'wallet' => $response->address,
                            'balance' => 0,
                            'xpub' => $response->privateKey,
                            'ownedBy' => $tatumOptions->ownedBy,
                            'derivation' => 0,
                            'deposited' => 0,
                        ]);


                        $curl = curl_init();

                        curl_setopt_array($curl, [
                          CURLOPT_URL => "https://api-eu1.tatum.io/v3/offchain/account/".$tatumOptions->accountid."/address/".$response->address."?index=1",
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_ENCODING => "",
                          CURLOPT_MAXREDIRS => 10,
                          CURLOPT_TIMEOUT => 30,
                          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                          CURLOPT_CUSTOMREQUEST => "POST",
                          CURLOPT_HTTPHEADER => [
                        "x-api-key: aa897cde-702b-48a8-ac30-52a36704180c_100"
                          ],
                        ]);

                        $response2 = curl_exec($curl);
                        $err = curl_error($curl);

                        curl_close($curl);

                        if ($err) {
                          echo "cURL Error #:" . $err;
                        } else {
                          //echo $response2;
                        }

                        $response2 = json_decode($response2);

                        return $response2;


                } else {

                $url = 'https://api-eu1.tatum.io/v3/offchain/account/'.$tatumOptions->accountid.'/address?index='.$tatumOptions->indexid;
                Log::emergency($url);

                $curl = curl_init();

                curl_setopt_array($curl, [
                  CURLOPT_URL => "https://api-eu1.tatum.io/v3/offchain/account/".$accountid."/address?index=".$tatumOptions->indexid,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => "",
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 30,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => "POST",
                  CURLOPT_HTTPHEADER => [
                    "x-api-key: aa897cde-702b-48a8-ac30-52a36704180c_100"
                  ],
                ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);

                if ($err) {
                  echo "cURL Error #:" . $err;
                } else {

                $derivation = $tatumOptions->indexid + 1;
                TatumOptions::where('accountid', $accountid)->update([
                    'indexid' => $derivation,
                ]);

                Log::emergency($response);

                 $response = json_decode($response);


                TatumWallets::create([
                    'hash' => $hash,
                    'currency' => $response->currency,
                    'wallet' => $response->address,
                    'balance' => 0,
                    'xpub' => $response->xpub,
                    'ownedBy' => $tatumOptions->ownedBy,
                    'derivation' => $derivation,
                    'deposited' => 0,
                ]);

                }


                  return $response;
                }




            }

            }
