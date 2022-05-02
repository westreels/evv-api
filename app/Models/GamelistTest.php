<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GamelistTest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $table = 'gamelist_test';

    protected $fillable = [
        'game_id',
        'game_name',
        'game_desc',
        'game_provider',
        'api_ext',
        'type',
        'demo_mode',
        'index_rating',
        'image',
        'softswiss',
        'extra_id',
        'disabled'
    ];


}
 