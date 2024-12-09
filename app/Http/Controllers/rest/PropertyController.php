<?php

namespace App\Http\Controllers\rest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Property;


class PropertyController extends Controller
{
    // Show all properties
    public function index()
    {
        $properties = Property::all();

        if ($properties->isEmpty()) {
            return $this->notFoundResponse(null, 'No properties found.');
        }

        return $this->successResponse(['properties' => $properties], 'Properties Fetched Successfully', 201);
    }

    // Store a new property
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'zone' => 'nullable|string|max:255', // 'nullable' if zone is optional
            'street' => 'nullable|string|max:255',
            'postal_code' => 'required|string|max:20',
            'type' => 'required|in:apartment,house,boarding-house',
            'status' => 'required|in:available,rented,full',
        ]);

        // Create address from the provided fields
        $address =  $request->street .',' . $request->barangay . ',' . ($request->zone ? $request->zone . ', ' : '') . $request->city . ', ' . $request->state . ', ' . $request->postal_code;

        // Store the property with the constructed address
        $property = Property::create([
            'name' => $request->name,
            'address' => $address, // Store the full address
            'city' => $request->city,
            'state' => $request->state,
            'barangay' => $request->barangay,
            'zone' => $request->zone,
            'street' => $request->street,
            'postal_code' => $request->postal_code,
            'type' => $request->type,
            'status' => $request->status,
        ]);

        return $this->successResponse(['property' => $property], 'Property created successfully.', 201);
    }

    // Show a single property
    public function show($id)
    {
        $property = Property::find($id);

        if (!$property) {
            return $this->notFoundResponse(null, 'Property not found.');
        }

        return $this->successResponse($property);
    }

    // Update an existing property
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'zone' => 'nullable|string|max:255', // 'nullable' if zone is optional
            'street' => 'nullable|string|max:255',
            'postal_code' => 'required|string|max:20',
            'type' => 'required|in:apartment,house,boarding-house',
            'status' => 'required|in:available,rented,full',
        ]);
        
        $property = Property::find($id);
    
        if (!$property) {
            return $this->notFoundResponse(null, 'Property not found.');
        }
    
        // Construct the full address before updating
        $address =  $request->street . ',' . $request->barangay . ',' . ($request->zone ? $request->zone . ', ' : '') . $request->city . ', ' . $request->state . ', ' . $request->postal_code;
    
        // Update the property details, including the address
        $property->update([
            'name' => $request->name,
            'address' => $address, // Update the full address
            'city' => $request->city,
            'state' => $request->state,
            'barangay' => $request->barangay,
            'zone' => $request->zone,
            'street' => $request->street,
            'postal_code' => $request->postal_code,
            'type' => $request->type,
            'status' => $request->status,
        ]);
    
        return $this->successResponse($property, 'Property updated successfully.');
    }

    // Delete a property
    public function destroy($id)
    {
        $property = Property::find($id);

        if (!$property) {
            return $this->notFoundResponse(null, 'Property not found.');
        }

        $property->delete();

        return $this->successResponse(null, 'Property deleted successfully.');
    }
}