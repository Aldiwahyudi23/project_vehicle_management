<?php

namespace App\Http\Controllers\Api\Vehicle;

use App\Http\Controllers\Controller;
use App\Models\VehicleBrand;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class VehicleBrandController extends Controller
{
    /**
     * Display a listing of vehicle brands.
     *
     * @OA\Get(
     *     path="/api/vehicle/brands",
     *     operationId="getVehicleBrands",
     *     tags={"Vehicle Brands"},
     *     summary="Get all vehicle brands",
     *     description="Returns list of all vehicle brands with optional filters",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="active_only",
     *         in="query",
     *         description="Filter only active brands",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by brand name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="country",
     *         in="query",
     *         description="Filter by country",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/VehicleBrand")),
     *             @OA\Property(property="message", type="string", example="Vehicle brands retrieved successfully")
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
            'country' => 'string|max:50',
            'per_page' => 'integer|min:1|max:100'
        ]);

        $query = VehicleBrand::query();

        // Filter by active status
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        // Filter by country
        if ($request->has('country') && $request->country) {
            $query->where('country', $request->country);
        }

        // Order by name
        $query->orderBy('name');

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $brands = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $brands,
            'message' => 'Vehicle brands retrieved successfully'
        ]);
    }

    /**
     * Store a newly created vehicle brand.
     *
     * @OA\Post(
     *     path="/api/vehicle/brands",
     *     operationId="createVehicleBrand",
     *     tags={"Vehicle Brands"},
     *     summary="Create a new vehicle brand",
     *     description="Create a new vehicle brand with provided data",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Toyota"),
     *             @OA\Property(property="country", type="string", example="Jepang"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Vehicle brand created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleBrand"),
     *             @OA\Property(property="message", type="string", example="Vehicle brand created successfully")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to create vehicle brands")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:vehicle_brands,name',
            'country' => 'nullable|string|max:50',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $brand = VehicleBrand::create($request->only(['name', 'country', 'is_active']));

        return response()->json([
            'success' => true,
            'data' => $brand,
            'message' => 'Vehicle brand created successfully'
        ], 201);
    }

    /**
     * Display the specified vehicle brand.
     *
     * @OA\Get(
     *     path="/api/vehicle/brands/{id}",
     *     operationId="getVehicleBrand",
     *     tags={"Vehicle Brands"},
     *     summary="Get specific vehicle brand",
     *     description="Returns vehicle brand details by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Brand ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleBrand"),
     *             @OA\Property(property="message", type="string", example="Vehicle brand retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle brand not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle brand not found")
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        $brand = VehicleBrand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle brand not found'
            ], 404);
        }

        // Load relationships if needed
        if (request()->has('with')) {
            $relations = explode(',', request()->get('with'));
            $brand->load($relations);
        }

        return response()->json([
            'success' => true,
            'data' => $brand,
            'message' => 'Vehicle brand retrieved successfully'
        ]);
    }

    /**
     * Update the specified vehicle brand.
     *
     * @OA\Put(
     *     path="/api/vehicle/brands/{id}",
     *     operationId="updateVehicleBrand",
     *     tags={"Vehicle Brands"},
     *     summary="Update vehicle brand",
     *     description="Update vehicle brand details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Brand ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Toyota"),
     *             @OA\Property(property="country", type="string", example="Jepang"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle brand updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleBrand"),
     *             @OA\Property(property="message", type="string", example="Vehicle brand updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle brand not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle brand not found")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to update vehicle brands")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $brand = VehicleBrand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle brand not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:vehicle_brands,name,' . $id,
            'country' => 'nullable|string|max:50',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $brand->update($request->only(['name', 'country', 'is_active']));

        return response()->json([
            'success' => true,
            'data' => $brand,
            'message' => 'Vehicle brand updated successfully'
        ]);
    }

    /**
     * Remove the specified vehicle brand.
     *
     * @OA\Delete(
     *     path="/api/vehicle/brands/{id}",
     *     operationId="deleteVehicleBrand",
     *     tags={"Vehicle Brands"},
     *     summary="Delete vehicle brand",
     *     description="Delete vehicle brand by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Brand ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle brand deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle brand deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle brand not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle brand not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - No permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to delete vehicle brands")
     *         )
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $brand = VehicleBrand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle brand not found'
            ], 404);
        }

        // Check if brand has related models
        if ($brand->models()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete brand because it has associated vehicle models'
            ], 422);
        }

        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle brand deleted successfully'
        ]);
    }

    /**
     * Get all models for a specific brand.
     *
     * @OA\Get(
     *     path="/api/vehicle/brands/{id}/models",
     *     operationId="getBrandModels",
     *     tags={"Vehicle Brands"},
     *     summary="Get models for a brand",
     *     description="Returns all vehicle models for a specific brand",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Brand ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/VehicleModel")),
     *             @OA\Property(property="message", type="string", example="Vehicle models retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle brand not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle brand not found")
     *         )
     *     )
     * )
     */
    public function models(string $id): JsonResponse
    {
        $brand = VehicleBrand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle brand not found'
            ], 404);
        }

        $models = $brand->models()->withCount('details')->get();

        return response()->json([
            'success' => true,
            'data' => $models,
            'message' => 'Vehicle models retrieved successfully'
        ]);
    }

    /**
     * Get all vehicle details for a specific brand.
     *
     * @OA\Get(
     *     path="/api/vehicle/brands/{id}/details",
     *     operationId="getBrandDetails",
     *     tags={"Vehicle Brands"},
     *     summary="Get vehicle details for a brand",
     *     description="Returns all vehicle details for a specific brand",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Brand ID",
     *         required=true,
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
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/VehicleDetail")),
     *             @OA\Property(property="message", type="string", example="Vehicle details retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle brand not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle brand not found")
     *         )
     *     )
     * )
     */
    public function details(string $id): JsonResponse
    {
        $brand = VehicleBrand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle brand not found'
            ], 404);
        }

        $perPage = request()->get('per_page', 15);
        $details = $brand->details()
            ->with(['model', 'type', 'transmission', 'origin'])
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $details,
            'message' => 'Vehicle details retrieved successfully'
        ]);
    }

    /**
     * Get statistics for a specific brand.
     *
     * @OA\Get(
     *     path="/api/vehicle/brands/{id}/stats",
     *     operationId="getBrandStats",
     *     tags={"Vehicle Brands"},
     *     summary="Get brand statistics",
     *     description="Returns statistics for a vehicle brand",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Brand ID",
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
     *             @OA\Property(property="message", type="string", example="Brand statistics retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle brand not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle brand not found")
     *         )
     *     )
     * )
     */
    public function stats(string $id): JsonResponse
    {
        $brand = VehicleBrand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle brand not found'
            ], 404);
        }

        $stats = [
            'total_models' => $brand->models()->count(),
            'active_models' => $brand->models()->where('is_active', true)->count(),
            'total_details' => $brand->details()->count(),
            'active_details' => $brand->details()->where('is_active', true)->count(),
            'latest_model' => $brand->models()->latest()->first(),
            'models_by_year' => $brand->models()
                ->selectRaw('YEAR(created_at) as year, COUNT(*) as count')
                ->groupBy('year')
                ->orderBy('year', 'desc')
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Brand statistics retrieved successfully'
        ]);
    }
}