<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Livecasinocallbacks extends Model
{
    use HasFactory;
   
   public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'livecasino_callbacks';
     
    protected $fillable = [
        'id', 'game', 'url'
    ];

}
