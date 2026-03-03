<?php

namespace App\Http\Controllers\Api\Vehicle;

use App\Http\Controllers\Controller;
use App\Models\VehicleDetail;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use App\Models\TransmissionType;
use App\Models\VehicleOrigin;
use App\Models\VehicleFeature;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class VehicleDetailController extends Controller
{
    /**
     * Display a listing of vehicle details.
     *
     * @OA\Get(
     *     path="/api/vehicle/details",
     *     operationId="getVehicleDetails",
     *     tags={"Vehicle Details"},
     *     summary="Get all vehicle details",
     *     description="Returns list of all vehicle details with comprehensive filters",
     *     security={{"sanctum":{}}},
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
     *         name="type_id",
     *         in="query",
     *         description="Filter by type ID",
     *         required=false,
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
     *         name="year_from",
     *         in="query",
     *         description="Filter by year from",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="year_to",
     *         in="query",
     *         description="Filter by year to",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="min_cc",
     *         in="query",
     *         description="Filter by minimum CC",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="max_cc",
     *         in="query",
     *         description="Filter by maximum CC",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="fuel_type",
     *         in="query",
     *         description="Filter by fuel type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="transmission_id",
     *         in="query",
     *         description="Filter by transmission ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="origin_id",
     *         in="query",
     *         description="Filter by origin ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="feature_id",
     *         in="query",
     *         description="Filter by feature ID",
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
     *         name="search",
     *         in="query",
     *         description="Search by engine type or description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="generation",
     *         in="query",
     *         description="Filter by generation",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="specification_key",
     *         in="query",
     *         description="Filter by specification key",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="specification_value",
     *         in="query",
     *         description="Filter by specification value",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field (year, cc, created_at)",
     *         required=false,
     *         @OA\Schema(type="string", default="year")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order (asc, desc)",
     *         required=false,
     *         @OA\Schema(type="string", default="desc")
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
     *         description="Include relationships (brand, model, type, transmission, origin, features)",
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
     *             @OA\Property(property="message", type="string", example="Vehicle details retrieved successfully")
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
            'brand_id' => 'nullable|integer|exists:vehicle_brands,id',
            'model_id' => 'nullable|integer|exists:vehicle_models,id',
            'type_id' => 'nullable|integer|exists:vehicle_types,id',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
            'year_from' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
            'year_to' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
            'min_cc' => 'nullable|integer|min:0',
            'max_cc' => 'nullable|integer|min:0',
            'fuel_type' => 'nullable|string|max:50',
            'transmission_id' => 'nullable|integer|exists:transmission_types,id',
            'origin_id' => 'nullable|integer|exists:vehicle_origins,id',
            'feature_id' => 'nullable|integer|exists:vehicle_features,id',
            'active_only' => 'boolean',
            'search' => 'nullable|string|max:100',
            'generation' => 'nullable|string|max:100',
            'specification_key' => 'nullable|string|max:100',
            'specification_value' => 'nullable|string|max:100',
            'sort_by' => 'string|in:year,cc,created_at,updated_at',
            'sort_order' => 'string|in:asc,desc',
            'per_page' => 'integer|min:1|max:100',
            'with' => 'string'
        ]);

        $query = VehicleDetail::query();

        // Apply filters
        $this->applyFilters($query, $request);

        // Filter by active status
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Search by engine type or description
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('engine_type', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('description', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('generation', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Filter by generation
        if ($request->has('generation') && $request->generation) {
            $query->where('generation', $request->generation);
        }

        // Filter by features
        if ($request->has('feature_id') && $request->feature_id) {
            $query->whereHas('features', function ($q) use ($request) {
                $q->where('vehicle_features.id', $request->feature_id);
            });
        }

        // Filter by specifications
        if ($request->has('specification_key') && $request->specification_key) {
            $query->whereJsonContains('specifications->' . $request->specification_key, $request->specification_value);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'year');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Include relationships
        if ($request->has('with') && $request->with) {
            $relations = array_filter(explode(',', $request->with));
            $allowedRelations = ['brand', 'model', 'type', 'transmission', 'origin', 'features'];
            $relations = array_intersect($relations, $allowedRelations);
            
            if (!empty($relations)) {
                $query->with($relations);
            }
        }

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $details = $query->paginate($perPage);

        // Add computed specifications to each item
        $details->getCollection()->transform(function ($detail) {
            $detail->full_name = $detail->full_name;
            $detail->specification_summary = $detail->specification_summary;
            $detail->image_url = $detail->image_url;
            return $detail;
        });

        return response()->json([
            'success' => true,
            'data' => $details,
            'message' => 'Vehicle details retrieved successfully'
        ]);
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, $request): void
    {
        if ($request->has('brand_id') && $request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('model_id') && $request->model_id) {
            $query->where('model_id', $request->model_id);
        }

        if ($request->has('type_id') && $request->type_id) {
            $query->where('type_id', $request->type_id);
        }

        if ($request->has('year') && $request->year) {
            $query->where('year', $request->year);
        }

        if ($request->has('year_from') && $request->year_from) {
            $query->where('year', '>=', $request->year_from);
        }

        if ($request->has('year_to') && $request->year_to) {
            $query->where('year', '<=', $request->year_to);
        }

        if ($request->has('min_cc') && $request->min_cc) {
            $query->where('cc', '>=', $request->min_cc);
        }

        if ($request->has('max_cc') && $request->max_cc) {
            $query->where('cc', '<=', $request->max_cc);
        }

        if ($request->has('fuel_type') && $request->fuel_type) {
            $query->where('fuel_type', $request->fuel_type);
        }

        if ($request->has('transmission_id') && $request->transmission_id) {
            $query->where('transmission_id', $request->transmission_id);
        }

        if ($request->has('origin_id') && $request->origin_id) {
            $query->where('origin_id', $request->origin_id);
        }
    }

    /**
     * Store a newly created vehicle detail.
     *
     * @OA\Post(
     *     path="/api/vehicle/details",
     *     operationId="createVehicleDetail",
     *     tags={"Vehicle Details"},
     *     summary="Create a new vehicle detail",
     *     description="Create a new vehicle detail with provided data",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"brand_id", "model_id", "type_id", "year", "cc", "fuel_type", "transmission_id"},
     *             @OA\Property(property="brand_id", type="integer", example=1),
     *             @OA\Property(property="model_id", type="integer", example=1),
     *             @OA\Property(property="type_id", type="integer", example=1),
     *             @OA\Property(property="year", type="integer", example=2023),
     *             @OA\Property(property="cc", type="integer", example=1496),
     *             @OA\Property(property="fuel_type", type="string", example="Bensin"),
     *             @OA\Property(property="transmission_id", type="integer", example=1),
     *             @OA\Property(property="engine_type", type="string", example="2NR-VE"),
     *             @OA\Property(property="origin_id", type="integer", example=1),
     *             @OA\Property(property="generation", type="string", example="Generasi 3"),
     *             @OA\Property(property="market_period", type="string", example="2023-2028"),
     *             @OA\Property(property="description", type="string", example="Toyota Avanza G 1.5 CVT"),
     *             @OA\Property(property="image_path", type="string", example="vehicles/avanza-2023.jpg"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(
     *                 property="specifications",
     *                 type="object",
     *                 example={"horse_power": "107 HP", "torque": "140 Nm", "doors": "5", "seats": "7"}
     *             ),
     *             @OA\Property(
     *                 property="features",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 2, 3}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Vehicle detail created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleDetail"),
     *             @OA\Property(property="message", type="string", example="Vehicle detail created successfully")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to create vehicle details")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'type_id' => 'required|integer|exists:vehicle_types,id',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 10),
            'cc' => 'required|integer|min:0',
            'fuel_type' => 'required|string|max:50',
            'transmission_id' => 'required|integer|exists:transmission_types,id',
            'engine_type' => 'nullable|string|max:100',
            'origin_id' => 'nullable|integer|exists:vehicle_origins,id',
            'generation' => 'nullable|string|max:100',
            'market_period' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'image_path' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'specifications' => 'nullable|array',
            'features' => 'nullable|array',
            'features.*' => 'integer|exists:vehicle_features,id'
        ]);

        // Check for duplicate combination
        $validator->after(function ($validator) use ($request) {
            $exists = VehicleDetail::where('brand_id', $request->brand_id)
                ->where('model_id', $request->model_id)
                ->where('type_id', $request->type_id)
                ->where('year', $request->year)
                ->where('cc', $request->cc)
                ->where('fuel_type', $request->fuel_type)
                ->where('transmission_id', $request->transmission_id)
                ->where('origin_id', $request->origin_id)
                ->exists();

            if ($exists) {
                $validator->errors()->add('general', 'Vehicle detail with this combination already exists.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $detailData = $request->only([
                'brand_id', 'model_id', 'type_id', 'year', 'cc', 'fuel_type',
                'transmission_id', 'engine_type', 'origin_id', 'generation',
                'market_period', 'description', 'image_path', 'is_active'
            ]);

            // Handle specifications
            if ($request->has('specifications') && is_array($request->specifications)) {
                $detailData['specifications'] = $request->specifications;
            }

            $detail = VehicleDetail::create($detailData);

            // Attach features if provided
            if ($request->has('features') && is_array($request->features)) {
                $detail->features()->sync($request->features);
            }

            DB::commit();

            $detail->load(['brand', 'model', 'type', 'transmission', 'origin', 'features']);
            
            // Add computed specifications
            $detail->full_name = $detail->full_name;
            $detail->specification_summary = $detail->specification_summary;
            $detail->image_url = $detail->image_url;

            return response()->json([
                'success' => true,
                'data' => $detail,
                'message' => 'Vehicle detail created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create vehicle detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified vehicle detail.
     *
     * @OA\Get(
     *     path="/api/vehicle/details/{id}",
     *     operationId="getVehicleDetail",
     *     tags={"Vehicle Details"},
     *     summary="Get specific vehicle detail",
     *     description="Returns vehicle detail details by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Detail ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="with",
     *         in="query",
     *         description="Include relationships",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleDetail"),
     *             @OA\Property(property="message", type="string", example="Vehicle detail retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle detail not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle detail not found")
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $detail = VehicleDetail::findOrFail($id);
            
            // Load relationships if requested
            if (request()->has('with') && request()->with) {
                $relations = array_filter(explode(',', request()->get('with')));
                $allowedRelations = ['brand', 'model', 'type', 'transmission', 'origin', 'features'];
                $relations = array_intersect($relations, $allowedRelations);
                
                if (!empty($relations)) {
                    $detail->load($relations);
                }
            }

            // Add computed specifications
            $detail->full_name = $detail->full_name;
            $detail->specification_summary = $detail->specification_summary;
            $detail->image_url = $detail->image_url;

            return response()->json([
                'success' => true,
                'data' => $detail,
                'message' => 'Vehicle detail retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle detail not found'
            ], 404);
        }
    }

    /**
     * Update the specified vehicle detail.
     *
     * @OA\Put(
     *     path="/api/vehicle/details/{id}",
     *     operationId="updateVehicleDetail",
     *     tags={"Vehicle Details"},
     *     summary="Update vehicle detail",
     *     description="Update vehicle detail details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Detail ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="brand_id", type="integer", example=1),
     *             @OA\Property(property="model_id", type="integer", example=1),
     *             @OA\Property(property="type_id", type="integer", example=1),
     *             @OA\Property(property="year", type="integer", example=2023),
     *             @OA\Property(property="cc", type="integer", example=1496),
     *             @OA\Property(property="fuel_type", type="string", example="Bensin"),
     *             @OA\Property(property="transmission_id", type="integer", example=1),
     *             @OA\Property(property="engine_type", type="string", example="2NR-VE"),
     *             @OA\Property(property="origin_id", type="integer", example=1),
     *             @OA\Property(property="generation", type="string", example="Generasi 3"),
     *             @OA\Property(property="market_period", type="string", example="2023-2028"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="image_path", type="string", example="vehicles/avanza-2023.jpg"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(
     *                 property="specifications",
     *                 type="object",
     *                 example={"horse_power": "107 HP", "torque": "140 Nm", "doors": "5", "seats": "7"}
     *             ),
     *             @OA\Property(
     *                 property="features",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 2, 3}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle detail updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleDetail"),
     *             @OA\Property(property="message", type="string", example="Vehicle detail updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle detail not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle detail not found")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to update vehicle details")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $detail = VehicleDetail::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'brand_id' => 'required|integer|exists:vehicle_brands,id',
                'model_id' => 'required|integer|exists:vehicle_models,id',
                'type_id' => 'required|integer|exists:vehicle_types,id',
                'year' => 'required|integer|min:1900|max:' . (date('Y') + 10),
                'cc' => 'required|integer|min:0',
                'fuel_type' => 'required|string|max:50',
                'transmission_id' => 'required|integer|exists:transmission_types,id',
                'engine_type' => 'nullable|string|max:100',
                'origin_id' => 'nullable|integer|exists:vehicle_origins,id',
                'generation' => 'nullable|string|max:100',
                'market_period' => 'nullable|string|max:50',
                'description' => 'nullable|string|max:500',
                'image_path' => 'nullable|string|max:255',
                'is_active' => 'boolean',
                'specifications' => 'nullable|array',
                'features' => 'nullable|array',
                'features.*' => 'integer|exists:vehicle_features,id'
            ]);

            // Check for duplicate combination (excluding current record)
            $validator->after(function ($validator) use ($request, $id) {
                $exists = VehicleDetail::where('id', '!=', $id)
                    ->where('brand_id', $request->brand_id)
                    ->where('model_id', $request->model_id)
                    ->where('type_id', $request->type_id)
                    ->where('year', $request->year)
                    ->where('cc', $request->cc)
                    ->where('fuel_type', $request->fuel_type)
                    ->where('transmission_id', $request->transmission_id)
                    ->where('origin_id', $request->origin_id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('general', 'Vehicle detail with this combination already exists.');
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $updateData = $request->only([
                'brand_id', 'model_id', 'type_id', 'year', 'cc', 'fuel_type',
                'transmission_id', 'engine_type', 'origin_id', 'generation',
                'market_period', 'description', 'image_path', 'is_active'
            ]);

            // Handle specifications
            if ($request->has('specifications')) {
                $updateData['specifications'] = $request->specifications;
            }

            $detail->update($updateData);

            // Update features if provided
            if ($request->has('features')) {
                $detail->features()->sync($request->features);
            }

            DB::commit();

            $detail->load(['brand', 'model', 'type', 'transmission', 'origin', 'features']);
            
            // Add computed specifications
            $detail->full_name = $detail->full_name;
            $detail->specification_summary = $detail->specification_summary;
            $detail->image_url = $detail->image_url;

            return response()->json([
                'success' => true,
                'data' => $detail,
                'message' => 'Vehicle detail updated successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle detail not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update vehicle detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified vehicle detail.
     *
     * @OA\Delete(
     *     path="/api/vehicle/details/{id}",
     *     operationId="deleteVehicleDetail",
     *     tags={"Vehicle Details"},
     *     summary="Delete vehicle detail",
     *     description="Delete vehicle detail by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Detail ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle detail deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle detail deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle detail not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle detail not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - No permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to delete vehicle details")
     *         )
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $detail = VehicleDetail::findOrFail($id);

            // Delete related features
            $detail->features()->detach();

            // Delete image file if exists
            if ($detail->image_path && Storage::disk('public')->exists($detail->image_path)) {
                Storage::disk('public')->delete($detail->image_path);
            }

            $detail->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle detail deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle detail not found'
            ], 404);
        }
    }

    /**
     * Get all features for a specific vehicle detail.
     *
     * @OA\Get(
     *     path="/api/vehicle/details/{id}/features",
     *     operationId="getDetailFeatures",
     *     tags={"Vehicle Details"},
     *     summary="Get features for a vehicle detail",
     *     description="Returns all features for a specific vehicle detail",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Detail ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/VehicleFeature")),
     *             @OA\Property(property="message", type="string", example="Vehicle features retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle detail not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle detail not found")
     *         )
     *     )
     * )
     */
    public function features(string $id): JsonResponse
    {
        try {
            $detail = VehicleDetail::findOrFail($id);

            $features = $detail->features()->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $features,
                'message' => 'Vehicle features retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle detail not found'
            ], 404);
        }
    }

    /**
     * Get all specifications for a specific vehicle detail.
     *
     * @OA\Get(
     *     path="/api/vehicle/details/{id}/specifications",
     *     operationId="getDetailspecifications",
     *     tags={"Vehicle Details"},
     *     summary="Get specifications for a vehicle detail",
     *     description="Returns all specifications for a specific vehicle detail",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Detail ID",
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
     *             @OA\Property(property="message", type="string", example="Vehicle specifications retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle detail not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle detail not found")
     *         )
     *     )
     * )
     */
    public function specifications(string $id): JsonResponse
    {
        try {
            $detail = VehicleDetail::findOrFail($id);

            $specifications = $detail->specifications ?? [];

            return response()->json([
                'success' => true,
                'data' => $specifications,
                'message' => 'Vehicle specifications retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle detail not found'
            ], 404);
        }
    }

    /**
     * Update specifications for a specific vehicle detail.
     *
     * @OA\Patch(
     *     path="/api/vehicle/details/{id}/specifications",
     *     operationId="updateDetailspecifications",
     *     tags={"Vehicle Details"},
     *     summary="Update specifications for a vehicle detail",
     *     description="Update specifications for a specific vehicle detail",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Detail ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"specifications"},
     *             @OA\Property(
     *                 property="specifications",
     *                 type="object",
     *                 example={"horse_power": "107 HP", "torque": "140 Nm", "doors": "5", "seats": "7"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="specifications updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Vehicle specifications updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle detail not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle detail not found")
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
    public function updatespecifications(Request $request, string $id): JsonResponse
    {
        try {
            $detail = VehicleDetail::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'specifications' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $detail->update(['specifications' => $request->specifications]);

            return response()->json([
                'success' => true,
                'data' => $detail->specifications,
                'message' => 'Vehicle specifications updated successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle detail not found'
            ], 404);
        }
    }

    /**
     * Get statistics for vehicle details.
     *
     * @OA\Get(
     *     path="/api/vehicle/details/stats",
     *     operationId="getDetailsStats",
     *     tags={"Vehicle Details"},
     *     summary="Get vehicle details statistics",
     *     description="Returns statistics for vehicle details",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Vehicle details statistics retrieved successfully")
     *         )
     *     )
     * )
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_details' => VehicleDetail::count(),
            'active_details' => VehicleDetail::where('is_active', true)->count(),
            'total_brands' => VehicleBrand::count(),
            'total_models' => VehicleModel::count(),
            'total_types' => VehicleType::count(),
            'years_available' => VehicleDetail::select('year')
                ->distinct()
                ->orderBy('year', 'desc')
                ->pluck('year'),
            'fuel_types' => VehicleDetail::select('fuel_type', DB::raw('COUNT(*) as count'))
                ->groupBy('fuel_type')
                ->orderBy('count', 'desc')
                ->get(),
            'popular_brands' => VehicleDetail::join('vehicle_brands', 'vehicle_details.brand_id', '=', 'vehicle_brands.id')
                ->select('vehicle_brands.name', 'vehicle_brands.id', DB::raw('COUNT(*) as count'))
                ->groupBy('vehicle_brands.id', 'vehicle_brands.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
            'recent_additions' => VehicleDetail::with(['brand', 'model'])
                ->latest()
                ->limit(5)
                ->get(),
            'common_specifications_keys' => $this->getCommonspecificationKeys()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Vehicle details statistics retrieved successfully'
        ]);
    }

    /**
     * Get available fuel types.
     *
     * @OA\Get(
     *     path="/api/vehicle/details/fuel-types",
     *     operationId="getFuelTypes",
     *     tags={"Vehicle Details"},
     *     summary="Get available fuel types",
     *     description="Returns list of all distinct fuel types in the system",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="message", type="string", example="Fuel types retrieved successfully")
     *         )
     *     )
     * )
     */
    public function fuelTypes(): JsonResponse
    {
        $fuelTypes = VehicleDetail::select('fuel_type')
            ->whereNotNull('fuel_type')
            ->distinct()
            ->orderBy('fuel_type')
            ->pluck('fuel_type');

        return response()->json([
            'success' => true,
            'data' => $fuelTypes,
            'message' => 'Fuel types retrieved successfully'
        ]);
    }

    /**
     * Get common specification keys from vehicle details.
     *
     * @OA\Get(
     *     path="/api/vehicle/details/specification-keys",
     *     operationId="getspecificationKeys",
     *     tags={"Vehicle Details"},
     *     summary="Get common specification keys",
     *     description="Returns list of common specification keys used in vehicle details",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="message", type="string", example="specification keys retrieved successfully")
     *         )
     *     )
     * )
     */
    public function specificationKeys(): JsonResponse
    {
        $specificationKeys = $this->getCommonspecificationKeys();

        return response()->json([
            'success' => true,
            'data' => $specificationKeys,
            'message' => 'specification keys retrieved successfully'
        ]);
    }

    /**
     * Helper method to get common specification keys
     */
    private function getCommonspecificationKeys(): array
    {
        $keys = VehicleDetail::select('specifications')
            ->whereNotNull('specifications')
            ->get()
            ->map(function ($detail) {
                return array_keys($detail->specifications ?? []);
            })
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return $keys;
    }

    /**
     * Upload image for vehicle detail.
     *
     * @OA\Post(
     *     path="/api/vehicle/details/{id}/upload-image",
     *     operationId="uploadDetailImage",
     *     tags={"Vehicle Details"},
     *     summary="Upload image for vehicle detail",
     *     description="Upload an image for a specific vehicle detail",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Detail ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"image"},
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="Image file (jpg, jpeg, png, webp, gif)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image uploaded successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Image uploaded successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle detail not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vehicle detail not found")
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
    public function uploadImage(Request $request, string $id): JsonResponse
    {
        try {
            $detail = VehicleDetail::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120', // 5MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Delete old image if exists
            if ($detail->image_path && Storage::disk('public')->exists($detail->image_path)) {
                Storage::disk('public')->delete($detail->image_path);
            }

            // Upload new image
            $imageFile = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
            $folder = 'vehicle-details/' . $detail->id;
            $path = $imageFile->storeAs($folder, $filename, 'public');

            // Update detail with new image path
            $detail->update(['image_path' => $path]);

            return response()->json([
                'success' => true,
                'data' => [
                    'image_path' => $path,
                    'image_url' => Storage::disk('public')->url($path)
                ],
                'message' => 'Image uploaded successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle detail not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}