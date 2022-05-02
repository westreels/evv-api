<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProvidersInsane extends Model
{
    use HasFactory;
   
   public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'providers_insane';
     
    protected $fillable = [
        'id', 'provider', 'ggr', 'index_rating', 'ggr_cost', 'softswiss_id'
    ];

}
