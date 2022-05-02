<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GametransactionsRaw extends Model
{
    use HasFactory;

    public $timestamps = true;
    
    protected $table = 'gametransactions_raw';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'casinoid', 'player', 'ownedBy', 'txid', 'created_at',  'rawdata', 'roundid', 'updated_at'
    ];

}

