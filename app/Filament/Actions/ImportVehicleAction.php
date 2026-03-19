<?php

namespace App\Filament\Actions;

use App\Models\TransmissionType;
use App\Models\VehicleBrand;
use App\Models\VehicleDetail;
use App\Models\VehicleModel;
use App\Services\VehicleData\VehicleDataService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;

class ImportVehicleAction extends Action
{
    public static function make(?string $name = 'import_vehicle'): static
    {
        return parent::make($name)
            ->label('Import Kendaraan')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->modalHeading('Import Data Kendaraan')
            ->modalDescription('Import data kendaraan menggunakan JSON sesuai struktur seeder.')
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Konfirmasi & Import')
            ->form([
                Wizard::make([

                    // =========================================
                    // STEP 1: INPUT JSON
                    // =========================================
                    Step::make('Input Data')
                        ->description('Paste JSON atau upload file .json')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Tabs::make('Input Method')
                                ->tabs([
                                    Tabs\Tab::make('Paste JSON')
                                        ->icon('heroicon-o-code-bracket')
                                        ->schema([
                                            Textarea::make('json_data')
                                                ->label('JSON Data')
                                                ->placeholder('Paste JSON kendaraan di sini...')
                                                ->rows(20)
                                                ->extraAttributes([
                                                    'style' => 'font-family: monospace; font-size: 12px;'
                                                ])
                                                ->live(debounce: 800)
                                                ->helperText('Format JSON harus sesuai struktur: brand, model, generations, variants.'),
                                        ]),

                                    Tabs\Tab::make('Upload File')
                                        ->icon('heroicon-o-document-arrow-up')
                                        ->schema([
                                            FileUpload::make('json_file')
                                                ->label('File JSON')
                                                ->acceptedFileTypes(['application/json', 'text/plain'])
                                                ->disk('local')
                                                ->directory('vehicle-imports')
                                                ->live()
                                                ->helperText('Upload file .json dengan struktur yang sama seperti seeder.'),
                                        ]),
                                ]),
                        ])
                        ->afterValidation(function () {
                            // Validasi akan dilakukan di step 2
                        }),

                    // =========================================
                    // STEP 2: PREVIEW & CONFIRM
                    // =========================================
                    Step::make('Preview & Konfirmasi')
                        ->description('Periksa data sebelum disimpan')
                        ->icon('heroicon-o-eye')
                        ->schema([
                            Placeholder::make('preview_content')
                                ->label('')
                                ->content(function (\Filament\Forms\Get $get): HtmlString {
                                    // Ambil JSON dari textarea atau file
                                    $jsonString = null;

                                    if (!empty($get('json_file'))) {
                                        $path = storage_path('app/local/vehicle-imports/' . $get('json_file'));
                                        if (File::exists($path)) {
                                            $jsonString = File::get($path);
                                        }
                                    } elseif (!empty($get('json_data'))) {
                                        $jsonString = $get('json_data');
                                    }

                                    if (empty($jsonString)) {
                                        return new HtmlString(self::renderAlert('warning', '⚠️ Tidak ada data', 'Kembali ke step sebelumnya dan input JSON atau upload file.'));
                                    }

                                    $data = json_decode($jsonString, true);

                                    if (json_last_error() !== JSON_ERROR_NONE) {
                                        return new HtmlString(self::renderAlert('danger', '❌ JSON Tidak Valid', 'Error: ' . json_last_error_msg()));
                                    }

                                    // Validasi struktur minimal
                                    $missingFields = [];
                                    foreach (['brand', 'model', 'generations', 'body_type', 'origin'] as $field) {
                                        if (empty($data[$field])) $missingFields[] = $field;
                                    }

                                    if (!empty($missingFields)) {
                                        return new HtmlString(self::renderAlert('danger', '❌ Struktur JSON Tidak Lengkap', 'Field wajib tidak ditemukan: <strong>' . implode(', ', $missingFields) . '</strong>'));
                                    }

                                    return new HtmlString(self::buildPreviewHtml($data));
                                }),
                        ]),
                ])
                ->skippable(false)
                ->columnSpanFull(),
            ])
            ->action(function (array $data) {
                $jsonString = null;

                if (!empty($data['json_file'])) {
                    $path = storage_path('app/local/vehicle-imports/' . $data['json_file']);
                    if (!File::exists($path)) {
                        Notification::make()->title('File tidak ditemukan')->danger()->send();
                        return;
                    }
                    $jsonString = File::get($path);
                    File::delete($path);
                } elseif (!empty($data['json_data'])) {
                    $jsonString = $data['json_data'];
                }

                if (empty($jsonString)) {
                    Notification::make()->title('Tidak ada data')->warning()->send();
                    return;
                }

                $parsed = json_decode($jsonString, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Notification::make()
                        ->title('JSON tidak valid')
                        ->body(json_last_error_msg())
                        ->danger()
                        ->send();
                    return;
                }

                try {
                    $service = new VehicleDataService();
                    $result = $service->seedFromJson($parsed);

                    $totalFeatures = 0;
                    foreach ($result['details'] as $detail) {
                        $totalFeatures += $detail->features()->count();
                    }

                    Notification::make()
                        ->title('Import Berhasil! 🎉')
                        ->body(
                            "✓ Brand: {$result['brand']->name}\n" .
                            "✓ Model: {$result['model']->name}\n" .
                            "✓ Types: " . count($result['types']) . "\n" .
                            "✓ Details: " . count($result['details']) . "\n" .
                            "✓ Features: {$totalFeatures}"
                        )
                        ->success()
                        ->duration(8000)
                        ->send();

                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Import Gagal')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

// =========================================
// BUILDER: PREVIEW HTML
// =========================================
protected static function buildPreviewHtml(array $data): string
{
    // ── CEK DUPLIKASI ──
    $existingBrand = VehicleBrand::where('name', $data['brand'])->first();
    $existingModel = $existingBrand
        ? VehicleModel::where('brand_id', $existingBrand->id)
            ->where('name', $data['model']['name'])
            ->first()
        : null;

    $stats = self::calculateStats($data);

    $html = '<div style="font-family: sans-serif; font-size: 14px;">';

    // ── SUMMARY CARDS ──
    $html .= '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;">';
    $html .= self::statCard(
        '🚗 Brand',
        $data['brand'] . ' ' . self::dbBadge($existingBrand !== null),
        '#3b82f6'
    );
    $html .= self::statCard(
        '📦 Model',
        $data['model']['name'] . ' ' . self::dbBadge($existingModel !== null),
        '#8b5cf6'
    );
    $html .= self::statCard('🏷️ Body Type', $data['body_type'], '#f59e0b');
    $html .= self::statCard('🌍 Origin', $data['origin'], '#10b981');
    $html .= '</div>';

    // ── TOTAL COUNTS ──
    $html .= '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;">';
    $html .= self::statCard('📋 Generations', $stats['total_generations'], '#6366f1');
    $html .= self::statCard('🔧 Types/Trims', $stats['total_types'], '#ec4899');
    $html .= self::statCard('📄 Details', $stats['total_details'] . ' total', '#14b8a6');
    $html .= self::statCard(
        '📄 Details Baru',
        $stats['new_details'] . ' baru / ' . $stats['duplicate_details'] . ' duplikat',
        $stats['new_details'] > 0 ? '#14b8a6' : '#94a3b8'
    );
    $html .= '</div>';

    // ── GENERATIONS LOOP ──
    foreach ($data['generations'] as $generation) {
        $yearEnd = $generation['year_end'] ?? 'Sekarang';
        $html .= '<div style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 16px; overflow: hidden;">';

        // Generation header
        $html .= '<div style="background: #1e293b; color: white; padding: 10px 16px; font-weight: 600;">';
        $html .= "🗓️ {$generation['name']} ({$generation['year_start']} - {$yearEnd})";
        $html .= '</div>';

        // ── VARIANTS LOOP ──
        foreach ($generation['variants'] as $variant) {
            $html .= '<div style="border-top: 1px solid #e5e7eb; padding: 12px 16px;">';

            // Variant header
            $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">';
            $html .= '<span style="background: #3b82f6; color: white; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">TRIM: ' . strtoupper($variant['trim']) . '</span>';
            $html .= '<span style="color: #6b7280; font-size: 12px;">Tahun: ' . implode(', ', $variant['years']) . '</span>';
            $html .= '</div>';

            // Engines
            $html .= '<div style="margin-bottom: 10px;">';
            $html .= '<strong style="font-size: 12px; color: #374151;">🚀 Mesin:</strong>';
            $html .= '<div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px;">';
            foreach ($variant['engines'] as $engine) {
                foreach ($engine['transmissions'] as $transmission) {
                    $html .= '<div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 12px;">';
                    $html .= "<strong>{$engine['engine_code']}</strong> • {$engine['cc']}cc • ";
                    $html .= "{$engine['power_hp']}HP • {$engine['torque_nm']}Nm • ";
                    $html .= "<span style='color: #7c3aed;'>{$transmission}</span>";
                    $html .= '</div>';
                }
            }
            $html .= '</div>';
            $html .= '</div>';

            // ── CEK DUPLIKASI PER DETAIL ──
            if ($existingModel) {
                $html .= '<div style="margin-bottom: 10px;">';
                $html .= '<strong style="font-size: 12px; color: #374151;">🔍 Cek Duplikasi Detail:</strong>';
                $html .= '<div style="margin-top: 6px; display: flex; flex-direction: column; gap: 4px;">';

                foreach ($variant['years'] as $year) {
                    foreach ($variant['engines'] as $engine) {
                        foreach ($engine['transmissions'] as $transmissionCode) {
                            $transmission = TransmissionType::where('name', $transmissionCode)->first();

                            $exists = $transmission && VehicleDetail::where([
                                'model_id'        => $existingModel->id,
                                'year'            => $year,
                                'cc'              => $engine['cc'],
                                'fuel_type'       => $engine['fuel_type'],
                                'transmission_id' => $transmission->id,
                            ])->exists();

                            $label  = "{$year} • {$engine['engine_code']} • {$engine['cc']}cc • {$transmissionCode}";
                            $html  .= self::detailRow($label, $exists);
                        }
                    }
                }

                $html .= '</div>';
                $html .= '</div>';
            }

            // Features
            if (!empty($variant['features'])) {
                $html .= '<div>';
                $html .= '<strong style="font-size: 12px; color: #374151;">⭐ Fitur (' . count($variant['features']) . '):</strong>';
                $html .= '<div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px;">';
                foreach ($variant['features'] as $feature) {
                    $html .= '<span style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 10px; font-size: 11px;">' . $feature . '</span>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }

            $html .= '</div>'; // end variant
        }

        $html .= '</div>'; // end generation
    }

    // ── FOOTER NOTE ──
    $isDuplicate = $stats['new_details'] === 0;
    if ($isDuplicate) {
        $html .= self::renderAlert(
            'warning',
            '⚠️ Semua data sudah ada di database',
            'Tidak ada data baru yang akan ditambahkan. Import tetap bisa dilanjutkan — data tidak akan diduplikasi.'
        );
    } else {
        $html .= self::renderAlert(
            'success',
            "✅ {$stats['new_details']} detail baru akan ditambahkan",
            "{$stats['duplicate_details']} data sudah ada dan akan dilewati (firstOrCreate)."
        );
    }

    $html .= '</div>';

    return $html;
}

// =========================================
// HELPER: CALCULATE STATS (WITH DUPE CHECK)
// =========================================
protected static function calculateStats(array $data): array
{
    $totalTypes    = 0;
    $totalDetails  = 0;
    $newDetails    = 0;
    $dupDetails    = 0;
    $totalFeatures = 0;
    $trimNames     = [];

    // Cek brand & model di DB
    $existingBrand = VehicleBrand::where('name', $data['brand'])->first();
    $existingModel = $existingBrand
        ? VehicleModel::where('brand_id', $existingBrand->id)
            ->where('name', $data['model']['name'])
            ->first()
        : null;

    foreach ($data['generations'] as $generation) {
        foreach ($generation['variants'] as $variant) {
            if (!in_array($variant['trim'], $trimNames)) {
                $trimNames[] = $variant['trim'];
                $totalTypes++;
            }

            foreach ($variant['years'] as $year) {
                foreach ($variant['engines'] as $engine) {
                    foreach ($engine['transmissions'] as $transmissionCode) {
                        $totalDetails++;

                        if ($existingModel) {
                            $transmission = TransmissionType::where('name', $transmissionCode)->first();

                            $exists = $transmission && VehicleDetail::where([
                                'model_id'        => $existingModel->id,
                                'year'            => $year,
                                'cc'              => $engine['cc'],
                                'fuel_type'       => $engine['fuel_type'],
                                'transmission_id' => $transmission->id,
                            ])->exists();

                            $exists ? $dupDetails++ : $newDetails++;
                        } else {
                            // Brand/model belum ada → semua pasti baru
                            $newDetails++;
                        }
                    }
                }
            }

            $totalFeatures += count($variant['features'] ?? []);
        }
    }

    return [
        'total_generations' => count($data['generations']),
        'total_types'       => $totalTypes,
        'total_details'     => $totalDetails,
        'new_details'       => $newDetails,
        'duplicate_details' => $dupDetails,
        'total_features'    => $totalFeatures,
    ];
}

// =========================================
// HELPER: DB BADGE
// =========================================
protected static function dbBadge(bool $exists): string
{
    if ($exists) {
        return '<span style="background: #fef3c7; color: #92400e; padding: 1px 7px; border-radius: 10px; font-size: 11px; font-weight: 600;">Sudah Ada</span>';
    }
    return '<span style="background: #dcfce7; color: #166534; padding: 1px 7px; border-radius: 10px; font-size: 11px; font-weight: 600;">Baru</span>';
}

// =========================================
// HELPER: DETAIL ROW (DUPLIKASI CHECK)
// =========================================
protected static function detailRow(string $label, bool $isDuplicate): string
{
    if ($isDuplicate) {
        return "
        <div style='display: flex; align-items: center; justify-content: space-between; 
                    background: #fefce8; border: 1px solid #fde047; 
                    border-radius: 4px; padding: 4px 10px; font-size: 11px;'>
            <span style='color: #713f12;'>⚠️ {$label}</span>
            <span style='background: #fde047; color: #713f12; padding: 1px 8px; 
                         border-radius: 8px; font-weight: 600;'>Sudah Ada</span>
        </div>";
    }

    return "
    <div style='display: flex; align-items: center; justify-content: space-between; 
                background: #f0fdf4; border: 1px solid #86efac; 
                border-radius: 4px; padding: 4px 10px; font-size: 11px;'>
        <span style='color: #14532d;'>✓ {$label}</span>
        <span style='background: #86efac; color: #14532d; padding: 1px 8px; 
                     border-radius: 8px; font-weight: 600;'>Akan Dibuat</span>
    </div>";
}

    // =========================================
    // HELPER: STAT CARD HTML
    // =========================================
    protected static function statCard(string $label, mixed $value, string $color): string
    {
        return "
        <div style='background: white; border: 1px solid #e5e7eb; border-left: 4px solid {$color}; border-radius: 6px; padding: 12px;'>
            <div style='font-size: 11px; color: #6b7280; margin-bottom: 4px;'>{$label}</div>
            <div style='font-size: 18px; font-weight: 700; color: #111827;'>{$value}</div>
        </div>";
    }

    // =========================================
    // HELPER: ALERT HTML
    // =========================================
    protected static function renderAlert(string $type, string $title, string $message): string
    {
        $colors = [
            'danger'  => ['bg' => '#fef2f2', 'border' => '#fca5a5', 'title' => '#991b1b', 'text' => '#7f1d1d'],
            'warning' => ['bg' => '#fffbeb', 'border' => '#fcd34d', 'title' => '#92400e', 'text' => '#78350f'],
            'success' => ['bg' => '#f0fdf4', 'border' => '#86efac', 'title' => '#166534', 'text' => '#14532d'],
        ];

        $c = $colors[$type] ?? $colors['warning'];

        return "
        <div style='background: {$c['bg']}; border: 1px solid {$c['border']}; border-radius: 8px; padding: 16px;'>
            <div style='font-weight: 700; color: {$c['title']}; margin-bottom: 6px;'>{$title}</div>
            <div style='color: {$c['text']}; font-size: 13px;'>{$message}</div>
        </div>";
    }
}