<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EzugiSessions extends Model
{
    use HasFactory;
   
   public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'ezugi_sessions';
     
    protected $fillable = [
        'id', 'playerid', 'casino_id', 'sessionid'
    ];

}
