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
     * Get available years for a specific brand, model and type.
     * 
     * URUTAN BARU: Setelah Type → Year (sebelumnya setelah CC)
     */
    public function getAvailableYears(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'type_id' => 'required|integer|exists:vehicle_types,id'
        ]);

        $years = VehicleDetail::select([
                'year',
                DB::raw('COUNT(*) as vehicle_count')
            ])
            ->where('brand_id', $request->brand_id)
            ->where('model_id', $request->model_id)
            ->where('type_id', $request->type_id)
            ->where('is_active', true)
            ->whereNotNull('year')
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
     * Get available engine capacities (CC) for a specific brand, model, type and year.
     * 
     * URUTAN BARU: Setelah Year → CC (sebelumnya setelah Type)
     */
    public function getAvailableCC(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'type_id' => 'required|integer|exists:vehicle_types,id',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 10)
        ]);

        $ccOptions = VehicleDetail::select([
                'cc',
                DB::raw('COUNT(*) as vehicle_count')
            ])
            ->where('brand_id', $request->brand_id)
            ->where('model_id', $request->model_id)
            ->where('type_id', $request->type_id)
            ->where('year', $request->year)
            ->where('is_active', true)
            ->whereNotNull('cc')
            ->groupBy('cc')
            ->orderBy('cc')
            ->get()
            ->map(function ($item) {
                return [
                    'cc' => $item->cc, // value asli
                    'name' => $item->format_cc, 
                    'vehicle_count' => $item->vehicle_count
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $ccOptions,
            'message' => 'Available CC options retrieved successfully'
        ]);
    }

    /**
     * Get available transmissions for a specific brand, model, type, year and CC.
     */
    public function getAvailableTransmissions(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'type_id' => 'required|integer|exists:vehicle_types,id',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 10),
            'cc' => 'required|integer|min:0'
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
            ->where('vehicle_details.year', $request->year)
            ->where('vehicle_details.cc', $request->cc)
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
     * Get available fuel types for a specific brand, model, type, year, cc and transmission.
     */
    public function getAvailableFuelTypes(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'type_id' => 'required|integer|exists:vehicle_types,id',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 10),
            'cc' => 'required|integer|min:0',
            'transmission_id' => 'required|integer|exists:transmission_types,id'
        ]);

        $fuelTypes = VehicleDetail::select('fuel_type')
            ->where('brand_id', $request->brand_id)
            ->where('model_id', $request->model_id)
            ->where('type_id', $request->type_id)
            ->where('year', $request->year)
            ->where('cc', $request->cc)
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
     * Get available market periods for a specific brand, model, type, year, cc, transmission and fuel type.
     */
    public function getAvailableMarketPeriods(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'type_id' => 'required|integer|exists:vehicle_types,id',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 10),
            'cc' => 'required|integer|min:0',
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
            ->where('year', $request->year)
            ->where('cc', $request->cc)
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
     */
    public function getVehicleDetailFromSelection(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:vehicle_brands,id',
            'model_id' => 'required|integer|exists:vehicle_models,id',
            'type_id' => 'required|integer|exists:vehicle_types,id',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 10),
            'cc' => 'required|integer|min:0',
            'transmission_id' => 'required|integer|exists:transmission_types,id',
            'fuel_type' => 'required|string|max:50',
            'market_period' => 'nullable|string|max:50'
        ]);

        $query = VehicleDetail::with(['brand', 'model', 'type', 'transmission'])
            ->where('brand_id', $request->brand_id)
            ->where('model_id', $request->model_id)
            ->where('type_id', $request->type_id)
            ->where('year', $request->year)
            ->where('cc', $request->cc)
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
                'vehicle_id' => $result->count() === 1 ? $result->first()['id'] : null
            ],
            'message' => $result->count() === 1 
                ? 'Vehicle detail found' 
                : 'Multiple vehicle details found'
        ]);
    }
}