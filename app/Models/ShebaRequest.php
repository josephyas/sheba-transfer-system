<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShebaRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'price',
        'from_sheba_number',
        'to_sheba_number',
        'note',
        'status',
        'confirmed_at',
        'canceled_at',
        'cancellation_note'
    ];

    protected $casts = [
        'price'        => 'integer',
        'confirmed_at' => 'datetime',
        'canceled_at'  => 'datetime',
    ];


    public function transactions()
    {
        return $this->hasMany( Transaction::class, 'sheba_request_id' );
    }
}
