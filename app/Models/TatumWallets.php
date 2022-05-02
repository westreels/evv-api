<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TatumWallets extends Model
{
    use HasFactory;

    public $timestamps = true;
    
    protected $table = 'tatumwallets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'currency', 'wallet', 'ownedBy', 'xpub', 'hash', 'deposited', 'updated_at', 'derivation', 'deposited', 'balance'
    ];


}