<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BgamingPlayers extends Model
{
    use HasFactory;
   
   public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'bgaming_players';
     
    protected $fillable = [
        'id', 'playerid', 'casino_id'
    ];

}
