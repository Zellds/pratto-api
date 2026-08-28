<?php

namespace App\Http\Controllers;

use App\Application\Moderation\UseCases\ListReports;
use App\Application\Moderation\UseCases\ReportContent;
use App\Application\Moderation\UseCases\ResolveReport;
use App\Domain\Moderation\Exceptions\CannotReportSelfException;
use App\Domain\Moderation\Exceptions\ReportedTargetNotFoundException;
use App\Domain\Moderation\Exceptions\ReportNotFoundException;
use App\Http\Requests\ResolveReportRequest;
use App\Http\Requests\StoreReportRequest;
use App\Http\Resources\ReportResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request, ReportContent $reportContent): JsonResponse
    {
        try {
            $output = $reportContent(
                $request->user()->id,
                $request->string('target_type')->value(),
                $request->string('target_id')->value(),
                $request->string('reason')->value(),
            );
        } catch (CannotReportSelfException $exception) {
            abort(422, $exception->getMessage());
        } catch (ReportedTargetNotFoundException $exception) {
            abort(404, $exception->getMessage());
        }

        return (new ReportResource($output))->response()->setStatusCode(201);
    }

    public function index(Request $request, ListReports $listReports): AnonymousResourceCollection
    {
        $data = $request->validate(['status' => ['nullable', 'string', 'in:open,reviewed,dismissed']]);

        return ReportResource::collection($listReports($data['status'] ?? 'open'));
    }

    public function update(ResolveReportRequest $request, string $report, ResolveReport $resolveReport): ReportResource
    {
        try {
            $output = $resolveReport($report, $request->user()->id, $request->string('status')->value(), $request->input('note'));
        } catch (ReportNotFoundException) {
            abort(404);
        }

        return new ReportResource($output);
    }
}
