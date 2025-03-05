<?php

namespace App\Http\Controllers\rest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    //
    public function index() {
        $payments = Payments::get();
        return $this->successResponse(['payments' => $payments], "fetched payments");
    }

    public function store(Request $request) {
        $validated = $request->validate([

        ]);
        
        return $this->successResponse(['payments' => $payments], "fetched payments");
    }


}
