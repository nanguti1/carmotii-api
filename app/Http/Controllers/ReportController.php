<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Available reports',
                'reports' => [
                    [
                        'id' => 'revenue',
                        'name' => 'Monthly Revenue Report',
                        'description' => 'Detailed breakdown of monthly revenue including booking and subscription payments',
                        'type' => 'financial',
                    ],
                    [
                        'id' => 'user_activity',
                        'name' => 'User Activity Report',
                        'description' => 'Analysis of user registration, activity, and retention metrics',
                        'type' => 'analytics',
                    ],
                    [
                        'id' => 'booking_summary',
                        'name' => 'Booking Summary Report',
                        'description' => 'Comprehensive overview of booking trends and statistics',
                        'type' => 'operational',
                    ],
                    [
                        'id' => 'car_performance',
                        'name' => 'Car Performance Report',
                        'description' => 'Performance metrics and analytics for all listed cars',
                        'type' => 'operational',
                    ],
                    [
                        'id' => 'financial_summary',
                        'name' => 'Financial Summary Report',
                        'description' => 'Complete financial overview including revenue, expenses, and profit',
                        'type' => 'financial',
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'report_type' => 'required|string|in:revenue,user_activity,booking_summary,car_performance,financial_summary',
                'period' => 'nullable|string|in:daily,weekly,monthly,yearly',
                'month' => 'nullable|date_format:Y-m',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $reportType = $request->get('report_type');
            $period = $request->get('period', 'monthly');
            $month = $request->get('month');

            switch ($reportType) {
                case 'revenue':
                    $report = $this->reportService->generateRevenueReport($month);
                    break;
                case 'user_activity':
                    $report = $this->reportService->generateUserActivityReport($period);
                    break;
                case 'booking_summary':
                    $report = $this->reportService->generateBookingSummaryReport($period);
                    break;
                case 'car_performance':
                    $report = $this->reportService->generateCarPerformanceReport();
                    break;
                case 'financial_summary':
                    $report = $this->reportService->generateFinancialSummaryReport($period);
                    break;
                default:
                    throw ValidationException::withMessages([
                        'report_type' => ['Invalid report type.']
                    ]);
            }

            return response()->json([
                'message' => 'Report generated successfully',
                'report' => $report
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function download(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'report_type' => 'required|string|in:revenue,user_activity,booking_summary,car_performance,financial_summary',
                'period' => 'nullable|string|in:daily,weekly,monthly,yearly',
                'month' => 'nullable|date_format:Y-m',
                'format' => 'nullable|string|in:csv,pdf',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $reportType = $request->get('report_type');
            $period = $request->get('period', 'monthly');
            $month = $request->get('month');
            $format = $request->get('format', 'csv');

            // Generate report
            switch ($reportType) {
                case 'revenue':
                    $report = $this->reportService->generateRevenueReport($month);
                    break;
                case 'user_activity':
                    $report = $this->reportService->generateUserActivityReport($period);
                    break;
                case 'booking_summary':
                    $report = $this->reportService->generateBookingSummaryReport($period);
                    break;
                case 'car_performance':
                    $report = $this->reportService->generateCarPerformanceReport();
                    break;
                case 'financial_summary':
                    $report = $this->reportService->generateFinancialSummaryReport($period);
                    break;
                default:
                    throw ValidationException::withMessages([
                        'report_type' => ['Invalid report type.']
                    ]);
            }

            // Export to file
            $filename = "{$reportType}_{$period}_{$month}_" . date('Y-m-d_H-i-s');
            
            if ($format === 'csv') {
                $filepath = $this->reportService->exportToCSV($report['data'], $filename);
            } else {
                // For PDF, you would use a PDF library like DomPDF
                $filepath = "reports/{$filename}.pdf";
                Storage::disk('local')->put($filepath, json_encode($report));
            }

            return response()->json([
                'message' => 'Report exported successfully',
                'download_url' => "/api/reports/download-file/{$filepath}",
                'filename' => "{$filename}.{$format}",
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadFile(string $filepath): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        try {
            if (!Storage::disk('local')->exists($filepath)) {
                abort(404, 'File not found');
            }

            $fileContents = Storage::disk('local')->get($filepath);
            $filename = basename($filepath);

            return response()->streamDownload($fileContents, $filename);
        } catch (\Exception $e) {
            abort(500, 'Failed to download file');
        }
    }
}
