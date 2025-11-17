<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\ComplaintIndexRequest;
use App\Http\Requests\Complaint\StoreComplaintRequest;
use App\Http\Requests\Complaint\ComplaintMetaRequest;
use App\Http\Resources\ComplaintResource;
use App\Http\Resources\ComplaintCategoryResource;
use App\Http\Resources\DepartmentResource;
use App\Services\Complaint\ComplaintService;
use Illuminate\Http\JsonResponse;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService
    ) {
    }

    public function index(ComplaintIndexRequest $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->validated();

        $perPage = $filters['per_page'] ?? 15;
        unset($filters['per_page']);

        $paginator = $this->complaintService->listForUser($user, $filters, $perPage);

        return ComplaintResource::collection($paginator)
            ->response();
    }

    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validated();

        $attachments = $request->file('attachments', []);

        unset($data['attachments']);

        $complaint = $this->complaintService->createComplaint(
            $user,
            $data,
            $attachments
        );

        return (new ComplaintResource($complaint))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ComplaintIndexRequest $request, int $complaint): JsonResponse
    {
        $user = $request->user();

        $model = $this->complaintService->getForUser($user, $complaint);

        return (new ComplaintResource($model->load(['category', 'department', 'attachments'])))
            ->response();
    }

    public function meta(ComplaintMetaRequest $request): JsonResponse
    {
        $user = $request->user();

        $meta = $this->complaintService->getCreateMetadata($user);

        return response()->json([
            'categories'  => ComplaintCategoryResource::collection($meta['categories']),
            'departments' => DepartmentResource::collection($meta['departments']),
        ]);
    }
}
