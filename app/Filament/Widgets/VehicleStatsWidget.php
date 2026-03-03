<?php

namespace App\Filament\Widgets;

use App\Models\VehicleBrand;
use App\Models\VehicleDetail;
use App\Models\VehicleModel;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VehicleStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Brands', VehicleBrand::count())
                ->description('Jumlah merek kendaraan')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('success'),
            
            Stat::make('Total Models', VehicleModel::count())
                ->description('Jumlah model kendaraan')
                ->descriptionIcon('heroicon-m-cube')
                ->color('warning'),
            
            Stat::make('Total Vehicles', VehicleDetail::count())
                ->description('Jumlah kendaraan')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),
        ];
    }
}