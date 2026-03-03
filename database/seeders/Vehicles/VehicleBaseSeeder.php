<?php

namespace Database\Seeders\Vehicles;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class VehicleBaseSeeder extends Seeder
{
    public function run(): void
    {
        $vehicleFolders = File::directories(database_path('seeders/Vehicles'));
        
        foreach ($vehicleFolders as $vehicleFolder) {
            $vehicleName = basename($vehicleFolder);
            
            $this->command->info("Seeding vehicle: {$vehicleName}");
            
            $modelSeeders = File::files($vehicleFolder);
            
            foreach ($modelSeeders as $modelSeeder) {
                // Skip data folder
                if (pathinfo($modelSeeder, PATHINFO_FILENAME) === 'data') {
                    continue;
                }
                
                $className = pathinfo($modelSeeder, PATHINFO_FILENAME);
                $seederClass = "Database\\Seeders\\Vehicles\\{$vehicleName}\\{$className}";
                
                if (class_exists($seederClass)) {
                    $this->call($seederClass);
                }
            }
        }
    }
}