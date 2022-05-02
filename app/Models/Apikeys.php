<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Actions\Actionable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Apikeys extends Model
{
    use HasFactory;

   public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'apikeys';
     
    protected $fillable = [
        'id', 'apikey', 'ownedBy', 'type', 'status', 'ip_block', 'allowed_ips', 'dueCycle', 'profitToday', 'profitThisWeek', 'profitThisMonth', 'profitCycle', 'profitTotal', 'created_at', 'updated_at'
    ];
}
