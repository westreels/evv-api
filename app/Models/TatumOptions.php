<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TatumOptions extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    protected $table = 'tatumoptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'ownedBy', 'apikey', 'accountid', 'currency', 'mmenomic', 'created_at', 'updated_at', 'active'
    ];


}