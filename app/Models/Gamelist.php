<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Gamelist extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $table = 'gamelist';

    protected $fillable = [
        'game_id',
        'game_name',
        'game_desc',
        'game_provider',
        'api_ext',
        'type',
        'demo_mode',
        'index_rating',
        'game_slug',
        'image',
        'id_hash',
        'extra_id',
        'disabled'
    ];

    public static function cachedList() {
        $cachedList = Cache::get('cachedList');  

        if (!$cachedList) { 
            $cachedList = \App\Models\Gamelist::all();
            Cache::put('cachedList', $cachedList, Carbon::now()->addMinutes(400));
        } 

        return $cachedList;
    }


}
 