<?php

namespace App\Http\Controllers\Api\Vehicle;

use App\Filament\Resources\API\ApiAccountResource;
use App\Http\Controllers\Controller;
use App\Models\VehicleDetailFeature;
use App\Models\VehicleDetail;
use App\Models\VehicleFeature;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class VehicleDetailFeatureController extends Controller
{
    /**
     * Display a listing of vehicle detail-feature relationships.
     *
     * @OA\Get(
     *     path="/api/vehicle/detail-features",
     *     operationId="getVehicleDetailFeatures",
     *     tags={"Vehicle Detail Features"},
     *     summary="Get all vehicle detail-feature relationships",
     *     description="Returns list of all pivot relationships with optional filters",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="vehicle_detail_id",
     *         in="query",
     *         description="Filter by vehicle detail ID",
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
     *             @OA\Property(property="message", type="string", example="Vehicle detail-feature relationships retrieved successfully")
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
            'vehicle_detail_id' => 'nullable|integer|exists:vehicle_details,id',
            'feature_id' => 'nullable|integer|exists:vehicle_features,id',
            'per_page' => 'integer|min:1|max:100'
        ]);

        $query = VehicleDetailFeature::query()
            ->with(['vehicleDetail', 'feature']);

        // Filter by vehicle detail
        if ($request->has('vehicle_detail_id') && $request->vehicle_detail_id) {
            $query->where('vehicle_detail_id', $request->vehicle_detail_id);
        }

        // Filter by feature
        if ($request->has('feature_id') && $request->feature_id) {
            $query->where('feature_id', $request->feature_id);
        }

        // Order by creation date
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $relationships = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $relationships,
            'message' => 'Vehicle detail-feature relationships retrieved successfully'
        ]);
    }

    /**
     * Store a new vehicle detail-feature relationship.
     *
     * @OA\Post(
     *     path="/api/vehicle/detail-features",
     *     operationId="createVehicleDetailFeature",
     *     tags={"Vehicle Detail Features"},
     *     summary="Create a new vehicle detail-feature relationship",
     *     description="Create a new pivot relationship between vehicle detail and feature",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"vehicle_detail_id", "feature_id"},
     *             @OA\Property(property="vehicle_detail_id", type="integer", example=1),
     *             @OA\Property(property="feature_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Relationship created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleDetailFeature"),
     *             @OA\Property(property="message", type="string", example="Vehicle detail-feature relationship created successfully")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to create relationships")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vehicle_detail_id' => 'required|integer|exists:vehicle_details,id',
            'feature_id' => 'required|integer|exists:vehicle_features,id'
        ]);

        // Check if relationship already exists
        $validator->after(function ($validator) use ($request) {
            $exists = VehicleDetailFeature::where('vehicle_detail_id', $request->vehicle_detail_id)
                ->where('feature_id', $request->feature_id)
                ->exists();

            if ($exists) {
                $validator->errors()->add('general', 'This relationship already exists.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $relationship = VehicleDetailFeature::create([
            'vehicle_detail_id' => $request->vehicle_detail_id,
            'feature_id' => $request->feature_id
        ]);

        // Load relationships
        $relationship->load(['vehicleDetail', 'feature']);

        return response()->json([
            'success' => true,
            'data' => $relationship,
            'message' => 'Vehicle detail-feature relationship created successfully'
        ], 201);
    }

    /**
     * Display the specified vehicle detail-feature relationship.
     *
     * @OA\Get(
     *     path="/api/vehicle/detail-features/{id}",
     *     operationId="getVehicleDetailFeature",
     *     tags={"Vehicle Detail Features"},
     *     summary="Get specific vehicle detail-feature relationship",
     *     description="Returns pivot relationship details by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Detail Feature ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleDetailFeature"),
     *             @OA\Property(property="message", type="string", example="Vehicle detail-feature relationship retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Relationship not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Relationship not found")
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $relationship = VehicleDetailFeature::with(['vehicleDetail', 'feature'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $relationship,
                'message' => 'Vehicle detail-feature relationship retrieved successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Relationship not found'
            ], 404);
        }
    }

    /**
     * Remove the specified vehicle detail-feature relationship.
     *
     * @OA\Delete(
     *     path="/api/vehicle/detail-features/{id}",
     *     operationId="deleteVehicleDetailFeature",
     *     tags={"Vehicle Detail Features"},
     *     summary="Delete vehicle detail-feature relationship",
     *     description="Delete pivot relationship by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle Detail Feature ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Relationship deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle detail-feature relationship deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Relationship not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Relationship not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - No permission",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to delete relationships")
     *         )
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $relationship = VehicleDetailFeature::findOrFail($id);
            $relationship->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle detail-feature relationship deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Relationship not found'
            ], 404);
        }
    }

    /**
     * Bulk create vehicle detail-feature relationships.
     *
     * @OA\Post(
     *     path="/api/vehicle/detail-features/bulk",
     *     operationId="bulkCreateVehicleDetailFeatures",
     *     tags={"Vehicle Detail Features"},
     *     summary="Bulk create relationships",
     *     description="Create multiple pivot relationships at once",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"relationships"},
     *             @OA\Property(
     *                 property="relationships",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="vehicle_detail_id", type="integer", example=1),
     *                     @OA\Property(property="feature_id", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Relationships created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Relationships created successfully")
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
            'relationships' => 'required|array|min:1',
            'relationships.*.vehicle_detail_id' => 'required|integer|exists:vehicle_details,id',
            'relationships.*.feature_id' => 'required|integer|exists:vehicle_features,id'
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

            $createdCount = 0;
            $skippedCount = 0;
            $createdRelationships = [];

            foreach ($request->relationships as $relData) {
                // Check if relationship already exists
                $exists = VehicleDetailFeature::where('vehicle_detail_id', $relData['vehicle_detail_id'])
                    ->where('feature_id', $relData['feature_id'])
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                $relationship = VehicleDetailFeature::create([
                    'vehicle_detail_id' => $relData['vehicle_detail_id'],
                    'feature_id' => $relData['feature_id']
                ]);

                $createdRelationships[] = $relationship;
                $createdCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'created' => $createdCount,
                    'skipped' => $skippedCount,
                    'total_processed' => count($request->relationships),
                    'relationships' => $createdRelationships
                ],
                'message' => 'Relationships created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create relationships',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete vehicle detail-feature relationships.
     *
     * @OA\Delete(
     *     path="/api/vehicle/detail-features/bulk",
     *     operationId="bulkDeleteVehicleDetailFeatures",
     *     tags={"Vehicle Detail Features"},
     *     summary="Bulk delete relationships",
     *     description="Delete multiple pivot relationships at once",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"relationships"},
     *             @OA\Property(
     *                 property="relationships",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="vehicle_detail_id", type="integer", example=1),
     *                     @OA\Property(property="feature_id", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Relationships deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Relationships deleted successfully")
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
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'relationships' => 'required|array|min:1',
            'relationships.*.vehicle_detail_id' => 'required|integer|exists:vehicle_details,id',
            'relationships.*.feature_id' => 'required|integer|exists:vehicle_features,id'
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

            $deletedCount = 0;
            $notFoundCount = 0;

            foreach ($request->relationships as $relData) {
                $deleted = VehicleDetailFeature::where('vehicle_detail_id', $relData['vehicle_detail_id'])
                    ->where('feature_id', $relData['feature_id'])
                    ->delete();

                if ($deleted) {
                    $deletedCount++;
                } else {
                    $notFoundCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'deleted' => $deletedCount,
                    'not_found' => $notFoundCount,
                    'total_processed' => count($request->relationships)
                ],
                'message' => 'Relationships deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete relationships',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics for relationships.
     *
     * @OA\Get(
     *     path="/api/vehicle/detail-features/stats",
     *     operationId="getDetailFeatureStats",
     *     tags={"Vehicle Detail Features"},
     *     summary="Get relationship statistics",
     *     description="Returns statistics for vehicle detail-feature relationships",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="feature_id",
     *         in="query",
     *         description="Filter by feature ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="vehicle_detail_id",
     *         in="query",
     *         description="Filter by vehicle detail ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Relationship statistics retrieved successfully")
     *         )
     *     )
     * )
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'feature_id' => 'nullable|integer|exists:vehicle_features,id',
            'vehicle_detail_id' => 'nullable|integer|exists:vehicle_details,id'
        ]);

        $query = VehicleDetailFeature::query();

        if ($request->has('feature_id') && $request->feature_id) {
            $query->where('feature_id', $request->feature_id);
        }

        if ($request->has('vehicle_detail_id') && $request->vehicle_detail_id) {
            $query->where('vehicle_detail_id', $request->vehicle_detail_id);
        }

        $stats = [
            'total_relationships' => $query->count(),
            'unique_vehicles' => $query->distinct('vehicle_detail_id')->count('vehicle_detail_id'),
            'unique_features' => $query->distinct('feature_id')->count('feature_id'),
            'most_common_features' => VehicleDetailFeature::select('feature_id', DB::raw('COUNT(*) as count'))
                ->when($request->has('vehicle_detail_id'), function ($q) use ($request) {
                    $q->where('vehicle_detail_id', $request->vehicle_detail_id);
                })
                ->groupBy('feature_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->with('feature')
                ->get(),
            'vehicles_with_most_features' => VehicleDetailFeature::select('vehicle_detail_id', DB::raw('COUNT(*) as count'))
                ->when($request->has('feature_id'), function ($q) use ($request) {
                    $q->where('feature_id', $request->feature_id);
                })
                ->groupBy('vehicle_detail_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->with('vehicleDetail')
                ->get(),
            'average_features_per_vehicle' => VehicleDetailFeature::selectRaw('AVG(feature_count) as avg_features')
                ->from(function ($query) {
                    $query->select('vehicle_detail_id', DB::raw('COUNT(*) as feature_count'))
                        ->from('vehicle_detail_features')
                        ->groupBy('vehicle_detail_id');
                }, 'subquery')
                ->first()->avg_features ?? 0
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Relationship statistics retrieved successfully'
        ]);
    }

    /**
     * Sync features for a specific vehicle detail.
     *
     * @OA\Post(
     *     path="/api/vehicle/detail-features/sync/{vehicle_detail_id}",
     *     operationId="syncVehicleDetailFeatures",
     *     tags={"Vehicle Detail Features"},
     *     summary="Sync features for a vehicle detail",
     *     description="Replace all features for a vehicle detail with the provided list",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="vehicle_detail_id",
     *         in="path",
     *         description="Vehicle Detail ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"feature_ids"},
     *             @OA\Property(
     *                 property="feature_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 2, 3, 4}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Features synced successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Features synced successfully")
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
    public function syncFeatures(string $vehicleDetailId, Request $request): JsonResponse
    {
        try {
            $vehicleDetail = VehicleDetail::findOrFail($vehicleDetailId);

            $validator = Validator::make($request->all(), [
                'feature_ids' => 'required|array',
                'feature_ids.*' => 'integer|exists:vehicle_features,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $vehicleDetail->features()->sync($request->feature_ids);

            return response()->json([
                'success' => true,
                'data' => [
                    'vehicle_detail_id' => $vehicleDetailId,
                    'feature_ids' => $request->feature_ids,
                    'total_features' => count($request->feature_ids)
                ],
                'message' => 'Features synced successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle detail not found'
            ], 404);
        }
    }
}

// Testing Api
// 1. Get All Relationships:
    // GET /api/vehicle/detail-features
    // Headers: Authorization: Bearer {token}
    // Query: ?vehicle_detail_id=1&feature_id=5&per_page=10

// 2. Get Single Relationship:
    // GET /api/vehicle/detail-features/1
    // Headers: Authorization: Bearer {token}

// 3. Get Relationship Statistics:
    // GET /api/vehicle/detail-features/stats
    // Headers: Authorization: Bearer {token}
    // Query: ?feature_id=1&vehicle_detail_id=1

// 4. Create Relationship (Requires vehicles:create):
    // POST /api/vehicle/detail-features
    // Headers: Authorization: Bearer {token}
    // Body: {
    //     "vehicle_detail_id": 1,
    //     "feature_id": 5
    // }

// 5. Bulk Create Relationships: 
    // POST /api/vehicle/detail-features/bulk
    // Headers: Authorization: Bearer {token}
    // Body: {
    //     "relationships": [
    //         {
    //             "vehicle_detail_id": 1,
    //             "feature_id": 1
    //         },
    //         {
    //             "vehicle_detail_id": 1,
    //             "feature_id": 2
    //         },
    //         {
    //             "vehicle_detail_id": 2,
    //             "feature_id": 3
    //         }
    //     ]
    // }

// 6. Sync Features for Vehicle Detail:
    // POST /api/vehicle/detail-features/sync/1
    // Headers: Authorization: Bearer {token}
    // Body: {
    //     "feature_ids": [1, 2, 3, 4, 5]
    // }

// 7. Delete Relationship (Requires vehicles:delete):
    // DELETE /api/vehicle/detail-features/1
    // Headers: Authorization: Bearer {token}

// 8. Bulk Delete Relationships:
    // DELETE /api/vehicle/detail-features/bulk
    // Headers: Authorization: Bearer {token}
    // Body: {
    //     "relationships": [
    //         {
    //             "vehicle_detail_id": 1,
    //             "feature_id": 1
    //         },
    //         {
    //             "vehicle_detail_id": 1,
    //             "feature_id": 2
    //         }
    //     ]
    // }
// Kapan Controller Pivot Ini Berguna:
    // Bulk Operations: Ketika perlu menambah/hapus banyak hubungan sekaligus

    // Advanced Analytics: Statistik khusus untuk hubungan detail-feature

    // Audit Trail: Melihat semua hubungan yang pernah dibuat

    // Manual Management: UI khusus untuk manage relationships

    // Reporting: Laporan khusus untuk kombinasi tertentu

    // Sync Operations: Mengganti semua features untuk sebuah vehicle detail