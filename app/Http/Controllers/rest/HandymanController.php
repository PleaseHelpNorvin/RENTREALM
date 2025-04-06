<?php

namespace App\Http\Controllers\rest;

use App\Models\Handyman;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HandymanController extends Controller
{
    //
    public function index()
    {
        $handymans = Handyman::with('user')->get();
    
        if ($handymans->isEmpty()) {
            return $this->notFoundResponse([], 'No handyman found');
        }
    
        return $this->successResponse(['handymans' => $handymans], 'Handyman Fetched Successfully');
    }

    public function showHandymanByUserId($userId)
    {
        $handymans = Handyman::with('user')->where('user_id', $userId)->get();

        if ($handymans->isEmpty()) {
            return $this->notFoundResponse([], 'No handyman found');
        }
        return $this->successResponse(['handymans' => $handymans], 'Handyman Fetched Successfully');

    }

    public function getBusyHandymanList()
    {
        $handymans = Handyman::with('user')->where('status', 'busy')->get();

        if ($handymans->isEmpty()) {
            return $this->notFoundResponse([], 'No busy handymen found');
        }

        return $this->successResponse(['handymans' => $handymans], 'Busy handymen fetched successfully');
    }

    public function getTerminatedHandymanList()
    {
        $handymans = Handyman::with('user')->where('status', 'terminated')->get();

        if ($handymans->isEmpty()) {
            return $this->notFoundResponse([], 'No terminated handymen found');
        }

        return $this->successResponse(['handymans' => $handymans], 'Terminated handymen fetched successfully');
    }

    public function getAvailableHandymanList()
    {
        $handymans = Handyman::with('user')->where('status', 'available')->get();

        if ($handymans->isEmpty()) {
            return $this->notFoundResponse([], 'No available handymen found');
        }

        return $this->successResponse(['handymans' => $handymans], 'Available handymen fetched successfully');
    }

    public function show($handymanId)
    {
        $handyman = Handyman::with('user')->find($handymanId);

        if (!$handyman) {
            return $this->notFoundResponse([], 'Handyman not found');
        }

        return $this->successResponse(['handymans' => [$handyman]], 'Handyman fetched successfully');
    }

    
    
    public function changeStatusToBusy()
    {
        $handyman = Handyman::find($handymanId);

        if (!$handyman) {
            return $this->notFoundResponse([], 'Handyman not found');
        }

        $handyman->update(['status' => 'busy']);

        return $this->successResponse(['handyman' => $handyman], 'Handyman terminated successfully');
    }

    public function terminateHandyman($handymanId)
    {
        $handyman = Handyman::find($handymanId);

        if (!$handyman) {
            return $this->notFoundResponse([], 'Handyman not found');
        }

        $handyman->update(['status' => 'terminated']);

        return $this->successResponse(['handyman' => $handyman], 'Handyman terminated successfully');
    }
}
