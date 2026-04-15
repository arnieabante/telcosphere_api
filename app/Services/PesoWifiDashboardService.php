<?php

namespace App\Services;

use App\Models\PesoWifiArea;
use App\Models\PesoWifiClient;
use App\Models\PesoWifiHarvest;
use Carbon\Carbon;

class PesoWifiDashboardService
{
    // =========================
    // TOTAL AREAS
    // =========================
    public function getTotalAreas(): int
    {
        return PesoWifiArea::where('is_active', 1)->count();
    }

    // =========================
    // TOTAL RESELLERS (CLIENTS)
    // =========================
    public function getTotalResellers(): int
    {
        return PesoWifiClient::where('is_active', 1)->count();
    }

    // =========================
    // COLLECTION THIS MONTH
    // =========================
    public function getMonthlyCollections(): float
    {
        return PesoWifiHarvest::where('is_active', 1)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('owner_income');
    }

    // =========================
    // TOTAL COLLECTIONS (ALL TIME)
    // =========================
    public function getTotalCollections(): float
    {
        return PesoWifiHarvest::where('is_active', 1)
            ->sum('owner_income');
    }

    // =========================
    // CLIENT LIST (NAME + STATUS)
    // =========================
   public function getClientsStatus()
    {
        return PesoWifiClient::where('is_active', 1)
            ->with('pesoWifiArea')
            ->select('id', 'name', 'device_status', 'area_id')
            ->orderBy('name')
            ->get()
            ->map(function ($client) {

                return [
                    'name' => $client->name,
                    'status' => $client->device_status ?? 'Offline',
                    'area_name' => $client->pesoWifiArea->name ?? 'No Area',
                ];
            });
    }

    // =========================
    // MONTHLY SALES (FOR GRAPH)
    // =========================
    public function getMonthlySales()
    {
        $year = now()->year;

        $data = PesoWifiHarvest::where('is_active', 1)
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, SUM(amount_harvested) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $result = [];

        for ($i = 1; $i <= 12; $i++) {
            $result[] = [
                'month' => Carbon::create()->month($i)->format('M'),
                'total' => (float) ($data[$i] ?? 0)
            ];
        }

        return $result;
    }
}