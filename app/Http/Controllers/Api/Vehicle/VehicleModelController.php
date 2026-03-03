<?php

namespace App\Http\Controllers\Api\Vehicle;

use App\Http\Controllers\Controller;
use App\Models\VehicleModel;
use App\Models\VehicleBrand;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class VehicleModelController extends Controller
{
    /**
     * Display a listing of vehicle models.
     *
     * @OA\Get(
     *     path="/api/vehicle/models",
     *     operationId="getVehicleModels",
     *     tags={"Vehicle Models"},
     *     summary="Get all vehicle models",
     *     description="Returns list of all vehicle models with optional filters",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="brand_id",
     *         in="query",
     *         description="Filter by brand ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="active_only",
     *         in="query",
     *         description="Filter only active models",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by model name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field (name, created_at, updated_at)",
     *         required=false,
     *         @OA\Schema(type="string", default="name")
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
     *         description="Include relationships (brand, types, details)",
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
     *             @OA\Property(property="message", type="string", example="Vehicle models retrieved successfully")
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
            'brand_id' => 'integer|exists:vehicle_brands,id',
            'active_only' => 'boolean',
            'search' => 'string|max:100',
            'sort_by' => 'string|in:name,created_at,updated_at',
            'sort_order' => 'string|in:asc,desc',
            'per_page' => 'integer|min:1|max:100',
            'with' => 'string'
        ]);

        $query = VehicleModel::query();

        // Filter by brand
        if ($request->has('brand_id') && $request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter by active status
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        // Sort
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Include relationships
        if ($request->has('with') && $request->with) {
            $relations = array_filter(explode(',', $request->with));
            $allowedRelations = ['brand', 'types', 'details', 'images'];
            $relations = array_intersect($relations, $allowedRelations);
            
            if (!empty($relations)) {
                $query->with($relations);
            }
        }

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $models = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $models,
            'message' => 'Vehicle models retrieved successfully'
        ]);
    }

    /**
     * Store a newly created vehicle model.
     *
     * @OA\Post(
     *     path="/api/vehicle/models",
     *     operationId="createVehicleModel",
     *     tags={"Vehicle Models"},
     *     summary="Create a new vehicle model",
     *     description="Create a new vehicle model with provided data",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"brand_id", "name"},
     *             @OA\Property(property="brand_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Avanza"),
     *             @OA\Property(property="description", type="string", example="Toyota Avanza - MPV 7-seater"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Vehicle model created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleModel"),
     *             @OA\Property(property="message", type="string", example="Vehicle model created successfully")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to create vehicle models")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'name' => 'required|string|max:100|unique:vehicle_models,name,NULL,id,brand_id,' . $request->brand_id,
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $model = VehicleModel::create($request->only(['brand_id', 'name', 'description', 'is_active']));

        return response()->json([
            'success' => true,
            'data' => $model->load('brand'),
            'message' => 'Vehicle model created successfully'
        ], 201);
    }

    /**
     * Display the specified vehicle model.
     *
     * @OA\Get(
     *     path="/api/vehicle/models/{id}",
     *     operationId="getVehicleModel",
     *     tags={"Vehicle Models"},
     *     summary="Get specific vehicle model",
     *     description="Returns vehicle model details by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="with",
     *         in="query",
     *         description="Include relationships (brand, types, details, images)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleModel"),
     *             @OA\Property(property="message", type="string", example="Vehicle model retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle model not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle model not found")
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $model = VehicleModel::findOrFail($id);
            
            // Load relationships if requested
            if (request()->has('with') && request()->with) {
                $relations = array_filter(explode(',', request()->get('with')));
                $allowedRelations = ['brand', 'types', 'details', 'images'];
                $relations = array_intersect($relations, $allowedRelations);
                
                if (!empty($relations)) {
                    $model->load($relations);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $model,
                'message' => 'Vehicle model retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle model not found'
            ], 404);
        }
    }

    /**
     * Update the specified vehicle model.
     *
     * @OA\Put(
     *     path="/api/vehicle/models/{id}",
     *     operationId="updateVehicleModel",
     *     tags={"Vehicle Models"},
     *     summary="Update vehicle model",
     *     description="Update vehicle model details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="brand_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Avanza"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle model updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleModel"),
     *             @OA\Property(property="message", type="string", example="Vehicle model updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle model not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle model not found")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to update vehicle models")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $model = VehicleModel::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'brand_id' => 'required|integer|exists:vehicle_brands,id',
                'name' => 'required|string|max:100|unique:vehicle_models,name,' . $id . ',id,brand_id,' . $request->brand_id,
                'description' => 'nullable|string|max:500',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $model->update($request->only(['brand_id', 'name', 'description', 'is_active']));

            return response()->json([
                'success' => true,
                'data' => $model->load('brand'),
                'message' => 'Vehicle model updated successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle model not found'
            ], 404);
        }
    }

    /**
     * Remove the specified vehicle model.
     *
     * @OA\Delete(
     *     path="/api/vehicle/models/{id}",
     *     operationId="deleteVehicleModel",
     *     tags={"Vehicle Models"},
     *     summary="Delete vehicle model",
     *     description="Delete vehicle model by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle model deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle model deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle model not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle model not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - No permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to delete vehicle models")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete - Has related data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot delete model because it has associated vehicle types")
     *         )
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $model = VehicleModel::findOrFail($id);

            // Check if model has related types
            if ($model->types()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete model because it has associated vehicle types'
                ], 422);
            }

            // Check if model has related details
            if ($model->details()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete model because it has associated vehicle details'
                ], 422);
            }

            $model->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle model deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle model not found'
            ], 404);
        }
    }

    /**
     * Get all types for a specific model.
     *
     * @OA\Get(
     *     path="/api/vehicle/models/{id}/types",
     *     operationId="getModelTypes",
     *     tags={"Vehicle Models"},
     *     summary="Get types for a model",
     *     description="Returns all vehicle types for a specific model",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="active_only",
     *         in="query",
     *         description="Filter only active types",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/VehicleType")),
     *             @OA\Property(property="message", type="string", example="Vehicle types retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle model not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle model not found")
     *         )
     *     )
     * )
     */
    public function types(string $id): JsonResponse
    {
        try {
            $model = VehicleModel::findOrFail($id);

            $query = $model->types();
            
            // Filter by active status
            if (request()->boolean('active_only')) {
                $query->where('is_active', true);
            }

            $types = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $types,
                'message' => 'Vehicle types retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle model not found'
            ], 404);
        }
    }

    /**
     * Get all vehicle details for a specific model.
     *
     * @OA\Get(
     *     path="/api/vehicle/models/{id}/details",
     *     operationId="getModelDetails",
     *     tags={"Vehicle Models"},
     *     summary="Get vehicle details for a model",
     *     description="Returns all vehicle details for a specific model",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Filter by year",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="active_only",
     *         in="query",
     *         description="Filter only active details",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Vehicle details retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle model not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle model not found")
     *         )
     *     )
     * )
     */
    public function details(string $id): JsonResponse
    {
        try {
            $model = VehicleModel::findOrFail($id);

            $query = $model->details()->with(['type', 'transmission', 'origin', 'features']);

            // Filter by year
            if (request()->has('year') && request()->year) {
                $query->where('year', request()->year);
            }

            // Filter by active status
            if (request()->boolean('active_only')) {
                $query->where('is_active', true);
            }

            // Order by year descending
            $query->orderBy('year', 'desc');

            // Paginate
            $perPage = request()->get('per_page', 15);
            $details = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $details,
                'message' => 'Vehicle details retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle model not found'
            ], 404);
        }
    }

    /**
     * Get all images for a specific model.
     *
     * @OA\Get(
     *     path="/api/vehicle/models/{id}/images",
     *     operationId="getModelImages",
     *     tags={"Vehicle Models"},
     *     summary="Get images for a model",
     *     description="Returns all images for a specific vehicle model",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/VehicleModelImage")),
     *             @OA\Property(property="message", type="string", example="Model images retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle model not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle model not found")
     *         )
     *     )
     * )
     */
    public function images(string $id): JsonResponse
    {
        try {
            $model = VehicleModel::findOrFail($id);

            $images = $model->images()->orderBy('is_primary', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $images,
                'message' => 'Model images retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle model not found'
            ], 404);
        }
    }

    /**
     * Get statistics for a specific model.
     *
     * @OA\Get(
     *     path="/api/vehicle/models/{id}/stats",
     *     operationId="getModelStats",
     *     tags={"Vehicle Models"},
     *     summary="Get model statistics",
     *     description="Returns statistics for a vehicle model",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Model statistics retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle model not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle model not found")
     *         )
     *     )
     * )
     */
    public function stats(string $id): JsonResponse
    {
        try {
            $model = VehicleModel::findOrFail($id);

            $stats = [
                'total_types' => $model->types()->count(),
                'active_types' => $model->types()->where('is_active', true)->count(),
                'total_details' => $model->details()->count(),
                'active_details' => $model->details()->where('is_active', true)->count(),
                'total_images' => $model->images()->count(),
                'years_available' => $model->details()
                    ->select('year')
                    ->distinct()
                    ->orderBy('year', 'desc')
                    ->pluck('year'),
                'engine_options' => $model->details()
                    ->select('cc', 'fuel_type')
                    ->distinct()
                    ->orderBy('cc')
                    ->get(),
                'transmission_options' => $model->details()
                    ->join('transmission_types', 'vehicle_details.transmission_id', '=', 'transmission_types.id')
                    ->select('transmission_types.name', 'transmission_types.id')
                    ->distinct()
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Model statistics retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle model not found'
            ], 404);
        }
    }
}