<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingPerProvider extends Model
{
    public $timestamps = true;

    use HasFactory;
    //protected $connection = 'mysql-api';

    protected $table = 'billing_providers';
     
    protected $fillable = [
        'id', 'provider', 'user', 'profitCycle', 'profitDue', 'revenueBet', 'revenueWin', 'ourProfit'
    ];

    
}
