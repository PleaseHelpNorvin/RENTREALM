<?php

namespace App\Http\Controllers\rest;

use App\Models\Room;
use App\Models\Address;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class PropertyController extends Controller
{
    // Show all properties
    public function index()
    {
        $properties = Property::with('address')->get();

        $properties->transform(function ($property) {
            $property->property_picture_url = json_decode($property->property_picture_url, true);
            return $property;
        });

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
        Log::info('Incoming request data', ['data' => $request->all()]);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'property_picture_url' => 'required|array',
            'property_picture_url.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'line_1' => 'required|string|max:255',
            'line_2' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'gender_allowed' => 'required|in:boys-only,girls-only',
            'type' => 'required|in:apartment,house,boarding-house',
            'status' => 'required|in:vacant,full',
        ]);

        $imageUrls = [];
        if ($request->hasFile('property_picture_url')) {
            foreach ($request->file('property_picture_url') as $image) {
                $imagePath = $image->store('property_pictures', 'public');
                $imageUrls[] = asset('storage/' . $imagePath);
            }
        }

        $validatedData['property_picture_url'] = json_encode($imageUrls);

        $property = Property::create([
            'name' => $validatedData['name'],
            'property_picture_url' => $validatedData['property_picture_url'],
            'gender_allowed' => $validatedData['gender_allowed'],
            'type' => $validatedData['type'],
            'status' => $validatedData['status'],
        ]);

        $fullAddress = "{$validatedData['line_1']}, {$validatedData['line_2']}, {$validatedData['province']}, {$validatedData['country']}, {$validatedData['postal_code']}";
        $coordinates = Address::getCoordinates($fullAddress);

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

        return $this->successResponse(['properties' => [$property]], "{$property->name} Fetched Successfully");
    }

    // Update an existing property
    public function update(Request $request, $id)
    {
        Log::info($request->all());

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'property_picture_url' => 'required|array',
            'property_picture_url.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'line_1' => 'required|string|max:255',
            'line_2' => 'nullable|string|max:255',
            'province' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'gender_allowed' => 'required|in:boys-only,girls-only',
            'type' => 'required|in:apartment,house,boarding-house',
            'status' => 'required|in:vacant,full',
        ]);

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
            $validatedData['property_picture_url'] = $property->property_picture_url;
        }

        $property->update([
            'name' => $validatedData['name'],
            'property_picture_url' => $validatedData['property_picture_url'],
            'gender_allowed' => $validatedData['gender_allowed'],
            'type' => $validatedData['type'],
            'status' => $validatedData['status'],
        ]);

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
        $property = Property::find($id);

        if (!$property) {
            return $this->notFoundResponse(null, 'Property not found.');
        }

        if ($property->address) {
            $property->address()->delete();
        }

        $property->rooms()->delete();
        $property->delete();

        return $this->successResponse(null, 'Property and related rooms deleted successfully.');
    }

    // Search properties
    public function search(Request $request)
    {
        $query = Property::query()->with('address');

        if ($request->filled('name')) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($request->name) . '%']);
        }

        if ($request->filled('address')) {
            $query->whereHas('address', function ($q) use ($request) {
                $q->whereRaw('LOWER(line_1) LIKE ?', ['%' . strtolower($request->address) . '%'])
                    ->orWhereRaw('LOWER(line_2) LIKE ?', ['%' . strtolower($request->address) . '%'])
                    ->orWhereRaw('LOWER(province) LIKE ?', ['%' . strtolower($request->address) . '%'])
                    ->orWhereRaw('LOWER(country) LIKE ?', ['%' . strtolower($request->address) . '%'])
                    ->orWhereRaw('postal_code LIKE ?', ['%' . $request->address . '%']);
            });
        }

        foreach (['type', 'status', 'gender_allowed'] as $filter) {
            if ($request->filled($filter)) {
                $query->whereRaw("LOWER($filter) LIKE ?", ['%' . strtolower($request->$filter) . '%']);
            }
        }

        Log::info($query->toSql(), $query->getBindings());

        $properties = $query->get();

        return $this->successResponse(['properties' => $properties], 'Searched Property fetched successfully.');
    }
}
