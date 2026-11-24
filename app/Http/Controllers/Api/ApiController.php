<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            
            'status' => 'ok',
        ]);
    }
}
