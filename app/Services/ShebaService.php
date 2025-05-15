<?php

namespace App\Services;

use App\Models\ShebaRequest;

class ShebaService
{
    public function getAllRequests(): array
    {
        $requests = ShebaRequest::orderBy( 'created_at', 'asc' )->get();

        return $requests->map( function ( $request ) {
            return [
                'id'              => $request->id,
                'price'           => $request->price,
                'status'          => $request->status,
                'fromShebaNumber' => $request->from_sheba_number,
                'ToShebaNumber'   => $request->to_sheba_number,
                'createdAt'       => $request->created_at->toIso8601String()
            ];
        } )->toArray();
    }

}
