<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'sheba_number',
        'balance',
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    public function transactions()
    {
        return $this->hasMany( Transaction::class );
    }
}
