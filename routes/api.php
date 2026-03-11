<?php

use App\Http\Controllers\Api\InspectionController;
use App\Http\Controllers\Api\SwaggerController;
use App\Http\Controllers\Api\Vehicle\Search\VehicleSearchController;
use App\Http\Controllers\Api\Vehicle\TransmissionTypeController;
use App\Http\Controllers\Api\Vehicle\VehicleBrandController;
use App\Http\Controllers\Api\Vehicle\VehicleDetailController;
use App\Http\Controllers\Api\Vehicle\VehicleDetailFeatureController;
use App\Http\Controllers\Api\Vehicle\VehicleFeatureController;
use App\Http\Controllers\Api\Vehicle\VehicleModelController;
use App\Http\Controllers\Api\Vehicle\VehicleModelImageController;
use App\Http\Controllers\Api\Vehicle\VehicleOriginController;
use App\Http\Controllers\Api\Vehicle\VehicleTypeController;
use Illuminate\Support\Facades\Route;

// Vehicle Brands Routes
Route::prefix('vehicle/brands')->group(function () {

     // Read-only routes (require sanctum and read ability)
    Route::middleware(['auth:sanctum'])->group(function () {
            // Read-only routes (require sanctum and read ability)
            Route::get('/', [VehicleBrandController::class, 'index'])
                ->middleware('ability:vehicles:read');

            Route::get('/{id}', [VehicleBrandController::class, 'show'])
                ->middleware('ability:vehicles:read');

            Route::get('/{id}/models', [VehicleBrandController::class, 'models'])
                ->middleware('ability:vehicles:read');

            Route::get('/{id}/details', [VehicleBrandController::class, 'details'])
                ->middleware('ability:vehicles:read');

            Route::get('/{id}/stats', [VehicleBrandController::class, 'stats'])
                ->middleware('ability:vehicles:read');
           
    });
    // Protected routes with create/update/delete abilities
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [VehicleBrandController::class, 'store'])
            ->middleware('ability:vehicles:create');
        
        Route::put('/{id}', [VehicleBrandController::class, 'update'])
            ->middleware('ability:vehicles:update');
        
        Route::delete('/{id}', [VehicleBrandController::class, 'destroy'])
            ->middleware('ability:vehicles:delete');
    });
});


// Vehicle Models Routes
Route::prefix('vehicle/models')->group(function () {
    // Read-only routes (require sanctum and read ability)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [VehicleModelController::class, 'index'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}', [VehicleModelController::class, 'show'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}/types', [VehicleModelController::class, 'types'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}/details', [VehicleModelController::class, 'details'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}/images', [VehicleModelController::class, 'images'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}/stats', [VehicleModelController::class, 'stats'])
            ->middleware('ability:vehicles:read');
    });
    
    // Protected routes with create/update/delete abilities
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [VehicleModelController::class, 'store'])
            ->middleware('ability:vehicles:create');
        
        Route::put('/{id}', [VehicleModelController::class, 'update'])
            ->middleware('ability:vehicles:update');
        
        Route::delete('/{id}', [VehicleModelController::class, 'destroy'])
            ->middleware('ability:vehicles:delete');
    });
});


// Vehicle Types Routes
Route::prefix('vehicle/types')->group(function () {
    // Read-only routes (require sanctum and read ability)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [VehicleTypeController::class, 'index'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}', [VehicleTypeController::class, 'show'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}/details', [VehicleTypeController::class, 'details'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}/stats', [VehicleTypeController::class, 'stats'])
            ->middleware('ability:vehicles:read');
        
        // Get available body types
        Route::get('/body-types/available', [VehicleTypeController::class, 'bodyTypes'])
            ->middleware('ability:vehicles:read');
    });
    
    // Protected routes with create/update/delete abilities
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [VehicleTypeController::class, 'store'])
            ->middleware('ability:vehicles:create');
        
        Route::put('/{id}', [VehicleTypeController::class, 'update'])
            ->middleware('ability:vehicles:update');
        
        Route::delete('/{id}', [VehicleTypeController::class, 'destroy'])
            ->middleware('ability:vehicles:delete');
    });
});

// Vehicle Model Images Routes
Route::prefix('vehicle/model-images')->group(function () {
    // Read-only routes (require sanctum and read ability)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [VehicleModelImageController::class, 'index'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}', [VehicleModelImageController::class, 'show'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/angles/available', [VehicleModelImageController::class, 'angles'])
            ->middleware('ability:vehicles:read');
    });
    
    // Protected routes with create/update/delete abilities
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [VehicleModelImageController::class, 'store'])
            ->middleware('ability:vehicles:create');
        
        Route::put('/{id}', [VehicleModelImageController::class, 'update'])
            ->middleware('ability:vehicles:update');
        
        Route::delete('/{id}', [VehicleModelImageController::class, 'destroy'])
            ->middleware('ability:vehicles:delete');
        
        Route::post('/{id}/set-primary', [VehicleModelImageController::class, 'setAsPrimary'])
            ->middleware('ability:vehicles:update');
        
        Route::post('/reorder', [VehicleModelImageController::class, 'reorder'])
            ->middleware('ability:vehicles:update');
    });
});


// Vehicle Details Routes
Route::prefix('vehicle/details')->group(function () {
    // Read-only routes (require sanctum and read ability)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [VehicleDetailController::class, 'index'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}', [VehicleDetailController::class, 'show'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}/features', [VehicleDetailController::class, 'features'])
            ->middleware('ability:vehicles:read');
        
        // Endpoint baru untuk atribut
        Route::get('/{id}/attributes', [VehicleDetailController::class, 'attributes'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/stats', [VehicleDetailController::class, 'stats'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/fuel-types', [VehicleDetailController::class, 'fuelTypes'])
            ->middleware('ability:vehicles:read');
        
        // Endpoint baru untuk mendapatkan keys atribut
        Route::get('/attribute-keys', [VehicleDetailController::class, 'attributeKeys'])
            ->middleware('ability:vehicles:read');
    });
    
    // Protected routes with create/update/delete abilities
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [VehicleDetailController::class, 'store'])
            ->middleware('ability:vehicles:create');
        
        Route::put('/{id}', [VehicleDetailController::class, 'update'])
            ->middleware('ability:vehicles:update');
        
        Route::delete('/{id}', [VehicleDetailController::class, 'destroy'])
            ->middleware('ability:vehicles:delete');
        
        Route::post('/{id}/upload-image', [VehicleDetailController::class, 'uploadImage'])
            ->middleware('ability:vehicles:update');
        
        // Endpoint baru untuk mengupdate atribut
        Route::patch('/{id}/attributes', [VehicleDetailController::class, 'updateAttributes'])
            ->middleware('ability:vehicles:update');
    });
});

// Transmission Types Routes
Route::prefix('vehicle/transmissions')->group(function () {
    // Read-only routes (require sanctum and read ability)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [TransmissionTypeController::class, 'index'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/list', [TransmissionTypeController::class, 'list'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}', [TransmissionTypeController::class, 'show'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}/details', [TransmissionTypeController::class, 'details'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}/stats', [TransmissionTypeController::class, 'stats'])
            ->middleware('ability:vehicles:read');
    });
    
    // Protected routes with create/update/delete abilities
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [TransmissionTypeController::class, 'store'])
            ->middleware('ability:vehicles:create');
        
        Route::put('/{id}', [TransmissionTypeController::class, 'update'])
            ->middleware('ability:vehicles:update');
        
        Route::delete('/{id}', [TransmissionTypeController::class, 'destroy'])
            ->middleware('ability:vehicles:delete');
    });
});

// Vehicle Origins Routes
Route::prefix('vehicle/origins')->group(function () {
    // Read-only routes (require sanctum and read ability)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [VehicleOriginController::class, 'index'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/list', [VehicleOriginController::class, 'list'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/summary', [VehicleOriginController::class, 'summary'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}', [VehicleOriginController::class, 'show'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}/details', [VehicleOriginController::class, 'details'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}/stats', [VehicleOriginController::class, 'stats'])
            ->middleware('ability:vehicles:read');
    });
    
    // Protected routes with create/update/delete abilities
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [VehicleOriginController::class, 'store'])
            ->middleware('ability:vehicles:create');
        
        Route::put('/{id}', [VehicleOriginController::class, 'update'])
            ->middleware('ability:vehicles:update');
        
        Route::delete('/{id}', [VehicleOriginController::class, 'destroy'])
            ->middleware('ability:vehicles:delete');
    });
});

// Vehicle Features Routes
Route::prefix('vehicle/features')->group(function () {
    // Read-only routes (require sanctum and read ability)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [VehicleFeatureController::class, 'index'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/list', [VehicleFeatureController::class, 'list'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/popular', [VehicleFeatureController::class, 'popular'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}', [VehicleFeatureController::class, 'show'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}/details', [VehicleFeatureController::class, 'details'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}/stats', [VehicleFeatureController::class, 'stats'])
            ->middleware('ability:vehicles:read');
    });
    
    // Protected routes with create/update/delete abilities
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [VehicleFeatureController::class, 'store'])
            ->middleware('ability:vehicles:create');
        
        Route::post('/bulk', [VehicleFeatureController::class, 'bulkStore'])
            ->middleware('ability:vehicles:create');
        
        Route::put('/{id}', [VehicleFeatureController::class, 'update'])
            ->middleware('ability:vehicles:update');
        
        Route::delete('/{id}', [VehicleFeatureController::class, 'destroy'])
            ->middleware('ability:vehicles:delete');
    });
});

// Vehicle Detail Features Routes (Pivot Table)
Route::prefix('vehicle/detail-features')->group(function () {
    // Read-only routes (require sanctum and read ability)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [VehicleDetailFeatureController::class, 'index'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/stats', [VehicleDetailFeatureController::class, 'stats'])
            ->middleware('ability:vehicles:read');
        
        Route::get('/{id}', [VehicleDetailFeatureController::class, 'show'])
            ->middleware('ability:vehicles:read');
    });
    
    // Protected routes with create/update/delete abilities
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [VehicleDetailFeatureController::class, 'store'])
            ->middleware('ability:vehicles:create');
        
        Route::post('/bulk', [VehicleDetailFeatureController::class, 'bulkStore'])
            ->middleware('ability:vehicles:create');
        
        Route::post('/sync/{vehicle_detail_id}', [VehicleDetailFeatureController::class, 'syncFeatures'])
            ->middleware('ability:vehicles:update');
        
        Route::delete('/{id}', [VehicleDetailFeatureController::class, 'destroy'])
            ->middleware('ability:vehicles:delete');
        
        Route::delete('/bulk', [VehicleDetailFeatureController::class, 'bulkDestroy'])
            ->middleware('ability:vehicles:delete');
    });
});


// Vehicle Search and Selection Routes
Route::prefix('vehicle')->group(function () {
    // Search endpoint (fuzzy search)
    Route::get('/search', [VehicleSearchController::class, 'search'])
        ->middleware(['auth:sanctum', 'ability:vehicles:read']);
    
    // Step-by-step selection endpoints
    Route::prefix('selection')->group(function () {
        // Step 1: Get Brands
        Route::get('/brands', [VehicleSearchController::class, 'getAvailableBrands'])
            ->middleware(['auth:sanctum', 'ability:vehicles:read']);
        
        // Step 2: Get Models (requires brand_id)
        Route::get('/models', [VehicleSearchController::class, 'getAvailableModels'])
            ->middleware(['auth:sanctum', 'ability:vehicles:read']);
        
        // Step 3: Get Types (requires brand_id, model_id)
        Route::get('/types', [VehicleSearchController::class, 'getAvailableTypes'])
            ->middleware(['auth:sanctum', 'ability:vehicles:read']);
        
        // STEP 4: GET YEARS (URUTAN BARU - setelah type, sebelum cc)
        // Requires: brand_id, model_id, type_id
        Route::get('/years', [VehicleSearchController::class, 'getAvailableYears'])
            ->middleware(['auth:sanctum', 'ability:vehicles:read']);
        
        // STEP 5: GET CC (URUTAN BARU - setelah year)
        // Requires: brand_id, model_id, type_id, year
        Route::get('/cc', [VehicleSearchController::class, 'getAvailableCC'])
            ->middleware(['auth:sanctum', 'ability:vehicles:read']);
        
        // Step 6: Get Transmissions (requires brand_id, model_id, type_id, year, cc)
        Route::get('/transmissions', [VehicleSearchController::class, 'getAvailableTransmissions'])
            ->middleware(['auth:sanctum', 'ability:vehicles:read']);
        
        // Step 7: Get Fuel Types (requires brand_id, model_id, type_id, year, cc, transmission_id)
        Route::get('/fuel-types', [VehicleSearchController::class, 'getAvailableFuelTypes'])
            ->middleware(['auth:sanctum', 'ability:vehicles:read']);
        
        // Step 8: Get Market Periods (requires brand_id, model_id, type_id, year, cc, transmission_id, fuel_type)
        Route::get('/market-periods', [VehicleSearchController::class, 'getAvailableMarketPeriods'])
            ->middleware(['auth:sanctum', 'ability:vehicles:read']);
        
        // Step 9: Get Final Vehicle Detail ID (requires all previous params)
        Route::get('/get-detail', [VehicleSearchController::class, 'getVehicleDetailFromSelection'])
            ->middleware(['auth:sanctum', 'ability:vehicles:read']);
    });
});
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Inspection routes
    Route::prefix('inspection')->middleware(['auth:sanctum'])->group(function () {
        // Get vehicle data for inspection
        Route::get('/form/vehicle/{id}', [InspectionController::class, 'getVehicleForInspectionForm'])
            ->middleware(['ability:vehicles:read']);
        // Get vehicle data for inspection
        Route::get('/form/vehicle-detail/{id}', [InspectionController::class, 'getVehicleDetailForInspectionForm'])
            ->middleware(['ability:vehicles:read']);
        
    });
});

