<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualSportsSessions extends Model
{
    use HasFactory;
   
   public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'virtualsports_sessions';
     
    protected $fillable = [
        'id', 'session_id', 'player_id', 'casino_id' 
    ];

}
