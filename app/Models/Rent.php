<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rent extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'garage_id',
        'rentType',
        'rentStatus',
        'dayAmount',
        'startDate',
        'endDate',
        'paymentProof',
        'totalCost',
    ];
 
    protected function casts(): array
    {
        return [
            'startDate' => 'date',
            'endDate' => 'date',
        ];
    }
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
 
    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }
}
