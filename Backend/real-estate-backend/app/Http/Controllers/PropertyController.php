<?php

namespace App\Http\Controllers;

use App\Constants\UserType;
use App\Models\Property;
use App\Models\PropertyMedia;
use App\Services\PropertyMediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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
            $query->where('construction_status', $request->input('construction_status'));
        }
        if ($request->has('approval_status')) {
            $query->where('approval_status', $request->input('approval_status'));
        }

        // Paginate the results with dynamic per_page value
        $perPage = $request->input('per_page', 10); // Default to 10 if not provided
        $properties = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'properties' => $properties,
        ]);
    }

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
    public function store(Request $request, PropertyMediaService $mediaService)
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
            'media' => 'nullable|array',
            'media.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,avi|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Add the authenticated user's ID to the request data
        $data = $request->except('media');
        $data['user_id'] = Auth::id();

        // Set the initial status to 'pending' for regular users
        if (Auth::user()->user_type === UserType::USER) {
            $data['approval_status'] = 'pending';
        }

        // Create the property
        $property = Property::create($data);

        // Handle media uploads if present
        if ($request->hasFile('media')) {
            try {
                $uploadedMedia = $mediaService->handleUpload($property, $request->file('media'));

                // If no cover image was set during upload but we have images, set the first one
                if (empty($property->cover_image)) {
                    $firstImage = collect($uploadedMedia)->firstWhere('MediaType', 'image');
                    if ($firstImage) {
                        $property->cover_image = $firstImage->MediaURL;
                        $property->save();
                    }
                }
            } catch (\Exception $e) {
                // If media upload fails, delete the property and return error
                $property->delete();
                return response()->json([
                    'error' => 'Media upload failed: ' . $e->getMessage()
                ], 500);
            }
        }

        // Load the media relationship for the response
        $property->load('media');

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
        if ($user->user_type !== UserType::ADMIN && $user->user_type !== UserType::SUPER_ADMIN && $property->user_id !== $user->id) {
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
            'media' => 'nullable|array', // For multiple media files
            'media.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,avi|max:20480', // 20MB max for each file
            'media_to_delete' => 'nullable|array', // Array of media IDs to delete
            'media_to_delete.*' => 'exists:property_media,id', // Ensure IDs exist
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Update the property
        $property->update($request->except(['media', 'media_to_delete']));

        // Handle media deletions if requested
        if ($request->has('media_to_delete')) {
            foreach ($request->input('media_to_delete') as $mediaId) {
                $media = PropertyMedia::find($mediaId);

                // Verify the media belongs to this property
                if ($media && $media->PropertyID == $property->id) {
                    // Delete the file from storage
                    Storage::disk('public')->delete($media->MediaURL);
                    // Delete the media record
                    $media->delete();
                }
            }
        }

        // Handle new media uploads if present
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                // Store the file and get the path
                $path = $file->store('property_media', 'public');

                // Determine media type
                $mediaType = in_array($file->getClientOriginalExtension(), ['mp4', 'mov', 'avi']) ? 'video' : 'image';

                // Create media record
                PropertyMedia::create([
                    'PropertyID' => $property->id,
                    'MediaURL' => $path,
                    'MediaType' => $mediaType,
                ]);

                // If no cover image is set and this is an image, set it as cover
                if ($mediaType === 'image' && empty($property->cover_image)) {
                    $property->cover_image = $path;
                    $property->save();
                }
            }
        }

        // Refresh the property with its relationships
        $property->load('media');

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
        if ($user->user_type !== UserType::ADMIN && $user->user_type !== UserType::SUPER_ADMIN && $property->user_id !== $user->id) {
            return response()->json(['error' => 'Forbidden. You do not have permission to delete this property.'], 403);
        }

        // Delete the property (the model event will handle media deletion)
        $property->delete();

        return response()->json([
            'success' => 'Property and all associated media deleted successfully',
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
        if ($user->user_type !== UserType::ADMIN && $user->user_type !== UserType::SUPER_ADMIN) {
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
        if ($user->user_type !== UserType::ADMIN && $user->user_type !== UserType::SUPER_ADMIN) {
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


    public function getPendingProperties(Request $request)
    {
        $user = $request->user();
        $query = Property::where('approval_status', 'pending');
    
        if ($user->user_type === UserType::USER) {
            $query->where('user_id', $user->id);
        }
    
        // Add search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('property_code', 'like', "%{$search}%");
            });
        }
    
        $properties = $query->latest()->paginate(5);
    
        return response()->json([
            'properties' => $properties
        ]);
    }
    
    public function getAcceptedProperties(Request $request)
    {
        $user = $request->user();
        $query = Property::where('approval_status', 'accepted');
    
        if ($user->user_type === UserType::USER) {
            $query->where('user_id', $user->id);
        }
    
        // Add search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('property_code', 'like', "%{$search}%");
            });
        }
    
        $properties = $query->latest()->paginate(5);
    
        return response()->json([
            'properties' => $properties
        ]);
    }
    
    public function getRejectedProperties(Request $request)
    {
        $user = $request->user();
        $query = Property::where('approval_status', 'rejected');
    
        if ($user->user_type === UserType::USER) {
            $query->where('user_id', $user->id);
        }
    
        // Add search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('property_code', 'like', "%{$search}%");
            });
        }
    
        $properties = $query->latest()->paginate(5);
    
        return response()->json([
            'properties' => $properties
        ]);
    }

    public function search(Request $request)
    {
        try {
            $query = Property::query();

            // Search by title
            if ($request->has('title')) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            // Search by property type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Search by price range
            if ($request->has('price_min')) {
                $query->where('price', '>=', $request->price_min);
            }
            if ($request->has('price_max')) {
                $query->where('price', '<=', $request->price_max);
            }

            // Search by city
            if ($request->has('city')) {
                $query->where('city', 'like', '%' . $request->city . '%');
            }

            // Search by district
            if ($request->has('district')) {
                $query->where('district', 'like', '%' . $request->district . '%');
            }

            // Search by area
            if ($request->has('area_min')) {
                $query->where('area', '>=', $request->area_min);
            }
            if ($request->has('area_max')) {
                $query->where('area', '<=', $request->area_max);
            }

            // Search by bedrooms count
            if ($request->has('bedrooms')) {
                $query->where('bedrooms', $request->bedrooms);
            }

            // Search by bathrooms count
            if ($request->has('bathrooms')) {
                $query->where('bathrooms', $request->bathrooms);
            }

            // Search by listing type (sale/rent)
            if ($request->has('listing_type')) {
                $query->where('listing_type', $request->listing_type);
            }

            // Search by construction status
            if ($request->has('construction_status')) {
                $query->where('construction_status', $request->construction_status);
            }

            // Sorting
            if ($request->has('sort_by')) {
                $direction = $request->get('sort_direction', 'asc');
                $query->orderBy($request->sort_by, $direction);
            }

            // Results per page
            $perPage = $request->get('per_page', 10);
            $properties = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $properties
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function getMedia($propertyId)
    {
        $property = Property::findOrFail($propertyId);
        return response()->json([
            'media' => $property->media
        ]);
    }

    /**
     * Add media to an existing property
     */
    public function addMedia(Request $request, $propertyId, PropertyMediaService $mediaService)
    {
        // Check authorization
        $property = Property::findOrFail($propertyId);
        $user = Auth::user();

        if ($user->user_type !== UserType::ADMIN && $user->user_type !== UserType::SUPER_ADMIN && $property->user_id !== $user->id) {
            return response()->json(['error' => 'Forbidden. You do not have permission to add media to this property.'], 403);
        }

        // Handle the media upload using the service
        $uploadedMedia = $mediaService->handleUpload($property, $request->file('media'));

        return response()->json([
            'success' => 'Media added successfully',
            'media' => $uploadedMedia,
        ], 201);
    }

    /**
     * Delete specific media from a property
     */
    public function deleteMedia($propertyId, $mediaId, PropertyMediaService $mediaService)
    {
        // Check authorization
        $property = Property::findOrFail($propertyId);
        $media = PropertyMedia::where('PropertyID', $propertyId)->findOrFail($mediaId);
        $user = Auth::user();

        if ($user->user_type !== UserType::ADMIN && $user->user_type !== UserType::SUPER_ADMIN && $property->user_id !== $user->id) {
            return response()->json(['error' => 'Forbidden. You do not have permission to delete media from this property.'], 403);
        }

        // Delete the media using the service
        $mediaService->deleteMedia($property, $media);

        return response()->json([
            'success' => 'Media deleted successfully'
        ], 200);
    }
}
