<?php

namespace App\Services\VehicleData;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use App\Models\VehicleDetail;
use App\Models\TransmissionType;
use App\Models\VehicleOrigin;
use App\Models\VehicleFeature;
use App\Models\VehicleTypeBody;

class VehicleDataService
{
    protected $transmissionMap = [];
    protected $originMap = [];
    protected $featureMap = [];
    protected $typeBodyMap = [];

    public function __construct()
    {
        $this->loadMaps();
    }

    protected function loadMaps(): void
    {
        $this->transmissionMap = TransmissionType::all()->keyBy('name');
        $this->originMap = VehicleOrigin::all()->keyBy('name');
        $this->featureMap = VehicleFeature::all()->keyBy('name');
        $this->typeBodyMap = VehicleTypeBody::all()->keyBy('code');
    }

    public function seedFromJson(array $data): array
    {
        $result = [
            'brand' => null,
            'model' => null,
            'types' => [],
            'details' => [],
            'features' => [],
        ];

        /** ===============================
         *  1. BRAND
         *  =============================== */
        $brand = VehicleBrand::firstOrCreate(
            ['name' => $data['brand']],
            ['country' => $this->getCountryFromBrand($data['brand'])]
        );
        $result['brand'] = $brand;

        /** ===============================
         *  2. MODEL
         *  =============================== */
        $model = VehicleModel::firstOrCreate(
            [
                'brand_id' => $brand->id,
                'name' => $data['model']['name'],
            ],
            [
                'description' => $data['model']['description'] ?? null,
            ]
        );
        $result['model'] = $model;

        /** ===============================
         *  3. ORIGIN
         *  =============================== */
        $origin = $this->originMap[$data['origin']]
            ?? VehicleOrigin::firstOrCreate(['name' => $data['origin']]);

        /** ===============================
         *  4. BODY TYPE
         *  =============================== */
        $typeBody = $this->getOrCreateTypeBody($data['body_type']);

        /** ===============================
         *  5. GENERATION LOOP
         *  =============================== */
        foreach ($data['generations'] as $generation) {
            foreach ($generation['variants'] as $variant) {

                // Gabungkan atribut dari berbagai level
                $specifications = array_merge(
                    $data['specifications'] ?? [], // Global specifications getCustomS specifications Value
                    $generation['specifications'] ?? [], // Generation specifications
                    $variant['specifications'] ?? [], // Variant specifications
                    ['trim' => $variant['trim']] // Tambahkan trim ke specifications
                );

                $type = $this->createOrUpdateType(
                    modelId: $model->id,
                    trim: $variant['trim'],
                    typeBodyId: $typeBody->id,
                );
        
                foreach ($variant['years'] as $year) {
                    foreach ($variant['engines'] as $engine) {
                        foreach ($engine['transmissions'] as $transmissionCode) {

                            $vehicleDetail = $this->createVehicleDetail(
                                $brand->id,
                                $model->id,
                                $type->id,
                                $year,
                                $engine,
                                $transmissionCode,
                                $origin->id,
                                $generation['name'],
                                $variant['trim'],
                                $specifications, // Kirim specifications untuk description
                                $variant['features'] ?? [] // Kirim features untuk description
                                
                            );

                            // Tambahkan fitur jika ada
                            if (isset($variant['features'])) {
                                $this->attachFeatures($vehicleDetail, $variant['features']);
                            }
                            
                            // Tambahkan ke result
                            $result['details'][] = $vehicleDetail;
                        }
                    }
                }
                
                $result['types'][] = $type;
            }
        }

        return $result;
    }

    /** ======================================================
     *  ATTACH FEATURES TO VEHICLE DETAIL
     *  ====================================================== */
    protected function attachFeatures(VehicleDetail $vehicleDetail, array $featureNames): void
    {
        $featureIds = [];
        
        foreach ($featureNames as $featureName) {
            // Cari di cache dulu
            if (isset($this->featureMap[$featureName])) {
                $feature = $this->featureMap[$featureName];
            } else {
                // Jika tidak ada di cache, cari atau buat baru
                $feature = VehicleFeature::firstOrCreate(
                    ['name' => $featureName],
                    [
                        'description' => $this->generateFeatureDescription($featureName),
                        'is_active' => true
                    ]
                );
                // Update cache
                $this->featureMap[$feature->name] = $feature;
            }
            
            $featureIds[] = $feature->id;
        }
        
        // Sync features (hindari duplikat)
        $vehicleDetail->features()->sync($featureIds, false);
    }

    /** ======================================================
     *  VEHICLE TYPE (TRIM ONLY)
     *  ====================================================== */
    protected function createOrUpdateType(
        int $modelId,
        string $trim,
        int $typeBodyId,
        
    ): VehicleType {
        return VehicleType::updateOrCreate(
            [
                'model_id' => $modelId,
                'name' => $this->normalizeTrimName($trim),
            ],
            [
                'type_body_id' => $typeBodyId,
               
                'is_active' => true,
            ]
        );
    }

    /** ======================================================
     *  VEHICLE DETAIL
     *  ====================================================== */
    protected function createVehicleDetail(
        int $brandId,
        int $modelId,
        int $typeId,
        int $year,
        array $engine,
        string $transmissionCode,
        int $originId,
        string $generation,
        string $trim,
        // array $specifications = [], // Tambahkan parameter specifications
        array $specifications = [], // Terima specifications langsung dari JSON
        array $features = [] // Tambahkan parameter features
    ): VehicleDetail {
        $transmission = $this->transmissionMap[$transmissionCode]
            ?? TransmissionType::firstOrCreate(['name' => $transmissionCode]);

        return VehicleDetail::firstOrCreate(
            [
                'brand_id' => $brandId,
                'model_id' => $modelId,
                'type_id' => $typeId,
                'year' => $year,
                'cc' => $engine['cc'],
                'fuel_type' => $engine['fuel_type'],
                'transmission_id' => $transmission->id,
                'origin_id' => $originId,
            ],
            [
                'engine_type' => $engine['engine_code'] ?? null,
                'generation' => $generation,
                'market_period' => $this->calculateMarketPeriod($year),
                'description' => $this->generateHtmlDescription(
                    $brandId,
                    $modelId,
                    $typeId,
                    $year,
                    $engine,
                    $transmissionCode,
                    $trim,
                    $specifications,
                    $features,
                    $generation
                ),
                'specifications' => $this->buildTypespecifications($specifications), // Gunakan specifications dari JSON
                'is_active' => true,
            ]
        );
    }

    /** ======================================================
     *  HTML DESCRIPTION GENERATOR
     *  ====================================================== */
    protected function generateHtmlDescription(
        int $brandId,
        int $modelId,
        int $typeId,
        int $year,
        array $engine,
        string $transmissionCode,
        string $trim,
        array $specifications,
        array $features,
        string $generation
    ): string {
        $brand = VehicleBrand::find($brandId)?->name;
        $model = VehicleModel::find($modelId)?->name;
        $type = VehicleType::find($typeId)?->name;
        
        $engineSize = round($engine['cc'] / 1000, 1) . 'L';
        $transmissionText = $this->getTransmissionText($transmissionCode);
        
        // Kategorikan atribut
        $specCategories = $this->categorizespecifications($specifications);
        $engineSpecs = $this->extractEngineSpecs($engine);
        
        $html = "<div class='vehicle-specifications'>";
        
        // HEADER
        $html .= "<h3>{$brand} {$model} {$type} - {$year}</h3>";
        $html .= "<p class='vehicle-subtitle'>{$generation} • {$trim} Trim</p>";
        
        // OVERVIEW
        $html .= "<div class='spec-section'>";
        $html .= "<h4>📋 Overview</h4>";
        $html .= "<p><strong>${brand} ${model} ${type} ${year} ${engineSize} ${transmissionText}.</strong> ";
        $html .= "Mesin {$engine['engine_code']} berbahan bakar {$engine['fuel_type']}. ";
        if (isset($specifications['segment'])) {
            $html .= "Kendaraan segment {$specifications['segment']}. ";
        }
        $html .= "</p>";
        $html .= "</div>";
        
        // ENGINE SPECIFICATIONS
        if (!empty($engineSpecs)) {
            $html .= "<div class='spec-section'>";
            $html .= "<h4>🚀 Spesifikasi Mesin</h4>";
            $html .= "<table class='spec-table'>";
            foreach ($engineSpecs as $key => $value) {
                $html .= "<tr>";
                $html .= "<td><strong>" . $this->formatKey($key) . "</strong></td>";
                $html .= "<td>" . $this->formatValue($value) . "</td>";
                $html .= "</tr>";
            }
            $html .= "</table>";
            $html .= "</div>";
        }
        
        // DIMENSIONS
        if (isset($specCategories['dimensions'])) {
            $html .= "<div class='spec-section'>";
            $html .= "<h4>📐 Dimensi Kendaraan</h4>";
            $html .= "<table class='spec-table'>";
            foreach ($specCategories['dimensions'] as $key => $value) {
                $html .= "<tr>";
                $html .= "<td><strong>" . $this->formatKey($key) . "</strong></td>";
                $html .= "<td>" . $this->formatValue($value) . "</td>";
                $html .= "</tr>";
            }
            $html .= "</table>";
            $html .= "</div>";
        }
        
        // SUSPENSION & BRAKES
        if (isset($specCategories['suspension_brakes'])) {
            $html .= "<div class='spec-section'>";
            $html .= "<h4>⚙️ Suspensi & Rem</h4>";
            $html .= "<table class='spec-table'>";
            foreach ($specCategories['suspension_brakes'] as $key => $value) {
                $html .= "<tr>";
                $html .= "<td><strong>" . $this->formatKey($key) . "</strong></td>";
                $html .= "<td>" . $this->formatValue($value) . "</td>";
                $html .= "</tr>";
            }
            $html .= "</table>";
            $html .= "</div>";
        }
        
        // WHEELS & TIRES
        if (isset($specCategories['wheels_tires'])) {
            $html .= "<div class='spec-section'>";
            $html .= "<h4>🛞 Roda & Ban</h4>";
            $html .= "<table class='spec-table'>";
            foreach ($specCategories['wheels_tires'] as $key => $value) {
                $html .= "<tr>";
                $html .= "<td><strong>" . $this->formatKey($key) . "</strong></td>";
                $html .= "<td>" . $this->formatValue($value) . "</td>";
                $html .= "</tr>";
            }
            $html .= "</table>";
            $html .= "</div>";
        }
        
        // INTERIOR
        if (isset($specCategories['interior'])) {
            $html .= "<div class='spec-section'>";
            $html .= "<h4>🧵 Interior</h4>";
            $html .= "<table class='spec-table'>";
            foreach ($specCategories['interior'] as $key => $value) {
                $html .= "<tr>";
                $html .= "<td><strong>" . $this->formatKey($key) . "</strong></td>";
                $html .= "<td>" . $this->formatValue($value) . "</td>";
                $html .= "</tr>";
            }
            $html .= "</table>";
            $html .= "</div>";
        }
        
        // EXTERIOR
        if (isset($specCategories['exterior'])) {
            $html .= "<div class='spec-section'>";
            $html .= "<h4>🚗 Eksterior</h4>";
            $html .= "<table class='spec-table'>";
            foreach ($specCategories['exterior'] as $key => $value) {
                $html .= "<tr>";
                $html .= "<td><strong>" . $this->formatKey($key) . "</strong></td>";
                $html .= "<td>" . $this->formatValue($value) . "</td>";
                $html .= "</tr>";
            }
            $html .= "</table>";
            $html .= "</div>";
        }
        
        // SAFETY
        if (isset($specCategories['safety'])) {
            $html .= "<div class='spec-section'>";
            $html .= "<h4>🛡️ Keamanan</h4>";
            $html .= "<table class='spec-table'>";
            foreach ($specCategories['safety'] as $key => $value) {
                $html .= "<tr>";
                $html .= "<td><strong>" . $this->formatKey($key) . "</strong></td>";
                $html .= "<td>" . $this->formatValue($value) . "</td>";
                $html .= "</tr>";
            }
            $html .= "</table>";
            $html .= "</div>";
        }
        
        // OTHER specifications
        if (isset($specCategories['other'])) {
            $html .= "<div class='spec-section'>";
            $html .= "<h4>📊 Informasi Lainnya</h4>";
            $html .= "<table class='spec-table'>";
            foreach ($specCategories['other'] as $key => $value) {
                $html .= "<tr>";
                $html .= "<td><strong>" . $this->formatKey($key) . "</strong></td>";
                $html .= "<td>" . $this->formatValue($value) . "</td>";
                $html .= "</tr>";
            }
            $html .= "</table>";
            $html .= "</div>";
        }
        
        // FEATURES SUMMARY
        if (!empty($features)) {
            $html .= "<div class='spec-section'>";
            $html .= "<h4>⭐ Fitur Utama</h4>";
            $html .= "<ul class='features-list'>";
            foreach ($features as $feature) {
                $featureDesc = $this->generateFeatureDescription($feature);
                $html .= "<li><strong>{$feature}:</strong> {$featureDesc}</li>";
            }
            $html .= "</ul>";
            $html .= "</div>";
        }
        
        // FOOTER
        $html .= "<div class='spec-footer'>";
        $html .= "<p><em>Spesifikasi ini berdasarkan data model {$brand} {$model} tahun {$year} dengan varian {$trim}.</em></p>";
        $html .= "<p><em>Data diperbarui secara berkala untuk akurasi informasi.</em></p>";
        $html .= "</div>";
        
        $html .= "</div>";
        
        return $html;
    }

    /** ======================================================
     *  HELPER METHODS FOR HTML DESCRIPTION
     *  ====================================================== */
    protected function categorizespecifications(array $specifications): array
    {
        $categories = [
            'dimensions' => [],
            'suspension_brakes' => [],
            'wheels_tires' => [],
            'interior' => [],
            'exterior' => [],
            'safety' => [],
            'other' => []
        ];
        
        $dimensionKeys = ['length', 'width', 'height', 'wheelbase', 'ground_clearance', 'kerb_weight', 'turning_radius', 'drag_coefficient', 'boot_space', 'fuel_tank'];
        $suspensionKeys = ['suspension', 'brake', 'platform', 'drive'];
        $wheelKeys = ['wheel', 'tyre', 'alloy', 'rim'];
        $interiorKeys = ['seat', 'steering', 'audio', 'infotainment', 'air_con', 'camera', 'sensor', 'mirror', 'window', 'storage', 'armrest', 'visor'];
        $exteriorKeys = ['body_kit', 'spoiler', 'grille', 'headlight', 'fog_lamp', 'drl', 'chrome', 'roof_rail', 'badge', 'paint'];
        $safetyKeys = ['airbag', 'abs', 'ebd', 'vsc', 'hsa', 'isofix', 'tss', 'pcs', 'rcta', 'bsm', 'ncap', 'safety_rating', 'child_protection', 'immobilizer'];
        
        foreach ($specifications as $key => $value) {
            $keyLower = strtolower($key);
            $categorized = false;
            
            // Check each category
            foreach ($dimensionKeys as $dimKey) {
                if (str_contains($keyLower, $dimKey)) {
                    $categories['dimensions'][$key] = $value;
                    $categorized = true;
                    break;
                }
            }
            
            if (!$categorized) {
                foreach ($suspensionKeys as $susKey) {
                    if (str_contains($keyLower, $susKey)) {
                        $categories['suspension_brakes'][$key] = $value;
                        $categorized = true;
                        break;
                    }
                }
            }
            
            if (!$categorized) {
                foreach ($wheelKeys as $wheelKey) {
                    if (str_contains($keyLower, $wheelKey)) {
                        $categories['wheels_tires'][$key] = $value;
                        $categorized = true;
                        break;
                    }
                }
            }
            
            if (!$categorized) {
                foreach ($interiorKeys as $intKey) {
                    if (str_contains($keyLower, $intKey)) {
                        $categories['interior'][$key] = $value;
                        $categorized = true;
                        break;
                    }
                }
            }
            
            if (!$categorized) {
                foreach ($exteriorKeys as $extKey) {
                    if (str_contains($keyLower, $extKey)) {
                        $categories['exterior'][$key] = $value;
                        $categorized = true;
                        break;
                    }
                }
            }
            
            if (!$categorized) {
                foreach ($safetyKeys as $safetyKey) {
                    if (str_contains($keyLower, $safetyKey)) {
                        $categories['safety'][$key] = $value;
                        $categorized = true;
                        break;
                    }
                }
            }
            
            if (!$categorized) {
                $categories['other'][$key] = $value;
            }
        }
        
        // Remove empty categories
        return array_filter($categories);
    }
    
    protected function extractEngineSpecs(array $engine): array
    {
        $specs = [];
        $engineKeys = [
            'cc', 'engine_code', 'fuel_type', 'power_hp', 'torque_nm', 
            'cylinders', 'valves', 'fuel_system', 'engine_position',
            'compression_ratio', 'emission_standard', 'engine_type',
            'max_power_rpm', 'max_torque_rpm'
        ];
        
        foreach ($engineKeys as $key) {
            if (isset($engine[$key]) && !empty($engine[$key])) {
                $specs[$key] = $engine[$key];
            }
        }
        
        return $specs;
    }
    
    protected function formatKey(string $key): string
    {
        // Convert snake_case or camelCase to readable text
        $key = str_replace(['_', '-'], ' ', $key);
        $key = ucwords($key);
        
        // Special cases
        $replacements = [
            'Cc' => 'Kapasitas Mesin',
            'Hp' => 'Tenaga',
            'Nm' => 'Torsi',
            'Rpm' => 'RPM',
            'Abs' => 'ABS',
            'Ebd' => 'EBD',
            'Vsc' => 'VSC',
            'Hsa' => 'HSA',
            'Tss' => 'TSS',
            'Pcs' => 'PCS',
            'Rcta' => 'RCTA',
            'Bsm' => 'BSM',
            'Ncap' => 'NCAP',
            'Drl' => 'DRL',
            'Usb' => 'USB',
            'Wifi' => 'WiFi',
            'Mid' => 'MID',
            'L' => 'Liter',
            'Mm' => 'mm',
            'Kg' => 'kg',
            'M' => 'meter'
        ];
        
        foreach ($replacements as $search => $replace) {
            $key = str_ireplace($search, $replace, $key);
        }
        
        return $key;
    }
    
    protected function formatValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        
        if (is_array($value)) {
            return implode(', ', $value);
        }
        
        // Add units for certain values
        $valueStr = (string) $value;
        
        if (is_numeric($value)) {
            // Check if it's a dimension
            if (str_contains($valueStr, 'mm') || str_contains($valueStr, 'kg') || 
                str_contains($valueStr, 'l') || str_contains($valueStr, 'm')) {
                return $valueStr;
            }
            
            // Add units based on context
            $lastKey = '';
            if ($lastKey) {
                if (str_contains(strtolower($lastKey), 'length') || 
                    str_contains(strtolower($lastKey), 'width') || 
                    str_contains(strtolower($lastKey), 'height') || 
                    str_contains(strtolower($lastKey), 'wheelbase') ||
                    str_contains(strtolower($lastKey), 'ground_clearance')) {
                    return $valueStr . ' mm';
                }
                
                if (str_contains(strtolower($lastKey), 'weight')) {
                    return $valueStr . ' kg';
                }
                
                if (str_contains(strtolower($lastKey), 'power')) {
                    return $valueStr . ' HP';
                }
                
                if (str_contains(strtolower($lastKey), 'torque')) {
                    return $valueStr . ' Nm';
                }
                
                if (str_contains(strtolower($lastKey), 'cc')) {
                    return number_format($value) . ' cc';
                }
            }
        }
        
        return $valueStr;
    }

    /** ======================================================
     *  ORIGINAL METHOD (KEEP FOR BACKWARD COMPATIBILITY)
     *  ====================================================== */
    protected function generateDescription(
        int $brandId,
        int $modelId,
        int $typeId,
        int $year,
        array $engine,
        string $transmissionCode,
        string $trim
    ): string {
        $brand = VehicleBrand::find($brandId)?->name;
        $model = VehicleModel::find($modelId)?->name;
        $type  = VehicleType::find($typeId)?->name;

        $engineSize = round($engine['cc'] / 1000, 1) . 'L';
        $transmissionText = $this->getTransmissionText($transmissionCode);

        return "{$brand} {$model} {$type} {$year} {$engineSize} {$transmissionText}. "
            . "Mesin {$engine['engine_code']} berbahan bakar {$engine['fuel_type']}.";
    }

    /** ======================================================
     *  OTHER HELPERS (UNCHANGED)
     *  ====================================================== */
    protected function generateFeatureDescription(string $featureName): string
    {
        $descriptions = [
            // Fitur Dasar & Kenyamanan
        'AC'                => 'Air Conditioner',
        'Power Steering'    => 'Power Steering System',
        'ABS'               => 'Anti-lock Braking System',
        'Dual Airbag'       => 'Dual Front Airbags',
        'Airbag'            => 'Safety Airbag System',
        'Power Window'      => 'Electric Power Windows',
        'Central Lock'      => 'Central Locking System',
        'Touchscreen'       => 'Touchscreen Display',
        'Camera Belakang'   => 'Rear View Camera',
        'Sensor Parkir'     => 'Parking Sensor System',
        'Leather Seat'      => 'Leather Upholstery Seats',
        'Sunroof'           => 'Sunroof/Moonroof',
        'Keyless Entry'     => 'Keyless Entry System',
        'Start Stop Button' => 'Push Start Button',
        'Cruise Control'    => 'Cruise Control System',
        'LED Headlight'     => 'LED Headlights',
        'Fog Lamp'          => 'Front Fog Lights',
        'Alloy Wheel'       => 'Alloy Wheels',

        // Fitur Keamanan Modern
        'EBD'               => 'Electronic Brakeforce Distribution',
        'BA'                => 'Brake Assist System',
        'VSC'               => 'Vehicle Stability Control',
        'HSA'               => 'Hill Start Assist',
        'ISOFIX'            => 'Child Seat Anchor System',
        'Blind Spot'        => 'Blind Spot Monitoring',
        
        // Fitur Interior & Teknologi
        'Digital AC'        => 'Digital Automatic Climate Control',
        'Audio Steering'    => 'Audio Steering Switch',
        'Tilt Steering'     => 'Tilt & Telescopic Steering Wheel',
        'Wireless Charge'   => 'Wireless Smartphone Charging',
        'Apple CarPlay'     => 'Apple CarPlay & Android Auto Integration',
        'Camera 360'        => '360 Degree Surround View Camera',
        
        // Fitur Eksterior
        'Retractable'       => 'Automatic Retractable Mirrors',
        'DRL'               => 'Daytime Running Lights',
        'Defogger'          => 'Rear Glass Defogger',
        
        // Fitur Premium & Tambahan (dari data sebelumnya)
        'Heated Seats'      => 'Heated Seats',
        'Memory Seats'      => 'Memory Seats',
        'Navigation System' => 'GPS Navigation System',
        'Ambient Lighting'  => 'Ambient Interior Lighting',
        'Premium Audio'     => 'Premium Audio System',
        'Dual Zone AC'      => 'Dual Zone Climate Control',
        'Bluetooth'         => 'Bluetooth Hands-free Connectivity',
        ];
        
        return $descriptions[$featureName] ?? $featureName;
    }

    protected function normalizeTrimName(string $trim): string
    {
        // Hapus angka kapasitas mesin (1.0, 1.3, 1.5, dll)
        return trim(preg_replace('/^\d+(\.\d+)?\s*/', '', $trim));
    }

    protected function calculateMarketPeriod(int $year): string
    {
        $currentYear = (int) date('Y');

        if ($year >= $currentYear) {
            return (string) $year;
        }

        return $year . '-' . ($year + 5);
    }

    protected function getTransmissionText(string $code): string
    {
        return match (strtoupper($code)) {
            'MT'           => 'Manual Transmission',
            'AT'           => 'Automatic (Torque Converter)',
            'CVT'          => 'Continuously Variable Transmission',
            'IVT'          => 'Intelligent Variable Transmission',
            'DCT'          => 'Dual Clutch Transmission',
            'DSG'          => 'Direct Shift Gearbox (DCT)',
            'AMT'          => 'Automated Manual Transmission',
            'AGS'          => 'Auto Gear Shift (AMT)',
            'DIRECT DRIVE' => 'Electric Drive Unit (Single Speed)',
            'e-CVT'       => 'Electronic-CVT (e-CVT)',
            default        => $code,
        };
    }

    protected function getCountryFromBrand(string $brand): ?string
    {
        $brands = [
            // JEPANG
            'Toyota'        => 'Jepang',
            'Honda'         => 'Jepang',
            'Suzuki'        => 'Jepang',
            'Daihatsu'      => 'Jepang',
            'Mitsubishi'    => 'Jepang',
            'Mazda'         => 'Jepang',
            'Nissan'        => 'Jepang',
            'Lexus'         => 'Jepang',
            'Subaru'        => 'Jepang',
            'Isuzu'         => 'Jepang',
            'Hino'          => 'Jepang',
            'Mitsoka'       => 'Jepang',

            // KOREA SELATAN
            'Hyundai'       => 'Korea Selatan',
            'Kia'           => 'Korea Selatan',
            'Genesis'       => 'Korea Selatan',
            'SsangYong'     => 'Korea Selatan',

            // JERMAN
            'BMW'           => 'Jerman',
            'Mercedes-Benz' => 'Jerman',
            'Volkswagen'    => 'Jerman',
            'Audi'          => 'Jerman',
            'Porsche'       => 'Jerman',
            'Opel'          => 'Jerman',
            'Smart'         => 'Jerman',

            // CHINA
            'Wuling'        => 'China',
            'MG'            => 'China',
            'Chery'         => 'China',
            'BYD'           => 'China',
            'DFSK'          => 'China',
            'Great Wall'    => 'China',
            'Haval'         => 'China',
            'Geely'         => 'China',
            'Baojun'        => 'China',
            'GAC'           => 'China',
            'NIO'           => 'China',
            'Zeekr'         => 'China',

            // EROPA LAINNYA
            'Ferrari'       => 'Italia',
            'Lamborghini'   => 'Italia',
            'Fiat'          => 'Italia',
            'Maserati'      => 'Italia',
            'Alfa Romeo'    => 'Italia',
            'Renault'       => 'Prancis',
            'Peugeot'       => 'Prancis',
            'Citroen'       => 'Prancis',
            'Bugatti'       => 'Prancis',
            'Volvo'         => 'Swedia',
            'Koenigsegg'    => 'Swedia',
            'Land Rover'    => 'Inggris',
            'Range Rover'   => 'Inggris',
            'Rolls-Royce'   => 'Inggris',
            'Bentley'       => 'Inggris',
            'Aston Martin'  => 'Inggris',
            'McLaren'       => 'Inggris',
            'Mini'          => 'Inggris',
            'Lotus'         => 'Inggris',

            // AMERIKA SERIKAT
            'Chevrolet'     => 'Amerika Serikat',
            'Ford'          => 'Amerika Serikat',
            'Tesla'         => 'Amerika Serikat',
            'Jeep'          => 'Amerika Serikat',
            'Dodge'         => 'Amerika Serikat',
            'Chrysler'      => 'Amerika Serikat',
            'Cadillac'      => 'Amerika Serikat',
            'GMC'           => 'Amerika Serikat',
            'Buick'         => 'Amerika Serikat',

            // NEGARA LAIN
            'Proton'        => 'Malaysia',
            'Perodua'       => 'Malaysia',
            'VinFast'       => 'Vietnam',
            'Holden'        => 'Australia',
            'Tata'          => 'India',
            'Mahindra'      => 'India',
            'Esemka'        => 'Indonesia',
        ];

        return $brands[$brand] ?? null;
    }

    protected function getOrCreateTypeBody(string $bodyType): VehicleTypeBody
    {
        $code = strtolower(str_replace(' ', '_', $bodyType));

        if (isset($this->typeBodyMap[$code])) {
            return $this->typeBodyMap[$code];
        }

        $typeBody = VehicleTypeBody::firstOrCreate(
            ['code' => $code],
            [
                'name' => strtoupper($bodyType),
                'description' => "{$bodyType} vehicle body type",
                'is_active' => true,
            ]
        );

        $this->typeBodyMap[$code] = $typeBody;

        return $typeBody;
    }

    protected function buildTypespecifications(array $specifications): array
    {
        // Gabungkan semua atribut yang ada di JSON
        // Pastikan nilai boolean dikonversi dengan benar
        $result = [];
        
        foreach ($specifications as $key => $value) {
            if (is_bool($value)) {
                $result[$key] = (bool) $value;
            } elseif (is_numeric($value)) {
                $result[$key] = (int) $value;
            } elseif (is_array($value)) {
                $result[$key] = $value;
            } else {
                $result[$key] = (string) $value;
            }
        }

        return $result;
    }
}