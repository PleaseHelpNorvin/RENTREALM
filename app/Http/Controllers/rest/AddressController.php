<?php

namespace App\Http\Controllers\rest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address;

class AddressController extends Controller
{
    //
    public function index() 
    {
        $address = Address::all();

        if ($address->isEmpty()) {
            return $this->notFoundResponse('No Address Found');
        }

        return $this->successResponse(['address' => $address], 'Addresses Fetched Successfully');
    }

}
