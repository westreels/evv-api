<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TatumTransactions extends Model
{
    use HasFactory;

    public $timestamps = true;
    
    protected $table = 'tatumtransactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'address', 'type', 'currency', 'amount', 'txid', 'callback', 'updated_at', 'created_at', 'data', 'ownedBy'
    ];


}