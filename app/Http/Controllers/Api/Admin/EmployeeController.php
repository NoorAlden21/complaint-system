<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService
    ) {
    }

    public function index(): JsonResponse
    {
        $employees = $this->employeeService->getAllEmployees();

        return response()->json([
            'data' => EmployeeResource::collection($employees),
        ]);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->employeeService->createEmployee($request->validated());

        return response()->json([
            'message'  => __('employees.created_successfully'),
            'data'     => new EmployeeResource($employee),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $employee = $this->employeeService->getEmployeeById($id);

        return response()->json([
            'data' => new EmployeeResource($employee),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, int $id): JsonResponse
    {
        $employee = $this->employeeService->updateEmployee($id, $request->validated());

        return response()->json([
            'message'  => __('employees.updated_successfully'),
            'data'     => new EmployeeResource($employee),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->employeeService->deleteEmployee($id);

        return response()->json([
            'message'  => __('employees.deleted_successfully'),
        ]);
    }
}
