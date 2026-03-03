<?php

namespace App\Http\Controllers\Api\Vehicle\Search;

use App\Http\Controllers\Controller;
use App\Models\VehicleDetail;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use App\Models\TransmissionType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;


class VehicleSearchController extends Controller
{
    /**
     * Search vehicle details by text.
     *
     * @OA\Get(
     *     path="/api/vehicle/search",
     *     operationId="searchVehicles",
     *     tags={"Vehicle Search"},
     *     summary="Search vehicles by text",
     *     description="Fuzzy search for vehicle details by brand, model, type, or engine",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit results",
     *         required=false,
     *         @OA\Schema(type="integer", default=20)
     *     ),
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
     *                     @OA\Property(property="full_name", type="string", example="Toyota Avanza G 1.5 1496 CVT"),
     *                     @OA\Property(property="brand", type="string", example="Toyota"),
     *                     @OA\Property(property="model", type="string", example="Avanza"),
     *                     @OA\Property(property="type", type="string", example="G 1.5"),
     *                     @OA\Property(property="year", type="integer", example=2023),
     *                     @OA\Property(property="cc", type="integer", example=1496),
     *                     @OA\Property(property="fuel_type", type="string", example="Bensin"),
     *                     @OA\Property(property="transmission", type="string", example="CVT"),
     *                     @OA\Property(property="specification_summary", type="string", example="2NR-VE • 1496 cc • Bensin • CVT")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Search results retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Search query is required")
     *         )
     *     )
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'limit' => 'integer|min:1|max:100'
        ]);

        $query = $request->q;
        $limit = $request->get('limit', 20);

        $results = VehicleDetail::select([
                'vehicle_details.id',
                'vehicle_details.year',
                'vehicle_details.cc',
                'vehicle_details.fuel_type',
                'vehicle_details.engine_type',
                'vehicle_brands.name as brand_name',
                'vehicle_models.name as model_name',
                'vehicle_types.name as type_name',
                'transmission_types.name as transmission_name'
            ])
            ->join('vehicle_brands', 'vehicle_details.brand_id', '=', 'vehicle_brands.id')
            ->join('vehicle_models', 'vehicle_details.model_id', '=', 'vehicle_models.id')
            ->join('vehicle_types', 'vehicle_details.type_id', '=', 'vehicle_types.id')
            ->join('transmission_types', 'vehicle_details.transmission_id', '=', 'transmission_types.id')
            ->where('vehicle_details.is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('vehicle_brands.name', 'LIKE', "%{$query}%")
                  ->orWhere('vehicle_models.name', 'LIKE', "%{$query}%")
                  ->orWhere('vehicle_types.name', 'LIKE', "%{$query}%")
                  ->orWhere('vehicle_details.engine_type', 'LIKE', "%{$query}%")
                  ->orWhere('vehicle_details.description', 'LIKE', "%{$query}%")
                  ->orWhere(DB::raw("CONCAT(vehicle_brands.name, ' ', vehicle_models.name, ' ', vehicle_types.name)"), 'LIKE', "%{$query}%");
            })
            ->orderBy('vehicle_details.year', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'full_name' => sprintf(
                        '%s %s %s %d %s',
                        $detail->brand_name,
                        $detail->model_name,
                        $detail->type_name,
                        $detail->year,
                        $detail->transmission_name
                    ),
                    'brand' => $detail->brand_name,
                    'model' => $detail->model_name,
                    'type' => $detail->type_name,
                    'year' => $detail->year,
                    'cc' => $detail->cc,
                    'fuel_type' => $detail->fuel_type,
                    'transmission' => $detail->transmission_name,
                    'specification_summary' => implode(' • ', array_filter([
                        $detail->engine_type,
                        $detail->cc ? $detail->cc . ' cc' : null,
                        $detail->fuel_type,
                        $detail->transmission_name
                    ]))
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $results,
            'message' => 'Search results retrieved successfully'
        ]);
    }

    /**
     * Get available brands from vehicle details.
     *
     * @OA\Get(
     *     path="/api/vehicle/selection/brands",
     *     operationId="getAvailableBrands",
     *     tags={"Vehicle Selection"},
     *     summary="Get brands from vehicle details",
     *     description="Returns all distinct brands that have vehicle details",
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
     *                     @OA\Property(property="name", type="string", example="Toyota"),
     *                     @OA\Property(property="vehicle_count", type="integer", example=20)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Available brands retrieved successfully")
     *         )
     *     )
     * )
     */
    public function getAvailableBrands(): JsonResponse
    {
        $brands = VehicleBrand::select([
                'vehicle_brands.id',
                'vehicle_brands.name',
                DB::raw('COUNT(vehicle_details.id) as vehicle_count')
            ])
            ->join('vehicle_details', 'vehicle_brands.id', '=', 'vehicle_details.brand_id')
            ->where('vehicle_details.is_active', true)
            ->groupBy('vehicle_brands.id', 'vehicle_brands.name')
            ->orderBy('vehicle_brands.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $brands,
            'message' => 'Available brands retrieved successfully'
        ]);
    }

    /**
     * Get available models for a specific brand.
     *
     * @OA\Get(
     *     path="/api/vehicle/selection/models",
     *     operationId="getAvailableModels",
     *     tags={"Vehicle Selection"},
     *     summary="Get models for a brand",
     *     description="Returns all distinct models for a specific brand from vehicle details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="brand_id",
     *         in="query",
     *         description="Brand ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
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
     *                     @OA\Property(property="name", type="string", example="Avanza"),
     *                     @OA\Property(property="vehicle_count", type="integer", example=15)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Available models retrieved successfully")
     *         )
     *     )
     * )
     */
    public function getAvailableModels(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:vehicle_brands,id'
        ]);

        $models = VehicleModel::select([
                'vehicle_models.id',
                'vehicle_models.name',
                DB::raw('COUNT(vehicle_details.id) as vehicle_count')
            ])
            ->join('vehicle_details', 'vehicle_models.id', '=', 'vehicle_details.model_id')
            ->where('vehicle_details.brand_id', $request->brand_id)
            ->where('vehicle_details.is_active', true)
            ->groupBy('vehicle_models.id', 'vehicle_models.name')
            ->orderBy('vehicle_models.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $models,
            'message' => 'Available models retrieved successfully'
        ]);
    }

    /**
     * Get available types for a specific brand and model.
     *
     * @OA\Get(
     *     path="/api/vehicle/selection/types",
     *     operationId="getAvailableTypes",
     *     tags={"Vehicle Selection"},
     *     summary="Get types for a brand and model",
     *     description="Returns all distinct types for a specific brand and model from vehicle details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="brand_id",
     *         in="query",
     *         description="Brand ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         description="Model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
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
     *                     @OA\Property(property="name", type="string", example="G 1.5"),
     *                     @OA\Property(property="vehicle_count", type="integer", example=10)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Available types retrieved successfully")
     *         )
     *     )
     * )
     */
    public function getAvailableTypes(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id'
        ]);

        $types = VehicleType::select([
                'vehicle_types.id',
                'vehicle_types.name',
                DB::raw('COUNT(vehicle_details.id) as vehicle_count')
            ])
            ->join('vehicle_details', 'vehicle_types.id', '=', 'vehicle_details.type_id')
            ->where('vehicle_details.brand_id', $request->brand_id)
            ->where('vehicle_details.model_id', $request->model_id)
            ->where('vehicle_details.is_active', true)
            ->groupBy('vehicle_types.id', 'vehicle_types.name')
            ->orderBy('vehicle_types.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $types,
            'message' => 'Available types retrieved successfully'
        ]);
    }

    /**
     * Get available engine capacities (CC) for a specific brand, model and type.
     *
     * @OA\Get(
     *     path="/api/vehicle/selection/cc",
     *     operationId="getAvailableCC",
     *     tags={"Vehicle Selection"},
     *     summary="Get CC options",
     *     description="Returns all distinct engine capacities for a specific brand, model and type",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="brand_id",
     *         in="query",
     *         description="Brand ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         description="Model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type_id",
     *         in="query",
     *         description="Type ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
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
     *                     @OA\Property(property="cc", type="integer", example=1496),
     *                     @OA\Property(property="vehicle_count", type="integer", example=5)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Available CC options retrieved successfully")
     *         )
     *     )
     * )
     */
    public function getAvailableCC(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'type_id' => 'required|integer|exists:vehicle_types,id'
        ]);

        $ccOptions = VehicleDetail::select([
                'cc',
                DB::raw('COUNT(*) as vehicle_count')
            ])
            ->where('brand_id', $request->brand_id)
            ->where('model_id', $request->model_id)
            ->where('type_id', $request->type_id)
            ->where('is_active', true)
            ->whereNotNull('cc')
            ->groupBy('cc')
            ->orderBy('cc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $ccOptions,
            'message' => 'Available CC options retrieved successfully'
        ]);
    }

    /**
     * Get available years for a specific brand, model, type and CC.
     *
     * @OA\Get(
     *     path="/api/vehicle/selection/years",
     *     operationId="getAvailableYears",
     *     tags={"Vehicle Selection"},
     *     summary="Get year options",
     *     description="Returns all distinct years for a specific brand, model, type and CC",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="brand_id",
     *         in="query",
     *         description="Brand ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         description="Model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type_id",
     *         in="query",
     *         description="Type ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="cc",
     *         in="query",
     *         description="Engine capacity (CC)",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
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
     *                     @OA\Property(property="year", type="integer", example=2023),
     *                     @OA\Property(property="vehicle_count", type="integer", example=3)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Available years retrieved successfully")
     *         )
     *     )
     * )
     */
    public function getAvailableYears(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'type_id' => 'required|integer|exists:vehicle_types,id',
            'cc' => 'required|integer|min:0'
        ]);

        $years = VehicleDetail::select([
                'year',
                DB::raw('COUNT(*) as vehicle_count')
            ])
            ->where('brand_id', $request->brand_id)
            ->where('model_id', $request->model_id)
            ->where('type_id', $request->type_id)
            ->where('cc', $request->cc)
            ->where('is_active', true)
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $years,
            'message' => 'Available years retrieved successfully'
        ]);
    }

    /**
     * Get available transmissions for a specific brand, model, type, CC and year.
     *
     * @OA\Get(
     *     path="/api/vehicle/selection/transmissions",
     *     operationId="getAvailableTransmissions",
     *     tags={"Vehicle Selection"},
     *     summary="Get transmission options",
     *     description="Returns all distinct transmissions for a specific brand, model, type, CC and year",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="brand_id",
     *         in="query",
     *         description="Brand ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         description="Model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type_id",
     *         in="query",
     *         description="Type ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="cc",
     *         in="query",
     *         description="Engine capacity (CC)",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Year",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
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
     *                     @OA\Property(property="name", type="string", example="CVT"),
     *                     @OA\Property(property="vehicle_count", type="integer", example=2)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Available transmissions retrieved successfully")
     *         )
     *     )
     * )
     */
    public function getAvailableTransmissions(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'type_id' => 'required|integer|exists:vehicle_types,id',
            'cc' => 'required|integer|min:0',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 10)
        ]);

        $transmissions = TransmissionType::select([
                'transmission_types.id',
                'transmission_types.name',
                DB::raw('COUNT(vehicle_details.id) as vehicle_count')
            ])
            ->join('vehicle_details', 'transmission_types.id', '=', 'vehicle_details.transmission_id')
            ->where('vehicle_details.brand_id', $request->brand_id)
            ->where('vehicle_details.model_id', $request->model_id)
            ->where('vehicle_details.type_id', $request->type_id)
            ->where('vehicle_details.cc', $request->cc)
            ->where('vehicle_details.year', $request->year)
            ->where('vehicle_details.is_active', true)
            ->groupBy('transmission_types.id', 'transmission_types.name')
            ->orderBy('transmission_types.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transmissions,
            'message' => 'Available transmissions retrieved successfully'
        ]);
    }

    /**
     * Get available fuel types for a specific brand, model, type, CC, year and transmission.
     *
     * @OA\Get(
     *     path="/api/vehicle/selection/fuel-types",
     *     operationId="getAvailableFuelTypes",
     *     tags={"Vehicle Selection"},
     *     summary="Get fuel type options",
     *     description="Returns all distinct fuel types for a specific brand, model, type, CC, year and transmission",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="brand_id",
     *         in="query",
     *         description="Brand ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         description="Model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type_id",
     *         in="query",
     *         description="Type ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="cc",
     *         in="query",
     *         description="Engine capacity (CC)",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Year",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="transmission_id",
     *         in="query",
     *         description="Transmission ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
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
     *                     type="string",
     *                     example="Bensin"
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Available fuel types retrieved successfully")
     *         )
     *     )
     * )
     */
    public function getAvailableFuelTypes(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'type_id' => 'required|integer|exists:vehicle_types,id',
            'cc' => 'required|integer|min:0',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 10),
            'transmission_id' => 'required|integer|exists:transmission_types,id'
        ]);

        $fuelTypes = VehicleDetail::select('fuel_type')
            ->where('brand_id', $request->brand_id)
            ->where('model_id', $request->model_id)
            ->where('type_id', $request->type_id)
            ->where('cc', $request->cc)
            ->where('year', $request->year)
            ->where('transmission_id', $request->transmission_id)
            ->where('is_active', true)
            ->whereNotNull('fuel_type')
            ->distinct()
            ->orderBy('fuel_type')
            ->pluck('fuel_type');

        return response()->json([
            'success' => true,
            'data' => $fuelTypes,
            'message' => 'Available fuel types retrieved successfully'
        ]);
    }

    /**
     * Get available market periods for a specific brand, model, type, CC, year, transmission and fuel type.
     *
     * @OA\Get(
     *     path="/api/vehicle/selection/market-periods",
     *     operationId="getAvailableMarketPeriods",
     *     tags={"Vehicle Selection"},
     *     summary="Get market period options",
     *     description="Returns all distinct market periods for a specific brand, model, type, CC, year, transmission and fuel type",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="brand_id",
     *         in="query",
     *         description="Brand ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         description="Model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type_id",
     *         in="query",
     *         description="Type ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="cc",
     *         in="query",
     *         description="Engine capacity (CC)",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Year",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="transmission_id",
     *         in="query",
     *         description="Transmission ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="fuel_type",
     *         in="query",
     *         description="Fuel type",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
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
     *                     @OA\Property(property="market_period", type="string", example="2023-2028"),
     *                     @OA\Property(property="vehicle_count", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Available market periods retrieved successfully")
     *         )
     *     )
     * )
     */
    public function getAvailableMarketPeriods(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'type_id' => 'required|integer|exists:vehicle_types,id',
            'cc' => 'required|integer|min:0',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 10),
            'transmission_id' => 'required|integer|exists:transmission_types,id',
            'fuel_type' => 'required|string|max:50'
        ]);

        $marketPeriods = VehicleDetail::select([
                'market_period',
                DB::raw('COUNT(*) as vehicle_count')
            ])
            ->where('brand_id', $request->brand_id)
            ->where('model_id', $request->model_id)
            ->where('type_id', $request->type_id)
            ->where('cc', $request->cc)
            ->where('year', $request->year)
            ->where('transmission_id', $request->transmission_id)
            ->where('fuel_type', $request->fuel_type)
            ->where('is_active', true)
            ->whereNotNull('market_period')
            ->groupBy('market_period')
            ->orderBy('market_period')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $marketPeriods,
            'message' => 'Available market periods retrieved successfully'
        ]);
    }

    /**
     * Get vehicle detail ID from complete selection.
     *
     * @OA\Get(
     *     path="/api/vehicle/selection/get-detail",
     *     operationId="getVehicleDetailFromSelection",
     *     tags={"Vehicle Selection"},
     *     summary="Get vehicle detail ID from complete selection",
     *     description="Returns vehicle detail ID (or IDs if multiple) from complete selection criteria",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="brand_id",
     *         in="query",
     *         description="Brand ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         description="Model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type_id",
     *         in="query",
     *         description="Type ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="cc",
     *         in="query",
     *         description="Engine capacity (CC)",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Year",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="transmission_id",
     *         in="query",
     *         description="Transmission ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="fuel_type",
     *         in="query",
     *         description="Fuel type",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="market_period",
     *         in="query",
     *         description="Market period",
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
     *             @OA\Property(property="message", type="string", example="Vehicle detail(s) found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No vehicle found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No vehicle found with the specified criteria")
     *         )
     *     )
     * )
     */
    public function getVehicleDetailFromSelection(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'type_id' => 'required|integer|exists:vehicle_types,id',
            'cc' => 'required|integer|min:0',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 10),
            'transmission_id' => 'required|integer|exists:transmission_types,id',
            'fuel_type' => 'required|string|max:50',
            'market_period' => 'nullable|string|max:50'
        ]);

        $query = VehicleDetail::with(['brand', 'model', 'type', 'transmission'])
            ->where('brand_id', $request->brand_id)
            ->where('model_id', $request->model_id)
            ->where('type_id', $request->type_id)
            ->where('cc', $request->cc)
            ->where('year', $request->year)
            ->where('transmission_id', $request->transmission_id)
            ->where('fuel_type', $request->fuel_type)
            ->where('is_active', true);

        if ($request->has('market_period') && $request->market_period) {
            $query->where('market_period', $request->market_period);
        }

        $vehicles = $query->get();

        if ($vehicles->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No vehicle found with the specified criteria'
            ], 404);
        }

        $result = $vehicles->map(function ($vehicle) {
            return [
                'id' => $vehicle->id,
                'full_name' => $vehicle->full_name,
                'specification_summary' => $vehicle->specification_summary,
                'engine_type' => $vehicle->engine_type,
                'generation' => $vehicle->generation,
                'origin' => $vehicle->origin->name ?? null,
                'market_period' => $vehicle->market_period,
                'description' => $vehicle->description
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $result->count(),
                'vehicles' => $result,
                // If only one vehicle found, include direct ID for convenience
                'vehicle_id' => $result->count() === 1 ? $result->first()['id'] : null
            ],
            'message' => $result->count() === 1 
                ? 'Vehicle detail found' 
                : 'Multiple vehicle details found'
        ]);
    }
}

// A. Pencarian Langsung (Fuzzy Search):
// text
// GET /api/vehicle/search?q=Toyota Avanza G 1.5&limit=10
// Headers: Authorization: Bearer {token}

// Response:
// {
//     "success": true,
//     "data": [
//         {
//             "id": 1,
//             "full_name": "Toyota Avanza G 1.5 2023 CVT",
//             "brand": "Toyota",
//             "model": "Avanza",
//             "type": "G 1.5",
//             "year": 2023,
//             "cc": 1496,
//             "fuel_type": "Bensin",
//             "transmission": "CVT",
//             "specification_summary": "2NR-VE • 1496 cc • Bensin • CVT"
//         }
//     ],
//     "message": "Search results retrieved successfully"
// }
// B. Step-by-Step Selection:
// Step 1: Get Brands
// text
// GET /api/vehicle/selection/brands
// Headers: Authorization: Bearer {token}

// Response:
// {
//     "success": true,
//     "data": [
//         {
//             "id": 1,
//             "name": "Toyota",
//             "vehicle_count": 20
//         },
//         {
//             "id": 2,
//             "name": "Honda",
//             "vehicle_count": 15
//         }
//     ],
//     "message": "Available brands retrieved successfully"
// }
// Step 2: Get Models (after selecting brand_id=1)
// text
// GET /api/vehicle/selection/models?brand_id=1
// Headers: Authorization: Bearer {token}

// Response:
// {
//     "success": true,
//     "data": [
//         {
//             "id": 1,
//             "name": "Avanza",
//             "vehicle_count": 15
//         },
//         {
//             "id": 2,
//             "name": "Innova",
//             "vehicle_count": 5
//         }
//     ],
//     "message": "Available models retrieved successfully"
// }
// Step 3: Get Types (after selecting brand_id=1, model_id=1)
// text
// GET /api/vehicle/selection/types?brand_id=1&model_id=1
// Headers: Authorization: Bearer {token}

// Response:
// {
//     "success": true,
//     "data": [
//         {
//             "id": 1,
//             "name": "G 1.5",
//             "vehicle_count": 10
//         },
//         {
//             "id": 2,
//             "name": "Veloz",
//             "vehicle_count": 5
//         }
//     ],
//     "message": "Available types retrieved successfully"
// }
// Step 4: Get CC Options (after selecting brand_id=1, model_id=1, type_id=1)
// text
// GET /api/vehicle/selection/cc?brand_id=1&model_id=1&type_id=1
// Headers: Authorization: Bearer {token}

// Response:
// {
//     "success": true,
//     "data": [
//         {
//             "cc": 1496,
//             "vehicle_count": 10
//         },
//         {
//             "cc": 1298,
//             "vehicle_count": 5
//         }
//     ],
//     "message": "Available CC options retrieved successfully"
// }
// Step 5: Get Years (after selecting brand_id=1, model_id=1, type_id=1, cc=1496)
// text
// GET /api/vehicle/selection/years?brand_id=1&model_id=1&type_id=1&cc=1496
// Headers: Authorization: Bearer {token}

// Response:
// {
//     "success": true,
//     "data": [
//         {
//             "year": 2023,
//             "vehicle_count": 5
//         },
//         {
//             "year": 2022,
//             "vehicle_count": 3
//         },
//         {
//             "year": 2021,
//             "vehicle_count": 2
//         }
//     ],
//     "message": "Available years retrieved successfully"
// }
// Step 6: Get Transmissions (after selecting brand_id=1, model_id=1, type_id=1, cc=1496, year=2023)
// text
// GET /api/vehicle/selection/transmissions?brand_id=1&model_id=1&type_id=1&cc=1496&year=2023
// Headers: Authorization: Bearer {token}

// Response:
// {
//     "success": true,
//     "data": [
//         {
//             "id": 1,
//             "name": "CVT",
//             "vehicle_count": 3
//         },
//         {
//             "id": 2,
//             "name": "MT",
//             "vehicle_count": 2
//         }
//     ],
//     "message": "Available transmissions retrieved successfully"
// }
// Step 7: Get Fuel Types (after selecting brand_id=1, model_id=1, type_id=1, cc=1496, year=2023, transmission_id=1)
// text
// GET /api/vehicle/selection/fuel-types?brand_id=1&model_id=1&type_id=1&cc=1496&year=2023&transmission_id=1
// Headers: Authorization: Bearer {token}

// Response:
// {
//     "success": true,
//     "data": ["Bensin"],
//     "message": "Available fuel types retrieved successfully"
// }
// Step 8: Get Market Periods (optional)
// text
// GET /api/vehicle/selection/market-periods?brand_id=1&model_id=1&type_id=1&cc=1496&year=2023&transmission_id=1&fuel_type=Bensin
// Headers: Authorization: Bearer {token}

// Response:
// {
//     "success": true,
//     "data": [
//         {
//             "market_period": "2023-2028",
//             "vehicle_count": 3
//         }
//     ],
//     "message": "Available market periods retrieved successfully"
// }
// Step 9: Get Final Vehicle Detail ID
// text
// GET /api/vehicle/selection/get-detail?brand_id=1&model_id=1&type_id=1&cc=1496&year=2023&transmission_id=1&fuel_type=Bensin&market_period=2023-2028
// Headers: Authorization: Bearer {token}

// Response:
// {
//     "success": true,
//     "data": {
//         "count": 1,
//         "vehicles": [
//             {
//                 "id": 1,
//                 "full_name": "Toyota Avanza G 1.5 2023 CVT",
//                 "specification_summary": "2NR-VE • 1496 cc • Bensin • CVT",
//                 "engine_type": "2NR-VE",
//                 "generation": "Generasi 3",
//                 "origin": "CKD",
//                 "market_period": "2023-2028",
//                 "description": "Toyota Avanza G 1.5 CVT 2023"
//             }
//         ],
//         "vehicle_id": 1
//     },
//     "message": "Vehicle detail found"
// }
// Fitur Utama:
// Fuzzy Search: Pencarian langsung dengan text

// Step-by-Step Selection: Seleksi bertahap dari Brand → Model → Type → CC → Year → Transmission → Fuel Type → Market Period

// Data yang Relevan: Setiap step hanya menampilkan data yang benar-benar ada di database berdasarkan pilihan sebelumnya

// Vehicle Count: Setiap pilihan menunjukkan jumlah vehicle yang tersedia

// Final Result: Mengembalikan vehicle detail ID setelah semua kriteria terpenuhi

// Multiple Results: Handle kasus dimana ada multiple vehicles dengan kriteria sama

// Active Filter: Hanya menampilkan vehicle yang is_active = true