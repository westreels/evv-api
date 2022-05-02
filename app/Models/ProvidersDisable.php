<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProvidersDisable extends Model
{
    use HasFactory;
   
   public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'providers_disable';
     
    protected $fillable = [
        'id', 'casino_id', 'ownedBy', 'provider', 'hidden', 'created_at', 'updated_at'
    ];

}
