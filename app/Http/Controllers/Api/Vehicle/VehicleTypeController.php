<?php

namespace App\Http\Controllers\Api\Vehicle;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use App\Models\VehicleTypeBody;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class VehicleTypeController extends Controller
{
    /**
     * Display a listing of vehicle types.
     *
     * @OA\Get(
     *     path="/api/vehicle/types",
     *     operationId="getVehicleTypes",
     *     tags={"Vehicle Types"},
     *     summary="Get all vehicle types",
     *     description="Returns list of all vehicle types with optional filters",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         description="Filter by model ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type_body_id",
     *         in="query",
     *         description="Filter by type body ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="active_only",
     *         in="query",
     *         description="Filter only active types",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by type name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field (name, created_at)",
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
     *         description="Include relationships (model, typeBody, details, model.brand)",
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
     *             @OA\Property(property="message", type="string", example="Vehicle types retrieved successfully")
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
            'type_body_id' => 'integer|exists:vehicle_type_bodies,id',
            'active_only' => 'boolean',
            'search' => 'string|max:100',
            'sort_by' => 'string|in:name,created_at',
            'sort_order' => 'string|in:asc,desc',
            'per_page' => 'integer|min:1|max:100',
            'with' => 'string'
        ]);

        $query = VehicleType::query();

        // Filter by model
        if ($request->has('model_id') && $request->model_id) {
            $query->where('model_id', $request->model_id);
        }

        // Filter by type body
        if ($request->has('type_body_id') && $request->type_body_id) {
            $query->where('type_body_id', $request->type_body_id);
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
            $allowedRelations = ['model', 'typeBody', 'details', 'model.brand'];
            $relations = array_intersect($relations, $allowedRelations);
            
            if (!empty($relations)) {
                $query->with($relations);
            }
        }

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $types = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $types,
            'message' => 'Vehicle types retrieved successfully'
        ]);
    }

    /**
     * Store a newly created vehicle type.
     *
     * @OA\Post(
     *     path="/api/vehicle/types",
     *     operationId="createVehicleType",
     *     tags={"Vehicle Types"},
     *     summary="Create a new vehicle type",
     *     description="Create a new vehicle type with provided data",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"model_id", "name", "type_body_id"},
     *             @OA\Property(property="model_id", type="integer", example=1),
     *             @OA\Property(property="type_body_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="G 1.5"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Vehicle type created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleType"),
     *             @OA\Property(property="message", type="string", example="Vehicle type created successfully")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to create vehicle types")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'type_body_id' => 'required|integer|exists:vehicle_type_bodies,id',
            'name' => 'required|string|max:100|unique:vehicle_types,name,NULL,id,model_id,' . $request->model_id,
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $type = VehicleType::create($request->only([
            'model_id', 'type_body_id', 'name', 'is_active'
        ]));

        return response()->json([
            'success' => true,
            'data' => $type->load(['model', 'typeBody']),
            'message' => 'Vehicle type created successfully'
        ], 201);
    }

    /**
     * Display the specified vehicle type.
     *
     * @OA\Get(
     *     path="/api/vehicle/types/{id}",
     *     operationId="getVehicleType",
     *     tags={"Vehicle Types"},
     *     summary="Get specific vehicle type",
     *     description="Returns vehicle type details by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Type ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="with",
     *         in="query",
     *         description="Include relationships (model, typeBody, details, model.brand)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleType"),
     *             @OA\Property(property="message", type="string", example="Vehicle type retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle type not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle type not found")
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $type = VehicleType::findOrFail($id);
            
            // Load relationships if requested
            if (request()->has('with') && request()->with) {
                $relations = array_filter(explode(',', request()->get('with')));
                $allowedRelations = ['model', 'typeBody', 'details', 'model.brand'];
                $relations = array_intersect($relations, $allowedRelations);
                
                if (!empty($relations)) {
                    $type->load($relations);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $type,
                'message' => 'Vehicle type retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle type not found'
            ], 404);
        }
    }

    /**
     * Update the specified vehicle type.
     *
     * @OA\Put(
     *     path="/api/vehicle/types/{id}",
     *     operationId="updateVehicleType",
     *     tags={"Vehicle Types"},
     *     summary="Update vehicle type",
     *     description="Update vehicle type details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Type ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="model_id", type="integer", example=1),
     *             @OA\Property(property="type_body_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="G 1.5 CVT"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle type updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleType"),
     *             @OA\Property(property="message", type="string", example="Vehicle type updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle type not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle type not found")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to update vehicle types")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $type = VehicleType::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'model_id' => 'required|integer|exists:vehicle_models,id',
                'type_body_id' => 'required|integer|exists:vehicle_type_bodies,id',
                'name' => 'required|string|max:100|unique:vehicle_types,name,' . $id . ',id,model_id,' . $request->model_id,
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $type->update($request->only([
                'model_id', 'type_body_id', 'name', 'is_active'
            ]));

            return response()->json([
                'success' => true,
                'data' => $type->load(['model', 'typeBody']),
                'message' => 'Vehicle type updated successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle type not found'
            ], 404);
        }
    }

    /**
     * Remove the specified vehicle type.
     *
     * @OA\Delete(
     *     path="/api/vehicle/types/{id}",
     *     operationId="deleteVehicleType",
     *     tags={"Vehicle Types"},
     *     summary="Delete vehicle type",
     *     description="Delete vehicle type by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Type ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle type deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle type deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle type not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle type not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - No permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to delete vehicle types")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete - Has related data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot delete type because it has associated vehicle details")
     *         )
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $type = VehicleType::findOrFail($id);

            // Check if type has related details
            if ($type->details()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete type because it has associated vehicle details'
                ], 422);
            }

            $type->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle type deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle type not found'
            ], 404);
        }
    }

    /**
     * Get all vehicle details for a specific type.
     *
     * @OA\Get(
     *     path="/api/vehicle/types/{id}/details",
     *     operationId="getTypeDetails",
     *     tags={"Vehicle Types"},
     *     summary="Get vehicle details for a type",
     *     description="Returns all vehicle details for a specific type",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Type ID",
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
     *         description="Vehicle type not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle type not found")
     *         )
     *     )
     * )
     */
    public function details(string $id): JsonResponse
    {
        try {
            $type = VehicleType::findOrFail($id);

            $query = $type->details()->with(['type', 'transmission', 'origin', 'features']);

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
                'message' => 'Vehicle type not found'
            ], 404);
        }
    }

    /**
     * Get statistics for a specific type.
     *
     * @OA\Get(
     *     path="/api/vehicle/types/{id}/stats",
     *     operationId="getTypeStats",
     *     tags={"Vehicle Types"},
     *     summary="Get type statistics",
     *     description="Returns statistics for a vehicle type",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Type ID",
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
     *             @OA\Property(property="message", type="string", example="Type statistics retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle type not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle type not found")
     *         )
     *     )
     * )
     */
    public function stats(string $id): JsonResponse
    {
        try {
            $type = VehicleType::findOrFail($id);

            $stats = [
                'total_details' => $type->details()->count(),
                'active_details' => $type->details()->where('is_active', true)->count(),
                'years_available' => $type->details()
                    ->select('year')
                    ->distinct()
                    ->orderBy('year', 'desc')
                    ->pluck('year'),
                'engine_options' => $type->details()
                    ->select('cc', 'fuel_type', 'engine_type')
                    ->distinct()
                    ->orderBy('cc')
                    ->get(),
                'transmission_options' => $type->details()
                    ->join('transmission_types', 'vehicle_details.transmission_id', '=', 'transmission_types.id')
                    ->select('transmission_types.name', 'transmission_types.id')
                    ->distinct()
                    ->get(),
                'model_info' => $type->model()->with('brand')->first(),
                'type_body_info' => $type->typeBody,
                'features_summary' => $type->details()
                    ->join('vehicle_detail_features', 'vehicle_details.id', '=', 'vehicle_detail_features.vehicle_detail_id')
                    ->join('vehicle_features', 'vehicle_detail_features.feature_id', '=', 'vehicle_features.id')
                    ->select('vehicle_features.name', 'vehicle_features.id')
                    ->distinct()
                    ->orderBy('vehicle_features.name')
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Type statistics retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle type not found'
            ], 404);
        }
    }

    /**
     * Get available type bodies (replaces old body-types endpoint).
     *
     * @OA\Get(
     *     path="/api/vehicle/type-bodies",
     *     operationId="getTypeBodies",
     *     tags={"Vehicle Types"},
     *     summary="Get available type bodies",
     *     description="Returns list of all type bodies in the system",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="active_only",
     *         in="query",
     *         description="Filter only active type bodies",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by type body name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string", example="Type bodies retrieved successfully")
     *         )
     *     )
     * )
     */
    public function typeBodies(Request $request): JsonResponse
    {
        $request->validate([
            'active_only' => 'boolean',
            'search' => 'string|max:100',
        ]);

        $query = VehicleTypeBody::query();

        // Filter by active status
        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        // Order by name
        $typeBodies = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $typeBodies,
            'message' => 'Type bodies retrieved successfully'
        ]);
    }
}