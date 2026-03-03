<?php

namespace Database\Seeders\Vehicles\Toyota;

use App\Services\VehicleData\VehicleDataService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class VelozSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = database_path('seeders/Vehicles/Toyota/data/veloz.json');
        
        if (!File::exists($dataPath)) {
            $this->command->error("File data Veloz tidak ditemukan: {$dataPath}");
            return;
        }

        $jsonData = File::get($dataPath);
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("Error parsing JSON: " . json_last_error_msg());
            return;
        }

        $service = new VehicleDataService();
        $result = $service->seedFromJson($data);

        $this->command->info("✓ Brand: {$result['brand']->name}");
        $this->command->info("✓ Model: {$result['model']->name}");
        $this->command->info("✓ Types created: " . count($result['types']));

        $this->command->info("✓ Details created: " . count($result['details']));
        
        // Tampilkan fitur yang diattach
        $totalFeatures = 0;
        foreach ($result['details'] as $detail) {
            $totalFeatures += $detail->features()->count();
        }
        $this->command->info("✓ Total features attached: {$totalFeatures}");
    }
}