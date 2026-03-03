<?php

namespace App\Http\Controllers\Api\Vehicle;

use App\Http\Controllers\Controller;
use App\Models\VehicleModelImage;
use App\Models\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use OpenApi\Annotations as OA;

class VehicleModelImageController extends Controller
{
    /**
     * Display a listing of vehicle model images.
     *
     * @OA\Get(
     *     path="/api/vehicle/model-images",
     *     operationId="getVehicleModelImages",
     *     tags={"Vehicle Model Images"},
     *     summary="Get all vehicle model images",
     *     description="Returns list of all vehicle model images with optional filters",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         description="Filter by model ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="is_primary",
     *         in="query",
     *         description="Filter primary images only",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="angle",
     *         in="query",
     *         description="Filter by angle",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field (order, created_at)",
     *         required=false,
     *         @OA\Schema(type="string", default="order")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order (asc, desc)",
     *         required=false,
     *         @OA\Schema(type="string", default="asc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="with",
     *         in="query",
     *         description="Include relationships (model, model.brand)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Vehicle model images retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'model_id' => 'integer|exists:vehicle_models,id',
            'is_primary' => 'boolean',
            'angle' => 'string|max:50',
            'sort_by' => 'string|in:order,created_at',
            'sort_order' => 'string|in:asc,desc',
            'per_page' => 'integer|min:1|max:100',
            'with' => 'string'
        ]);

        $query = VehicleModelImage::query();

        // Filter by model
        if ($request->has('model_id') && $request->model_id) {
            $query->where('model_id', $request->model_id);
        }

        // Filter by primary status
        if ($request->has('is_primary')) {
            $query->where('is_primary', $request->boolean('is_primary'));
        }

        // Filter by angle
        if ($request->has('angle') && $request->angle) {
            $query->where('angle', $request->angle);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'order');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Include relationships
        if ($request->has('with') && $request->with) {
            $relations = array_filter(explode(',', $request->with));
            $allowedRelations = ['model', 'model.brand'];
            $relations = array_intersect($relations, $allowedRelations);
            
            if (!empty($relations)) {
                $query->with($relations);
            }
        }

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $images = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $images,
            'message' => 'Vehicle model images retrieved successfully'
        ]);
    }

    /**
     * Store a newly created vehicle model image.
     *
     * @OA\Post(
     *     path="/api/vehicle/model-images",
     *     operationId="createVehicleModelImage",
     *     tags={"Vehicle Model Images"},
     *     summary="Upload a new vehicle model image",
     *     description="Upload a new image for a vehicle model",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"model_id", "image"},
     *                 @OA\Property(property="model_id", type="integer", example=1),
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="Image file (jpg, jpeg, png, webp, gif)"
     *                 ),
     *                 @OA\Property(property="is_primary", type="boolean", example=false),
     *                 @OA\Property(property="angle", type="string", example="front"),
     *                 @OA\Property(property="caption", type="string", example="Front view of Toyota Avanza"),
     *                 @OA\Property(property="order", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Vehicle model image uploaded successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleModelImage"),
     *             @OA\Property(property="message", type="string", example="Vehicle model image uploaded successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - No permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to upload vehicle model images")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120', // 5MB max
            'is_primary' => 'boolean',
            'angle' => 'nullable|string|max:50',
            'caption' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle image upload
        try {
            $imageFile = $request->file('image');
            
            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
            
            // Define storage path
            $folder = 'vehicle-models/' . $request->model_id;
            $path = $imageFile->storeAs($folder, $filename, 'public');
            
            // Create thumbnail if needed (optional)
            $this->createThumbnail($imageFile, $folder, $filename);

            // If setting as primary, unset other primary images for this model
            if ($request->boolean('is_primary')) {
                VehicleModelImage::where('model_id', $request->model_id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            // Get order value or auto-increment
            $order = $request->order ?? VehicleModelImage::where('model_id', $request->model_id)->max('order') + 1;

            $image = VehicleModelImage::create([
                'model_id' => $request->model_id,
                'image_path' => $path,
                'is_primary' => $request->boolean('is_primary'),
                'angle' => $request->angle,
                'caption' => $request->caption,
                'order' => $order
            ]);

            return response()->json([
                'success' => true,
                'data' => $image->load('model'),
                'message' => 'Vehicle model image uploaded successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create thumbnail for uploaded image
     */
    private function createThumbnail($imageFile, $folder, $filename): void
    {
        try {
            $thumbnail = Image::make($imageFile->getRealPath());
            $thumbnail->resize(300, 200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            $thumbnailPath = $folder . '/thumbs/' . $filename;
            Storage::disk('public')->put($thumbnailPath, (string) $thumbnail->encode());
        } catch (\Exception $e) {
            // Log error but don't fail the upload
            Log::error('Thumbnail creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified vehicle model image.
     *
     * @OA\Get(
     *     path="/api/vehicle/model-images/{id}",
     *     operationId="getVehicleModelImage",
     *     tags={"Vehicle Model Images"},
     *     summary="Get specific vehicle model image",
     *     description="Returns vehicle model image details by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Model Image ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="with",
     *         in="query",
     *         description="Include relationships (model, model.brand)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleModelImage"),
     *             @OA\Property(property="message", type="string", example="Vehicle model image retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle model image not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle model image not found")
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $image = VehicleModelImage::findOrFail($id);
            
            // Load relationships if requested
            if (request()->has('with') && request()->with) {
                $relations = array_filter(explode(',', request()->get('with')));
                $allowedRelations = ['model', 'model.brand'];
                $relations = array_intersect($relations, $allowedRelations);
                
                if (!empty($relations)) {
                    $image->load($relations);
                }
            }

            // Add full URL to image path
            $image->full_image_url = Storage::disk('public')->url($image->image_path);
            $image->thumbnail_url = Storage::disk('public')->exists(dirname($image->image_path) . '/thumbs/' . basename($image->image_path))
                ? Storage::disk('public')->url(dirname($image->image_path) . '/thumbs/' . basename($image->image_path))
                : null;

            return response()->json([
                'success' => true,
                'data' => $image,
                'message' => 'Vehicle model image retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle model image not found'
            ], 404);
        }
    }

    /**
     * Update the specified vehicle model image.
     *
     * @OA\Put(
     *     path="/api/vehicle/model-images/{id}",
     *     operationId="updateVehicleModelImage",
     *     tags={"Vehicle Model Images"},
     *     summary="Update vehicle model image",
     *     description="Update vehicle model image details (metadata only, not the image file)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Model Image ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="is_primary", type="boolean", example=true),
     *             @OA\Property(property="angle", type="string", example="side"),
     *             @OA\Property(property="caption", type="string", example="Side view updated"),
     *             @OA\Property(property="order", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle model image updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleModelImage"),
     *             @OA\Property(property="message", type="string", example="Vehicle model image updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle model image not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle model image not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - No permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to update vehicle model images")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $image = VehicleModelImage::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'is_primary' => 'boolean',
                'angle' => 'nullable|string|max:50',
                'caption' => 'nullable|string|max:255',
                'order' => 'nullable|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // If setting as primary, unset other primary images for this model
            if ($request->has('is_primary') && $request->boolean('is_primary') && !$image->is_primary) {
                VehicleModelImage::where('model_id', $image->model_id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $image->update($request->only(['is_primary', 'angle', 'caption', 'order']));

            return response()->json([
                'success' => true,
                'data' => $image->load('model'),
                'message' => 'Vehicle model image updated successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle model image not found'
            ], 404);
        }
    }

    /**
     * Remove the specified vehicle model image.
     *
     * @OA\Delete(
     *     path="/api/vehicle/model-images/{id}",
     *     operationId="deleteVehicleModelImage",
     *     tags={"Vehicle Model Images"},
     *     summary="Delete vehicle model image",
     *     description="Delete vehicle model image by ID (removes file from storage)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Model Image ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle model image deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle model image deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle model image not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle model image not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - No permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to delete vehicle model images")
     *         )
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $image = VehicleModelImage::findOrFail($id);

            // Delete file from storage
            if (Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
                
                // Delete thumbnail if exists
                $thumbnailPath = dirname($image->image_path) . '/thumbs/' . basename($image->image_path);
                if (Storage::disk('public')->exists($thumbnailPath)) {
                    Storage::disk('public')->delete($thumbnailPath);
                }
            }

            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle model image deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle model image not found'
            ], 404);
        }
    }

    /**
     * Set image as primary for a model.
     *
     * @OA\Post(
     *     path="/api/vehicle/model-images/{id}/set-primary",
     *     operationId="setImageAsPrimary",
     *     tags={"Vehicle Model Images"},
     *     summary="Set image as primary",
     *     description="Set a specific image as the primary image for its model",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Model Image ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image set as primary successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleModelImage"),
     *             @OA\Property(property="message", type="string", example="Image set as primary successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle model image not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle model image not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - No permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to update vehicle model images")
     *         )
     *     )
     * )
     */
    public function setAsPrimary(string $id): JsonResponse
    {
        try {
            $image = VehicleModelImage::findOrFail($id);

            // Unset other primary images for this model
            VehicleModelImage::where('model_id', $image->model_id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            // Set this image as primary
            $image->update(['is_primary' => true]);

            return response()->json([
                'success' => true,
                'data' => $image,
                'message' => 'Image set as primary successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle model image not found'
            ], 404);
        }
    }

    /**
     * Reorder images for a model.
     *
     * @OA\Post(
     *     path="/api/vehicle/model-images/reorder",
     *     operationId="reorderModelImages",
     *     tags={"Vehicle Model Images"},
     *     summary="Reorder model images",
     *     description="Update the order of multiple images at once",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_data"},
     *             @OA\Property(
     *                 property="order_data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="order", type="integer")
     *                 ),
     *                 example={{"id": 1, "order": 2}, {"id": 2, "order": 1}}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Images reordered successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Images reordered successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - No permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to update vehicle model images")
     *         )
     *     )
     * )
     */
    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_data' => 'required|array',
            'order_data.*.id' => 'required|integer|exists:vehicle_model_images,id',
            'order_data.*.order' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($request->order_data as $item) {
                VehicleModelImage::where('id', $item['id'])
                    ->update(['order' => $item['order']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Images reordered successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available angles.
     *
     * @OA\Get(
     *     path="/api/vehicle/model-images/angles",
     *     operationId="getImageAngles",
     *     tags={"Vehicle Model Images"},
     *     summary="Get available image angles",
     *     description="Returns list of all distinct angles in the system",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="message", type="string", example="Image angles retrieved successfully")
     *         )
     *     )
     * )
     */
    public function angles(): JsonResponse
    {
        $angles = VehicleModelImage::select('angle')
            ->whereNotNull('angle')
            ->distinct()
            ->orderBy('angle')
            ->pluck('angle');

        return response()->json([
            'success' => true,
            'data' => $angles,
            'message' => 'Image angles retrieved successfully'
        ]);
    }
}