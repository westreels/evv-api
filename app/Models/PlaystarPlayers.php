<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaystarPlayers extends Model
{
    use HasFactory;
   
   public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'playstar_players';
     
    protected $fillable = [
        'id', 'playerid', 'casino_id', 'sessionid', 'nick'
    ];

}
