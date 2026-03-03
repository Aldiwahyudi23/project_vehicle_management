<?php

namespace App\Http\Controllers\Api\Vehicle;

use App\Http\Controllers\Controller;
use App\Models\VehicleOrigin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class VehicleOriginController extends Controller
{
    /**
     * Display a listing of vehicle origins.
     *
     * @OA\Get(
     *     path="/api/vehicle/origins",
     *     operationId="getVehicleOrigins",
     *     tags={"Vehicle Origins"},
     *     summary="Get all vehicle origins",
     *     description="Returns list of all vehicle origins with optional filters",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="active_only",
     *         in="query",
     *         description="Filter only active origins",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by origin name",
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
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Vehicle origins retrieved successfully")
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
            'sort_by' => 'string|in:name,created_at',
            'sort_order' => 'string|in:asc,desc',
            'per_page' => 'integer|min:1|max:100'
        ]);

        $query = VehicleOrigin::query();

        // Filter by active status
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('description', 'LIKE', '%' . $request->search . '%');
        }

        // Sort
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $origins = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $origins,
            'message' => 'Vehicle origins retrieved successfully'
        ]);
    }

    /**
     * Store a newly created vehicle origin.
     *
     * @OA\Post(
     *     path="/api/vehicle/origins",
     *     operationId="createVehicleOrigin",
     *     tags={"Vehicle Origins"},
     *     summary="Create a new vehicle origin",
     *     description="Create a new vehicle origin with provided data",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="CKD"),
     *             @OA\Property(property="description", type="string", example="Completely Knocked Down - Assembled locally"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Vehicle origin created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleOrigin"),
     *             @OA\Property(property="message", type="string", example="Vehicle origin created successfully")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to create vehicle origins")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:vehicle_origins,name',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $origin = VehicleOrigin::create($request->only(['name', 'description', 'is_active']));

        return response()->json([
            'success' => true,
            'data' => $origin,
            'message' => 'Vehicle origin created successfully'
        ], 201);
    }

    /**
     * Display the specified vehicle origin.
     *
     * @OA\Get(
     *     path="/api/vehicle/origins/{id}",
     *     operationId="getVehicleOrigin",
     *     tags={"Vehicle Origins"},
     *     summary="Get specific vehicle origin",
     *     description="Returns vehicle origin details by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Origin ID",
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
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleOrigin"),
     *             @OA\Property(property="message", type="string", example="Vehicle origin retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle origin not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle origin not found")
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $origin = VehicleOrigin::findOrFail($id);

            // Include details if requested
            if (request()->boolean('with_details')) {
                $limit = request()->get('details_limit', 10);
                $origin->load(['details' => function ($query) use ($limit) {
                    $query->with(['brand', 'model'])
                        ->latest()
                        ->limit($limit);
                }]);
            }

            return response()->json([
                'success' => true,
                'data' => $origin,
                'message' => 'Vehicle origin retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle origin not found'
            ], 404);
        }
    }

    /**
     * Update the specified vehicle origin.
     *
     * @OA\Put(
     *     path="/api/vehicle/origins/{id}",
     *     operationId="updateVehicleOrigin",
     *     tags={"Vehicle Origins"},
     *     summary="Update vehicle origin",
     *     description="Update vehicle origin details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Origin ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="CBU"),
     *             @OA\Property(property="description", type="string", example="Completely Built Up - Imported fully assembled"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle origin updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleOrigin"),
     *             @OA\Property(property="message", type="string", example="Vehicle origin updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle origin not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle origin not found")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to update vehicle origins")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $origin = VehicleOrigin::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:50|unique:vehicle_origins,name,' . $id,
                'description' => 'nullable|string|max:255',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $origin->update($request->only(['name', 'description', 'is_active']));

            return response()->json([
                'success' => true,
                'data' => $origin,
                'message' => 'Vehicle origin updated successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle origin not found'
            ], 404);
        }
    }

    /**
     * Remove the specified vehicle origin.
     *
     * @OA\Delete(
     *     path="/api/vehicle/origins/{id}",
     *     operationId="deleteVehicleOrigin",
     *     tags={"Vehicle Origins"},
     *     summary="Delete vehicle origin",
     *     description="Delete vehicle origin by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Origin ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle origin deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle origin deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle origin not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle origin not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - No permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to delete vehicle origins")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete - Has related data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot delete origin because it has associated vehicle details")
     *         )
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $origin = VehicleOrigin::findOrFail($id);

            // Check if origin has related details
            if ($origin->details()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete origin because it has associated vehicle details'
                ], 422);
            }

            $origin->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle origin deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle origin not found'
            ], 404);
        }
    }

    /**
     * Get all vehicle details for a specific origin.
     *
     * @OA\Get(
     *     path="/api/vehicle/origins/{id}/details",
     *     operationId="getOriginDetails",
     *     tags={"Vehicle Origins"},
     *     summary="Get vehicle details for an origin",
     *     description="Returns all vehicle details for a specific origin",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Origin ID",
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
     *         name="brand_id",
     *         in="query",
     *         description="Filter by brand ID",
     *         required=false,
     *         @OA\Schema(type="integer")
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
     *         description="Vehicle origin not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle origin not found")
     *         )
     *     )
     * )
     */
    public function details(string $id): JsonResponse
    {
        try {
            $origin = VehicleOrigin::findOrFail($id);

            $query = $origin->details()
                ->with(['brand', 'model', 'type', 'transmission']);

            // Filter by year
            if (request()->has('year') && request()->year) {
                $query->where('year', request()->year);
            }

            // Filter by brand
            if (request()->has('brand_id') && request()->brand_id) {
                $query->where('brand_id', request()->brand_id);
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
                'message' => 'Vehicle origin not found'
            ], 404);
        }
    }

    /**
     * Get statistics for a specific origin.
     *
     * @OA\Get(
     *     path="/api/vehicle/origins/{id}/stats",
     *     operationId="getOriginStats",
     *     tags={"Vehicle Origins"},
     *     summary="Get origin statistics",
     *     description="Returns statistics for a vehicle origin",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Origin ID",
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
     *             @OA\Property(property="message", type="string", example="Origin statistics retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle origin not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle origin not found")
     *         )
     *     )
     * )
     */
    public function stats(string $id): JsonResponse
    {
        try {
            $origin = VehicleOrigin::findOrFail($id);

            $stats = [
                'total_details' => $origin->details()->count(),
                'active_details' => $origin->details()->where('is_active', true)->count(),
                'years_available' => $origin->details()
                    ->select('year')
                    ->distinct()
                    ->orderBy('year', 'desc')
                    ->pluck('year'),
                'brand_distribution' => $origin->details()
                    ->join('vehicle_brands', 'vehicle_details.brand_id', '=', 'vehicle_brands.id')
                    ->select('vehicle_brands.name', 'vehicle_brands.id', DB::raw('COUNT(*) as count'))
                    ->groupBy('vehicle_brands.id', 'vehicle_brands.name')
                    ->orderBy('count', 'desc')
                    ->get(),
                'model_distribution' => $origin->details()
                    ->join('vehicle_models', 'vehicle_details.model_id', '=', 'vehicle_models.id')
                    ->select('vehicle_models.name', 'vehicle_models.id', DB::raw('COUNT(*) as count'))
                    ->groupBy('vehicle_models.id', 'vehicle_models.name')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
                'transmission_distribution' => $origin->details()
                    ->join('transmission_types', 'vehicle_details.transmission_id', '=', 'transmission_types.id')
                    ->select('transmission_types.name', 'transmission_types.id', DB::raw('COUNT(*) as count'))
                    ->groupBy('transmission_types.id', 'transmission_types.name')
                    ->orderBy('count', 'desc')
                    ->get(),
                'fuel_type_distribution' => $origin->details()
                    ->select('fuel_type', DB::raw('COUNT(*) as count'))
                    ->groupBy('fuel_type')
                    ->orderBy('count', 'desc')
                    ->get(),
                'cc_distribution' => $origin->details()
                    ->select(DB::raw('ROUND(cc/100)*100 as cc_range'), DB::raw('COUNT(*) as count'))
                    ->whereNotNull('cc')
                    ->groupBy('cc_range')
                    ->orderBy('cc_range')
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Origin statistics retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle origin not found'
            ], 404);
        }
    }

    /**
     * Get available origins (for dropdowns).
     *
     * @OA\Get(
     *     path="/api/vehicle/origins/list",
     *     operationId="getOriginsList",
     *     tags={"Vehicle Origins"},
     *     summary="Get origins list",
     *     description="Returns simplified list of origins (for dropdowns)",
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
     *                     @OA\Property(property="name", type="string", example="CKD"),
     *                     @OA\Property(property="description", type="string", example="Completely Knocked Down")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Vehicle origins list retrieved successfully")
     *         )
     *     )
     * )
     */
    public function list(): JsonResponse
    {
        $origins = VehicleOrigin::active()
            ->select('id', 'name', 'description')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $origins,
            'message' => 'Vehicle origins list retrieved successfully'
        ]);
    }

    /**
     * Get summary statistics for all origins.
     *
     * @OA\Get(
     *     path="/api/vehicle/origins/summary",
     *     operationId="getOriginsSummary",
     *     tags={"Vehicle Origins"},
     *     summary="Get origins summary",
     *     description="Returns summary statistics for all origins",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Origins summary retrieved successfully")
     *         )
     *     )
     * )
     */
    public function summary(): JsonResponse
    {
        $summary = VehicleOrigin::withCount(['details' => function ($query) {
                $query->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();

        $totalVehicles = VehicleOrigin::join('vehicle_details', 'vehicle_origins.id', '=', 'vehicle_details.origin_id')
            ->where('vehicle_details.is_active', true)
            ->count();

        $result = [
            'total_origins' => $summary->count(),
            'total_vehicles' => $totalVehicles,
            'origins' => $summary,
            'distribution' => $summary->map(function ($origin) use ($totalVehicles) {
                return [
                    'id' => $origin->id,
                    'name' => $origin->name,
                    'count' => $origin->details_count,
                    'percentage' => $totalVehicles > 0 ? round(($origin->details_count / $totalVehicles) * 100, 2) : 0
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Origins summary retrieved successfully'
        ]);
    }
}