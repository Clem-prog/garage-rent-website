<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'garageType',
        'rentPrice',
        'electricPrice',
        'amount'
    ];

    public function rents()
    {
        return $this->hasMany(Rent::class);
    }
}
