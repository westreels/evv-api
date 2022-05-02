<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CurrencyPrices extends Model
{
    use HasFactory;
   
   public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'currencyprices';
     
    protected $fillable = [
        'id', 'currency', 'price', 'updated_at', 'created_at'
    ];


    public static function cachedPrices() {
        $cachedPrices = Cache::get('cachedPrices');  

        if (!$cachedPrices) { 
            $cachedPrices = \App\Models\CurrencyPrices::all();
            Cache::put('cachedPrices', $cachedPrices, Carbon::now()->addMinutes(15));
        } 

        return $cachedPrices;
    }

}