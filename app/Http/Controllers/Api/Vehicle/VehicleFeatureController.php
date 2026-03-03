<?php

namespace App\Http\Controllers\Api\Vehicle;

use App\Http\Controllers\Controller;
use App\Models\VehicleFeature;
use App\Models\VehicleDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class VehicleFeatureController extends Controller
{
    /**
     * Display a listing of vehicle features.
     *
     * @OA\Get(
     *     path="/api/vehicle/features",
     *     operationId="getVehicleFeatures",
     *     tags={"Vehicle Features"},
     *     summary="Get all vehicle features",
     *     description="Returns list of all vehicle features with optional filters",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="active_only",
     *         in="query",
     *         description="Filter only active features",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by feature name or description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category (safety, comfort, technology, exterior, interior)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field (name, created_at, popularity)",
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
     *         name="with_counts",
     *         in="query",
     *         description="Include vehicle details count",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Vehicle features retrieved successfully")
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
            'active_only' => 'boolean',
            'search' => 'string|max:100',
            'category' => 'string|max:50',
            'sort_by' => 'string|in:name,created_at,popularity',
            'sort_order' => 'string|in:asc,desc',
            'per_page' => 'integer|min:1|max:100',
            'with_counts' => 'boolean'
        ]);

        $query = VehicleFeature::query();

        // Filter by active status
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Search by name or description
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('description', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Filter by category (if you have category field, otherwise remove this)
        // if ($request->has('category') && $request->category) {
        //     $query->where('category', $request->category);
        // }

        // Sort
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'popularity') {
            $query->withCount('details')
                ->orderBy('details_count', $sortOrder)
                ->orderBy('name', 'asc');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Include counts if requested
        if ($request->boolean('with_counts')) {
            $query->withCount('details');
        }

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $features = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $features,
            'message' => 'Vehicle features retrieved successfully'
        ]);
    }

    /**
     * Store a newly created vehicle feature.
     *
     * @OA\Post(
     *     path="/api/vehicle/features",
     *     operationId="createVehicleFeature",
     *     tags={"Vehicle Features"},
     *     summary="Create a new vehicle feature",
     *     description="Create a new vehicle feature with provided data",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Apple CarPlay"),
     *             @OA\Property(property="description", type="string", example="Apple CarPlay & Android Auto Integration"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="category", type="string", example="technology")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Vehicle feature created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleFeature"),
     *             @OA\Property(property="message", type="string", example="Vehicle feature created successfully")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to create vehicle features")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:vehicle_features,name',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            // 'category' => 'nullable|string|max:50' // if you have category field
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $feature = VehicleFeature::create($request->only(['name', 'description', 'is_active']));

        return response()->json([
            'success' => true,
            'data' => $feature,
            'message' => 'Vehicle feature created successfully'
        ], 201);
    }

    /**
     * Display the specified vehicle feature.
     *
     * @OA\Get(
     *     path="/api/vehicle/features/{id}",
     *     operationId="getVehicleFeature",
     *     tags={"Vehicle Features"},
     *     summary="Get specific vehicle feature",
     *     description="Returns vehicle feature details by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Feature ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="with_details",
     *         in="query",
     *         description="Include vehicle details",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="details_limit",
     *         in="query",
     *         description="Limit for vehicle details",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleFeature"),
     *             @OA\Property(property="message", type="string", example="Vehicle feature retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle feature not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle feature not found")
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $feature = VehicleFeature::findOrFail($id);

            // Include details if requested
            if (request()->boolean('with_details')) {
                $limit = request()->get('details_limit', 10);
                $feature->load(['details' => function ($query) use ($limit) {
                    $query->with(['brand', 'model'])
                        ->latest()
                        ->limit($limit);
                }]);
            }

            // Add counts if not already loaded
            if (!isset($feature->details_count)) {
                $feature->details_count = $feature->details()->count();
            }

            return response()->json([
                'success' => true,
                'data' => $feature,
                'message' => 'Vehicle feature retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle feature not found'
            ], 404);
        }
    }

    /**
     * Update the specified vehicle feature.
     *
     * @OA\Put(
     *     path="/api/vehicle/features/{id}",
     *     operationId="updateVehicleFeature",
     *     tags={"Vehicle Features"},
     *     summary="Update vehicle feature",
     *     description="Update vehicle feature details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Feature ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Apple CarPlay"),
     *             @OA\Property(property="description", type="string", example="Apple CarPlay & Android Auto Integration - Updated"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="category", type="string", example="technology")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle feature updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleFeature"),
     *             @OA\Property(property="message", type="string", example="Vehicle feature updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle feature not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle feature not found")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to update vehicle features")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $feature = VehicleFeature::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100|unique:vehicle_features,name,' . $id,
                'description' => 'nullable|string|max:500',
                'is_active' => 'boolean',
                // 'category' => 'nullable|string|max:50' // if you have category field
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $feature->update($request->only(['name', 'description', 'is_active']));

            return response()->json([
                'success' => true,
                'data' => $feature,
                'message' => 'Vehicle feature updated successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle feature not found'
            ], 404);
        }
    }

    /**
     * Remove the specified vehicle feature.
     *
     * @OA\Delete(
     *     path="/api/vehicle/features/{id}",
     *     operationId="deleteVehicleFeature",
     *     tags={"Vehicle Features"},
     *     summary="Delete vehicle feature",
     *     description="Delete vehicle feature by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Feature ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle feature deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle feature deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle feature not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle feature not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - No permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to delete vehicle features")
     *         )
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $feature = VehicleFeature::findOrFail($id);

            // Detach all related details before deleting
            $feature->details()->detach();
            
            $feature->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle feature deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle feature not found'
            ], 404);
        }
    }

    /**
     * Get all vehicle details for a specific feature.
     *
     * @OA\Get(
     *     path="/api/vehicle/features/{id}/details",
     *     operationId="getFeatureDetails",
     *     tags={"Vehicle Features"},
     *     summary="Get vehicle details for a feature",
     *     description="Returns all vehicle details for a specific feature",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Feature ID",
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
     *         name="brand_id",
     *         in="query",
     *         description="Filter by brand ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         description="Filter by model ID",
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
     *         description="Vehicle feature not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle feature not found")
     *         )
     *     )
     * )
     */
    public function details(string $id): JsonResponse
    {
        try {
            $feature = VehicleFeature::findOrFail($id);

            $query = $feature->details()
                ->with(['brand', 'model', 'type']);

            // Filter by year
            if (request()->has('year') && request()->year) {
                $query->where('year', request()->year);
            }

            // Filter by brand
            if (request()->has('brand_id') && request()->brand_id) {
                $query->where('brand_id', request()->brand_id);
            }

            // Filter by model
            if (request()->has('model_id') && request()->model_id) {
                $query->where('model_id', request()->model_id);
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
                'message' => 'Vehicle feature not found'
            ], 404);
        }
    }

    /**
     * Get statistics for a specific feature.
     *
     * @OA\Get(
     *     path="/api/vehicle/features/{id}/stats",
     *     operationId="getFeatureStats",
     *     tags={"Vehicle Features"},
     *     summary="Get feature statistics",
     *     description="Returns statistics for a vehicle feature",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Feature ID",
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
     *             @OA\Property(property="message", type="string", example="Feature statistics retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle feature not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle feature not found")
     *         )
     *     )
     * )
     */
    public function stats(string $id): JsonResponse
    {
        try {
            $feature = VehicleFeature::findOrFail($id);

            $stats = [
                'total_vehicles' => $feature->details()->count(),
                'active_vehicles' => $feature->details()->where('is_active', true)->count(),
                'years_available' => $feature->details()
                    ->select('year')
                    ->distinct()
                    ->orderBy('year', 'desc')
                    ->pluck('year'),
                'brand_distribution' => $feature->details()
                    ->join('vehicle_brands', 'vehicle_details.brand_id', '=', 'vehicle_brands.id')
                    ->select('vehicle_brands.name', 'vehicle_brands.id', DB::raw('COUNT(*) as count'))
                    ->groupBy('vehicle_brands.id', 'vehicle_brands.name')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
                'model_distribution' => $feature->details()
                    ->join('vehicle_models', 'vehicle_details.model_id', '=', 'vehicle_models.id')
                    ->select('vehicle_models.name', 'vehicle_models.id', DB::raw('COUNT(*) as count'))
                    ->groupBy('vehicle_models.id', 'vehicle_models.name')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
                'year_distribution' => $feature->details()
                    ->select('year', DB::raw('COUNT(*) as count'))
                    ->groupBy('year')
                    ->orderBy('year', 'desc')
                    ->get(),
                'popular_with_transmissions' => $feature->details()
                    ->join('transmission_types', 'vehicle_details.transmission_id', '=', 'transmission_types.id')
                    ->select('transmission_types.name', 'transmission_types.id', DB::raw('COUNT(*) as count'))
                    ->groupBy('transmission_types.id', 'transmission_types.name')
                    ->orderBy('count', 'desc')
                    ->limit(5)
                    ->get(),
                'average_year' => $feature->details()->avg('year'),
                'newest_vehicle' => $feature->details()
                    ->with(['brand', 'model'])
                    ->orderBy('year', 'desc')
                    ->first(),
                'oldest_vehicle' => $feature->details()
                    ->with(['brand', 'model'])
                    ->orderBy('year', 'asc')
                    ->first()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Feature statistics retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle feature not found'
            ], 404);
        }
    }

    /**
     * Get available features (for dropdowns).
     *
     * @OA\Get(
     *     path="/api/vehicle/features/list",
     *     operationId="getFeaturesList",
     *     tags={"Vehicle Features"},
     *     summary="Get features list",
     *     description="Returns simplified list of features (for dropdowns)",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="ABS"),
     *                     @OA\Property(property="description", type="string", example="Anti-lock Braking System")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Vehicle features list retrieved successfully")
     *         )
     *     )
     * )
     */
    public function list(): JsonResponse
    {
        $features = VehicleFeature::active()
            ->select('id', 'name', 'description')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $features,
            'message' => 'Vehicle features list retrieved successfully'
        ]);
    }

    /**
     * Get popular features (most used).
     *
     * @OA\Get(
     *     path="/api/vehicle/features/popular",
     *     operationId="getPopularFeatures",
     *     tags={"Vehicle Features"},
     *     summary="Get popular features",
     *     description="Returns most popular features based on usage count",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of features to return",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="min_count",
     *         in="query",
     *         description="Minimum usage count",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Popular features retrieved successfully")
     *         )
     *     )
     * )
     */
    public function popular(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'integer|min:1|max:50',
            'min_count' => 'integer|min:0'
        ]);

        $limit = $request->get('limit', 10);
        $minCount = $request->get('min_count', 1);

        $popularFeatures = VehicleFeature::withCount('details')
            ->having('details_count', '>=', $minCount)
            ->orderBy('details_count', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $popularFeatures,
            'message' => 'Popular features retrieved successfully'
        ]);
    }

    /**
     * Bulk create features.
     *
     * @OA\Post(
     *     path="/api/vehicle/features/bulk",
     *     operationId="bulkCreateFeatures",
     *     tags={"Vehicle Features"},
     *     summary="Bulk create features",
     *     description="Create multiple features at once",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"features"},
     *             @OA\Property(
     *                 property="features",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="Wireless Charging"),
     *                     @OA\Property(property="description", type="string", example="Wireless smartphone charging pad"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Features created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Features created successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'features' => 'required|array|min:1',
            'features.*.name' => 'required|string|max:100',
            'features.*.description' => 'nullable|string|max:500',
            'features.*.is_active' => 'boolean'
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

            $createdFeatures = [];
            $skippedFeatures = [];

            foreach ($request->features as $featureData) {
                // Check if feature already exists
                $existing = VehicleFeature::where('name', $featureData['name'])->first();
                
                if ($existing) {
                    $skippedFeatures[] = [
                        'name' => $featureData['name'],
                        'reason' => 'Already exists'
                    ];
                    continue;
                }

                $feature = VehicleFeature::create([
                    'name' => $featureData['name'],
                    'description' => $featureData['description'] ?? null,
                    'is_active' => $featureData['is_active'] ?? true
                ]);

                $createdFeatures[] = $feature;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'created' => count($createdFeatures),
                    'skipped' => count($skippedFeatures),
                    'features' => $createdFeatures,
                    'skipped_details' => $skippedFeatures
                ],
                'message' => 'Features created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create features',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}