<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DKSessions extends Model
{
    use HasFactory;
   
   public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'dkgames_sessions';
     
    protected $fillable = [
        'id', 'player_id', 'casino_id', 'session_id', 'mode', 'demo_bal'
    ];

}
