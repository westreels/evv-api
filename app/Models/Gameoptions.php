<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gameoptions extends Model
{
    use HasFactory;
   
   public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'gameoptions';
     
    protected $fillable = [
        'id', 'apikey', 'operator', 'operatorurl', 'operator_secret', 'livecasino_prefix', 'slots_prefix', 'virtualsports_enabled', 'virtualsports_prefix', 'evoplay_prefix', 'poker_prefix', 'bankgroup', 'bankgroupeur', 'bonusbankgroup', 'bonusgroupeur', 'callbackurl', 'sessiondomain', 'statichost', 'ggr', 'created_at', 'updated_at', 'newevoplay', 'livecasino_enabled', 'arcade_enabled', 'staging', 'native_currency', 'slug_type'
    ];

}
