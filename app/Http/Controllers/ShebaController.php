<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\ShebaStoreRequest;
use App\Http\Requests\ShebaUpdateRequest;
use App\Services\ShebaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShebaController extends Controller
{
    protected ShebaService $shebaService;

    public function __construct( ShebaService $shebaService )
    {
        $this->shebaService = $shebaService;
    }

    public function index(): JsonResponse
    {
        try {
            $requests = $this->shebaService->getAllRequests();

            return response()->json( [
                'requests' => $requests
            ] );
        } catch ( \Exception $e ) {
            Log::error( 'Error retrieving Sheba requests: ' . $e->getMessage(), [
                'exception' => $e
            ] );

            return response()->json( [
                'message' => 'An error occurred while retrieving Sheba requests',
                'code'    => 'SERVER_ERROR'
            ], 500 );
        }
    }

    public function store( ShebaStoreRequest $request ): JsonResponse
    {
        try {
            $idempotencyKey = $request->header( 'X-Idempotency-Key', Str::uuid()->toString() );

            $shebaRequest = $this->shebaService->createRequest(
                $request->price,
                $request->fromShebaNumber,
                $request->ToShebaNumber,
                $request->note,
                $idempotencyKey
            );

            return response()->json( [
                'message' => 'Request is saved successfully and is in pending status',
                'request' => $shebaRequest
            ], 201 );
        } catch ( InsufficientBalanceException $e ) {
            Log::warning( 'Insufficient balance for transfer', [
                'from'   => $request->fromShebaNumber,
                'amount' => $request->price
            ] );

            return response()->json( [
                'message' => $e->getMessage(),
                'code'    => 'INSUFFICIENT_BALANCE'
            ], 422 );
        } catch ( InvalidRequestException $e ) {
            Log::warning( 'Invalid transfer request: ' . $e->getMessage(), [
                'from' => $request->fromShebaNumber,
                'to'   => $request->ToShebaNumber
            ] );

            return response()->json( [
                'message' => $e->getMessage(),
                'code'    => 'INVALID_REQUEST'
            ], 400 );
        } catch ( \Exception $e ) {
            Log::error( 'Error processing transfer request: ' . $e->getMessage(), [
                'exception' => $e,
                'from'      => $request->fromShebaNumber,
                'to'        => $request->ToShebaNumber
            ] );

            return response()->json( [
                'message' => 'AAn error occurred while processing your request',
                'code'    => 'SERVER_ERROR'
            ], 500 );
        }
    }

    public function update( ShebaUpdateRequest $request, string $id ): JsonResponse
    {
        try {
            $shebaRequest = $this->shebaService->updateRequestStatus(
                $id,
                $request->status,
                $request->note ?? null
            );

            $message = $request->status === 'confirmed'
                ? 'Request is Confirmed!'
                : 'Request is Canceled!';

            return response()->json( [
                'message' => $message,
                'request' => $shebaRequest
            ] );
        } catch ( InvalidRequestException $e ) {
            return response()->json( [
                'message' => $e->getMessage(),
                'code'    => 'INVALID_REQUEST'
            ], 400 );
        } catch ( \Exception $e ) {
            return response()->json( [
                'message' => 'Ann error occurred while processing your request',
                'code'    => 'SERVER_ERROR'
            ], 500 );
        }
    }
}
