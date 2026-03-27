<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InspectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

        /**
     * Get vehicle detail data for inspection form
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getVehicleForInspectionForm($id): JsonResponse
    {
        try {
            // Load vehicle detail dengan relasi yang diperlukan
            $vehicleDetail = VehicleDetail::with(['brand', 'model', 'type',  'type.typeBody', 'transmission', 'features'])
                ->findOrFail($id);

            // Ambil data fitur yang aktif
            $features = $vehicleDetail->features()
                ->where('is_active', true)
                ->get()
                ->pluck('name')
                ->toArray();

            // Format data untuk inspection form
            $data = [
                // Data attributes dari JSON column
                'doors' => $vehicleDetail->getCustomSpecificationsValue('doors', null),
                'drive' => $vehicleDetail->getCustomSpecificationsValue('drive', null),
                'pickup' => $vehicleDetail->getCustomSpecificationsValue('pickup', false),
                'box' => $vehicleDetail->getCustomSpecificationsValue('box', false),
                
                // Data fuel type
                'fuel_type' => $vehicleDetail->fuel_type,
                
                // Data transmission
                'transmission' => $vehicleDetail->transmission?->name,

                'segment' => $vehicleDetail->type?->typeBody?->name,
                
                'is_active' => $vehicleDetail->is_active,
                
                // Data fitur - ditambahkan di bawah is_active
                'features' => $features
            ];

            return response()->json([
                'success' => true,
                'message' => 'Vehicle detail retrieved successfully',
                'data' => $data
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle detail not found',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve vehicle detail: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    public function getVehicleDetailForInspectionForm($id): JsonResponse
    {
        try {
            // Load vehicle detail dengan relasi yang diperlukan
            $vehicleDetail = VehicleDetail::with(['brand', 'model', 'type', 'type.typeBody','transmission', 'features'])
                ->findOrFail($id);
            // Format data untuk inspection form
            $data = [
                    'brand' => [
                        'id' => $vehicleDetail->brand?->id,
                        'name' => $vehicleDetail->brand?->name,
                    ],

                    'model' => [
                        'id' => $vehicleDetail->model?->id,
                        'name' => $vehicleDetail->model?->name,
                    ],

                    'type' => [
                        'id' => $vehicleDetail->type?->id,
                        'name' => $vehicleDetail->type?->name,
                    ],

                    'year' => $vehicleDetail->year,

                    'cc' => [
                        'cc' => $vehicleDetail->cc,
                        'name' => $vehicleDetail->format_cc, // dari accessor model
                    ],

                    'transmission' => [
                        'id' => $vehicleDetail->transmission?->id,
                        'name' => $vehicleDetail->transmission?->name,
                    ],

                    'fuel_type' => $vehicleDetail->fuel_type,

                    'market_period' => $vehicleDetail->market_period,

                    'segment' => $vehicleDetail->type?->typeBody?->name,

                    'vehicle_id' => $vehicleDetail->id,

                    'vehicle_name' => $vehicleDetail->full_name, // dari accessor model
            ];

            return response()->json([
                'success' => true,
                'message' => 'Vehicle detail retrieved successfully',
                'data' => $data
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle detail not found',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve vehicle detail: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }


}
