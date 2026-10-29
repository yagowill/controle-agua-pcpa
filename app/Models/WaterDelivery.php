<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'operation_type',
        'sector',
        'empty_bottles_count',
        'reception_date',
        'estimated_date',
        'received_from_person',
        'reception_attendant',
        'delivery_date',
        'delivered_to_person',
        'delivery_attendant',
        'status',
        'notes',
    ];

    protected $casts = [
        'reception_date' => 'datetime',
        'estimated_date' => 'datetime',
        'delivery_date' => 'datetime',
        'empty_bottles_count' => 'integer',
    ];
}
