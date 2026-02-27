<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Inertia\Inertia;

class DashBoardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $stats = $this->dashboardService->getStats();
        $chart = $this->dashboardService->getChartData();

        return Inertia::render('Admin/Dashboard', [
            'totalRevenue' => $stats['totalRevenue'],
            'newOrdersCount' => $stats['newOrdersCount'],
            'lowStockCount' => $stats['lowStockCount'],
            'totalUsersCount' => $stats['totalUsersCount'],
            'chartLabels' => $chart['labels'],
            'chartData' => $chart['data'],
        ]);
    }
}