<?php

namespace App\Http\Controllers\Api\Vehicle;

use App\Http\Controllers\Controller;
use App\Models\TransmissionType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class TransmissionTypeController extends Controller
{
    /**
     * Display a listing of transmission types.
     *
     * @OA\Get(
     *     path="/api/vehicle/transmissions",
     *     operationId="getTransmissionTypes",
     *     tags={"Transmission Types"},
     *     summary="Get all transmission types",
     *     description="Returns list of all transmission types with optional filters",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="active_only",
     *         in="query",
     *         description="Filter only active transmission types",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by transmission name",
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
     *             @OA\Property(property="message", type="string", example="Transmission types retrieved successfully")
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

        $query = TransmissionType::query();

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
        $transmissions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transmissions,
            'message' => 'Transmission types retrieved successfully'
        ]);
    }

    /**
     * Store a newly created transmission type.
     *
     * @OA\Post(
     *     path="/api/vehicle/transmissions",
     *     operationId="createTransmissionType",
     *     tags={"Transmission Types"},
     *     summary="Create a new transmission type",
     *     description="Create a new transmission type with provided data",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="CVT"),
     *             @OA\Property(property="description", type="string", example="Continuously Variable Transmission"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transmission type created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/TransmissionType"),
     *             @OA\Property(property="message", type="string", example="Transmission type created successfully")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to create transmission types")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:transmission_types,name',
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

        $transmission = TransmissionType::create($request->only(['name', 'description', 'is_active']));

        return response()->json([
            'success' => true,
            'data' => $transmission,
            'message' => 'Transmission type created successfully'
        ], 201);
    }

    /**
     * Display the specified transmission type.
     *
     * @OA\Get(
     *     path="/api/vehicle/transmissions/{id}",
     *     operationId="getTransmissionType",
     *     tags={"Transmission Types"},
     *     summary="Get specific transmission type",
     *     description="Returns transmission type details by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Transmission Type ID",
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
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/TransmissionType"),
     *             @OA\Property(property="message", type="string", example="Transmission type retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transmission type not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Transmission type not found")
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $transmission = TransmissionType::findOrFail($id);

            // Include details if requested
            if (request()->boolean('with_details')) {
                $transmission->load(['details' => function ($query) {
                    $query->limit(10)->latest();
                }]);
            }

            return response()->json([
                'success' => true,
                'data' => $transmission,
                'message' => 'Transmission type retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transmission type not found'
            ], 404);
        }
    }

    /**
     * Update the specified transmission type.
     *
     * @OA\Put(
     *     path="/api/vehicle/transmissions/{id}",
     *     operationId="updateTransmissionType",
     *     tags={"Transmission Types"},
     *     summary="Update transmission type",
     *     description="Update transmission type details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Transmission Type ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="CVT"),
     *             @OA\Property(property="description", type="string", example="Continuously Variable Transmission - Updated"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transmission type updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/TransmissionType"),
     *             @OA\Property(property="message", type="string", example="Transmission type updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transmission type not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Transmission type not found")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to update transmission types")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $transmission = TransmissionType::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:50|unique:transmission_types,name,' . $id,
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

            $transmission->update($request->only(['name', 'description', 'is_active']));

            return response()->json([
                'success' => true,
                'data' => $transmission,
                'message' => 'Transmission type updated successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transmission type not found'
            ], 404);
        }
    }

    /**
     * Remove the specified transmission type.
     *
     * @OA\Delete(
     *     path="/api/vehicle/transmissions/{id}",
     *     operationId="deleteTransmissionType",
     *     tags={"Transmission Types"},
     *     summary="Delete transmission type",
     *     description="Delete transmission type by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Transmission Type ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transmission type deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transmission type deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transmission type not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Transmission type not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - No permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to delete transmission types")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete - Has related data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot delete transmission type because it has associated vehicle details")
     *         )
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $transmission = TransmissionType::findOrFail($id);

            // Check if transmission has related details
            if ($transmission->details()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete transmission type because it has associated vehicle details'
                ], 422);
            }

            $transmission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Transmission type deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transmission type not found'
            ], 404);
        }
    }

    /**
     * Get all vehicle details for a specific transmission type.
     *
     * @OA\Get(
     *     path="/api/vehicle/transmissions/{id}/details",
     *     operationId="getTransmissionDetails",
     *     tags={"Transmission Types"},
     *     summary="Get vehicle details for a transmission",
     *     description="Returns all vehicle details for a specific transmission type",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Transmission Type ID",
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
     *         description="Transmission type not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Transmission type not found")
     *         )
     *     )
     * )
     */
    public function details(string $id): JsonResponse
    {
        try {
            $transmission = TransmissionType::findOrFail($id);

            $query = $transmission->details()
                ->with(['brand', 'model', 'type']);

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
                'message' => 'Transmission type not found'
            ], 404);
        }
    }

    /**
     * Get statistics for a specific transmission type.
     *
     * @OA\Get(
     *     path="/api/vehicle/transmissions/{id}/stats",
     *     operationId="getTransmissionStats",
     *     tags={"Transmission Types"},
     *     summary="Get transmission statistics",
     *     description="Returns statistics for a transmission type",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Transmission Type ID",
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
     *             @OA\Property(property="message", type="string", example="Transmission statistics retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transmission type not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Transmission type not found")
     *         )
     *     )
     * )
     */
    public function stats(string $id): JsonResponse
    {
        try {
            $transmission = TransmissionType::findOrFail($id);

            $stats = [
                'total_details' => $transmission->details()->count(),
                'active_details' => $transmission->details()->where('is_active', true)->count(),
                'years_available' => $transmission->details()
                    ->select('year')
                    ->distinct()
                    ->orderBy('year', 'desc')
                    ->pluck('year'),
                'popular_brands' => $transmission->details()
                    ->join('vehicle_brands', 'vehicle_details.brand_id', '=', 'vehicle_brands.id')
                    ->select('vehicle_brands.name', 'vehicle_brands.id', DB::raw('COUNT(*) as count'))
                    ->groupBy('vehicle_brands.id', 'vehicle_brands.name')
                    ->orderBy('count', 'desc')
                    ->limit(5)
                    ->get(),
                'popular_models' => $transmission->details()
                    ->join('vehicle_models', 'vehicle_details.model_id', '=', 'vehicle_models.id')
                    ->select('vehicle_models.name', 'vehicle_models.id', DB::raw('COUNT(*) as count'))
                    ->groupBy('vehicle_models.id', 'vehicle_models.name')
                    ->orderBy('count', 'desc')
                    ->limit(5)
                    ->get(),
                'fuel_type_distribution' => $transmission->details()
                    ->select('fuel_type', DB::raw('COUNT(*) as count'))
                    ->groupBy('fuel_type')
                    ->orderBy('count', 'desc')
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Transmission statistics retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transmission type not found'
            ], 404);
        }
    }

    /**
     * Get available transmission types (for dropdowns).
     *
     * @OA\Get(
     *     path="/api/vehicle/transmissions/list",
     *     operationId="getTransmissionsList",
     *     tags={"Transmission Types"},
     *     summary="Get transmission types list",
     *     description="Returns simplified list of transmission types (for dropdowns)",
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
     *                     @OA\Property(property="name", type="string", example="MT"),
     *                     @OA\Property(property="description", type="string", example="Manual Transmission")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Transmission types list retrieved successfully")
     *         )
     *     )
     * )
     */
    public function list(): JsonResponse
    {
        $transmissions = TransmissionType::active()
            ->select('id', 'name', 'description')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transmissions,
            'message' => 'Transmission types list retrieved successfully'
        ]);
    }
}