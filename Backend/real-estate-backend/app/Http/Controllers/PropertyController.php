<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
 
        // Start with all properties
        $query = Property::query();

        // Apply filters
        if ($request->has('city')) {
            $query->where('city', $request->input('city'));
        }
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }
        if ($request->has('construction_status')) {
            $query->where('status', $request->input('status'));
        }

        // Paginate the results
        $properties = $query->paginate(10);

        return response()->json([
            'success' => true,
            'properties' => $properties,
        ]);    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       // Validate the request data
       $validator = Validator::make($request->all(), [
        'title' => 'required|string|max:255',
        'slug' => 'required|string|unique:properties,slug',
        'description' => 'required|string',
        'type' => 'required|in:land,apartment,villa,office',
        'price' => 'required|numeric|min:0',
        'city' => 'required|string',
        'district' => 'required|string',
        'full_address' => 'nullable|string',
        'area' => 'required|integer|min:0',
        'bedrooms' => 'nullable|integer|min:0',
        'bathrooms' => 'nullable|integer|min:0',
        'listing_type' => 'required|in:for_sale,for_rent',
        'construction_status' => 'required|in:available,under_construction',
        'transaction_status' => 'nullable|in:pending,completed',
        'approval_status' => 'sometimes|in:pending,accepted,rejected',
        'building_year' => 'nullable|integer|min:1900|max:' . date('Y'),
        'legal_status' => 'nullable|in:licensed,unlicensed,pending',
        'furnished' => 'nullable|boolean',
        'amenities' => 'nullable|json',
        'payment_options' => 'nullable|json',
        'cover_image' => 'nullable|string',
        'property_code' => 'required|string|unique:properties,property_code',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Add the authenticated user's ID to the request data
    $data = $request->all();
    $data['user_id'] = Auth::id();

    // Set the initial status to 'pending' for regular users
    if (Auth::user()->user_type === 'user') {
        $data['approval_status'] = 'pending';
    }

    // Create the property
    $property = Property::create($data);

    return response()->json([
        'success' => 'Property created successfully',
        'property' => $property,
    ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $property = Property::find($id);

        if (!$property) {
            return response()->json(['error' => 'Property not found'], 404);
        }

        return response()->json([
            'success' => true,
            'property' => $property,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
         // Find the property
         $property = Property::find($id);

         if (!$property) {
             return response()->json(['error' => 'Property not found'], 404);
         }
 
         // Check if the authenticated user is the owner, admin, or super-admin
         $user = Auth::user();
         if ($user->user_type !== 'admin' && $user->user_type !== 'super-admin' && $property->user_id !== $user->id) {
             return response()->json(['error' => 'Forbidden. You do not have permission to update this property.'], 403);
         }
 
         // Validate the request data
         $validator = Validator::make($request->all(), [
             'title' => 'sometimes|string|max:255',
             'slug' => 'sometimes|string|unique:properties,slug,' . $property->id,
             'description' => 'sometimes|string',
             'type' => 'sometimes|in:land,apartment,villa,office',
             'price' => 'sometimes|numeric|min:0',
             'city' => 'sometimes|string',
             'district' => 'sometimes|string',
             'full_address' => 'nullable|string',
             'area' => 'sometimes|integer|min:0',
             'bedrooms' => 'nullable|integer|min:0',
             'bathrooms' => 'nullable|integer|min:0',
             'listing_type' => 'sometimes|in:for_sale,for_rent',
             'construction_status' => 'sometimes|in:available,under_construction',
             'transaction_status' => 'nullable|in:pending,completed',
             'building_year' => 'nullable|integer|min:1900|max:' . date('Y'),
             'legal_status' => 'nullable|in:licensed,unlicensed,pending',
             'furnished' => 'nullable|boolean',
             'amenities' => 'nullable|json',
             'payment_options' => 'nullable|json',
             'cover_image' => 'nullable|string',
             'property_code' => 'sometimes|string|unique:properties,property_code,' . $property->id,
         ]);
 
         if ($validator->fails()) {
             return response()->json(['error' => $validator->errors()], 400);
         }
 
         // Update the property
         $property->update($request->all());
 
         return response()->json([
             'success' => 'Property updated successfully',
             'property' => $property,
         ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
       // Find the property
       $property = Property::find($id);

       if (!$property) {
           return response()->json(['error' => 'Property not found'], 404);
       }

       // Check if the authenticated user is the owner, admin, or super-admin
       $user = Auth::user();
       if ($user->user_type !== 'admin' && $user->user_type !== 'super-admin' && $property->user_id !== $user->id) {
           return response()->json(['error' => 'Forbidden. You do not have permission to delete this property.'], 403);
       }

       // Delete the property
       $property->delete();

       return response()->json([
           'success' => 'Property deleted successfully',
       ], 200);
   }

   /**
     * Accept a property (accessible by admin and super-admin).
     */
    public function acceptProperty($id)
    {
         // Find the property
    $property = Property::find($id);

    if (!$property) {
        return response()->json(['error' => 'Property not found'], 404);
    }

    // Ensure the authenticated user is an admin or super-admin
    $user = Auth::user();
    if ($user->user_type !== 'admin' && $user->user_type !== 'super-admin') {
        return response()->json(['error' => 'Forbidden. Only admins and super-admins can accept properties.'], 403);
    }


    // Update the status to 'accepted'
    $property->approval_status = 'accepted';
    $property->save();


    return response()->json([
        'message' => 'Property accepted successfully',
        'property' => $property,
    ], 200);
    }

    /**
     * Reject a property (accessible by admin and super-admin).
     */
    public function rejectProperty($id)
    {
      // Find the property
      $property = Property::find($id);

      if (!$property) {
          return response()->json(['error' => 'Property not found'], 404);
      }
  
      // Ensure the authenticated user is an admin or super-admin
      $user = Auth::user();
      if ($user->user_type !== 'admin' && $user->user_type !== 'super-admin') {
          return response()->json(['error' => 'Forbidden. Only admins and super-admins can reject properties.'], 403);
      }
 
      // Update the status to 'rejected'
      $property->approval_status = 'rejected';
      $property->save();
   
      return response()->json([
          'message' => 'Property rejected successfully',
          'property' => $property,
      ], 200);
    }
}
