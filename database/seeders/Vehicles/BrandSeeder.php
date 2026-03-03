<?php

namespace Database\Seeders\Vehicles;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brandFolders = File::directories(database_path('seeders/Brands'));
        
        foreach ($brandFolders as $brandFolder) {
            $brandName = basename($brandFolder);
            
            $this->command->info("Seeding brand: {$brandName}");
            
            $modelSeeders = File::files($brandFolder);
            
            foreach ($modelSeeders as $modelSeeder) {
                // Skip data folder
                if (pathinfo($modelSeeder, PATHINFO_FILENAME) === 'data') {
                    continue;
                }
                
                $className = pathinfo($modelSeeder, PATHINFO_FILENAME);
                $seederClass = "Database\\Seeders\\Brands\\{$brandName}\\{$className}";
                
                if (class_exists($seederClass)) {
                    $this->call($seederClass);
                }
            }
        }
    }
}