<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreModuleRequest;
use App\Http\Requests\Tenant\UpdateModuleRequest;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ModuleController extends Controller
{
    public function index(): Response
    {
        return response('THIS IS MODULE INDEX');
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Create Module resource.',
        ]);
    }

    public function store(StoreModuleRequest $request): JsonResponse
    {
        $module = Module::query()->create($request->validated());

        return response()->json($module, 201);
    }

    public function show(Module $module): JsonResponse
    {
        return response()->json($module);
    }

    public function edit(Module $module): JsonResponse
    {
        return response()->json($module);
    }

    public function update(UpdateModuleRequest $request, Module $module): JsonResponse
    {
        $module->update($request->validated());

        return response()->json($module);
    }

    public function destroy(Module $module): JsonResponse
    {
        $module->delete();

        return response()->json([
            'message' => 'Module deleted successfully.',
        ]);
    }
}
