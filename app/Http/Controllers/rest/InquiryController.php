<?php

namespace App\Http\Controllers\rest;

use App\Models\Inquiry;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InquiryController extends Controller
{
    //
    public function index() {
        $inquiries = Inquiry::all();
        if($inquiries->isEmpty()) {
            return $this->notFoundResponse(null, "No Inquiries Found");
        }

        return $this->successResponse(['inquiries' => $inquiries], 'Inquries fetched successfully', 200);
    }

    public function store(Request $request) 
    {
        \Log::info($request->all());
        
        $Validated = $request->validate([
            'profile_id' => 'required|exists:user_profiles,id',
            'room_id' => 'required|exists:rooms,id',
            'has_pets' => 'boolean',
            'wifi_enabled' => 'boolean',
            'has_laundry_access' => 'boolean',
            'has_private_fridge' => 'boolean',
            'has_tv' => 'boolean',
        ]);


        $inquiry = Inquiry::create($Validated);
        return $this->createdResponse(['inquiry' => [$inquiry] ], 'Your Inquiry has been reviewed now by the admins or landlord');
    }

    public function show($id)
    {
        $inquiry = Inquiry::find($id);

        if(!$inquiry) {
            return $this->notFoundResponse(null, "Inquiry $id is notfound");
        }

        return $this->successResponse(['inquiry' => [$inquiry]], "Inqury $id Found");
    }

    public function update(Request $request, $id)
    {
        $inquiry = Inquiry::find($id);
    
        if (!$inquiry) {
            return $this->notFoundResponse(null, "Inquiry $id not found");
        }
    
        $validated = $request->validate([
            'status' => 'required|in:pending,accepted,rejected',
        ]);
    
        $inquiry->update($validated);
    
        return $this->successResponse(['inquiry' => $inquiry], "Inquiry $id updated successfully");
    }

}
