<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'sheba_request_id',
        'type',
        'amount',
        'note',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function account()
    {
        return $this->belongsTo( Account::class );
    }

    public function shebaRequest()
    {
        return $this->belongsTo( ShebaRequest::class, 'sheba_request_id' );
    }
}
