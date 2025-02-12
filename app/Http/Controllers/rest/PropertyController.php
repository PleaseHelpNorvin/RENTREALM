<?php
namespace App\Http\Controllers\rest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Address;
use App\Models\Room;

class PropertyController extends Controller
{
    // Show all properties
    public function index()
    {
        // Eager-load the 'address' relationship with properties
        $properties = Property::with('address')->get();
    
        if ($properties->isEmpty()) {
            return $this->notFoundResponse(null, 'No properties found.');
        }
    
        return $this->successResponse(
            ['properties' => $properties],
            'Properties Fetched Successfully',
            200
        );
    }

    // Store a new property
    public function store(Request $request)
    {
        \Log::info($request->all());
    
        // Validate input
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'property_picture_url' => 'required|array', // Ensure it's an array
            'property_picture_url.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate each file in the array
            'line_1' => 'required|string|max:255',
            'line_2' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'gender_allowed' => 'required|in:boys-only,girls-only',
            'pets_allowed' => 'required|boolean',
            'type' => 'required|in:apartment,house,boarding-house',
            'status' => 'required|in:available,rented,full',
        ]);
    
        $imageUrls = [];
        
        // Process images
        if ($request->hasFile('property_picture_url')) {
            foreach ($request->file('property_picture_url') as $image) {
                // Store image and generate URL
                $imagePath = $image->store('property_pictures', 'public');
                $imageUrls[] = asset('storage/' . $imagePath);
            }
        }
    
        // Add processed image URLs to validated data
        // JSON encode the URLs array before saving
        $validatedData['property_picture_url'] = json_encode($imageUrls);
    
        // Create the property
        $property = Property::create([
            'name' => $validatedData['name'],
            'property_picture_url' => $validatedData['property_picture_url'],
            'gender_allowed' => $validatedData['gender_allowed'],
            'pets_allowed' => $validatedData['pets_allowed'],
            'type' => $validatedData['type'],
            'status' => $validatedData['status'],
        ]);

        $fullAddress = "{$validatedData['line_1']}, {$validatedData['line_2']}, {$validatedData['province']}, {$validatedData['country']}, {$validatedData['postal_code']}";
        $coordinates = Address::getCoordinates($fullAddress);
    
        // Create the address for the property
        $property->address()->create([
            'line_1' => $validatedData['line_1'],
            'line_2' => $validatedData['line_2'],
            'province' => $validatedData['province'],
            'country' => $validatedData['country'],
            'postal_code' => $validatedData['postal_code'],
            'latitude' => $coordinates['latitude'] ?? null,
            'longitude' => $coordinates['longitude'] ?? null,
        ]);
    
        return $this->successResponse(['property' => $property], 'Property created successfully.', 201);
    }
    

    // Show a single property
    public function show($id)
    {
        $property = Property::with('address')->find($id);
    
        if (!$property) {
            return $this->notFoundResponse(null, 'Property not found.');
        }
    
        return $this->successResponse(['property' => $property], "{$property->name} Fetched Successfully");
    }

    // Update an existing property
    public function update(Request $request, $id)
    {
        \Log::info($request->all());
        // Validate input
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'property_picture_url' => 'nullable|array', // Ensure it's an array
            'property_picture_url.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate each file in the array
            'line_1' => 'required|string|max:255',
            'line_2' => 'nullable|string|max:255',
            'province' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'gender_allowed' => 'required|in:boys-only,girls-only',
            'pets_allowed' => 'required|in:0,1,true,false',
            'type' => 'required|in:apartment,house,boarding-house',
            'status' => 'required|in:available,rented,full',
        ]);
    
        // Find the property by ID
        $property = Property::find($id);
    
        if (!$property) {
            return $this->notFoundResponse(null, 'Property not found.');
        }
    
        $imageUrls = [];
        if ($request->hasFile('property_picture_url')) {
            foreach ($request->file('property_picture_url') as $image) {
                $imagePath = $image->store('property_pictures', 'public');
                $imageUrls[] = asset('storage/' . $imagePath);
            }
            $validatedData['property_picture_url'] = json_encode($imageUrls);
        } else {
            // Keep the existing property picture URLs if no new files are uploaded
            $validatedData['property_picture_url'] = $property->property_picture_url;
        }
    
        // Update the property fields
        $property->update([
            'name' => $validatedData['name'],
            'property_picture_url' => $validatedData['property_picture_url'],
            'gender_allowed' => $validatedData['gender_allowed'],
            'pets_allowed' => $validatedData['pets_allowed'],
            'type' => $validatedData['type'],
            'status' => $validatedData['status'],
        ]);
    
        // Update the associated address
        $property->address()->update([
            'line_1' => $validatedData['line_1'],
            'line_2' => $validatedData['line_2'],
            'province' => $validatedData['province'],
            'country' => $validatedData['country'],
            'postal_code' => $validatedData['postal_code'],
        ]);
    
        return $this->successResponse($property, 'Property updated successfully.');
    }
    
    // Delete a property
    public function destroy($id)
    {
        // Find the property by ID
        $property = Property::find($id);

        // If the property does not exist, return a not found response
        if (!$property) {
            return $this->notFoundResponse(null, 'Property not found.');
        }

        if ($property->address) {
            $property->address()->delete();
        }

        // Delete all related rooms manually (if not using cascading delete)
        $property->rooms()->delete();

        // Delete the property
        $property->delete();

        return $this->successResponse(null, 'Property and related rooms deleted successfully.');
    }
}
