<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreSampleXRequest;
use App\Http\Requests\Tenant\UpdateSampleXRequest;
use App\Models\SampleX;
use Illuminate\Http\JsonResponse;

class SampleXController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(SampleX::query()->latest()->get());
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Create SampleX resource.',
        ]);
    }

    public function store(StoreSampleXRequest $request): JsonResponse
    {
        $sampleX = SampleX::query()->create($request->validated());

        return response()->json($sampleX, 201);
    }

    public function show(SampleX $sampleX): JsonResponse
    {
        return response()->json($sampleX);
    }

    public function edit(SampleX $sampleX): JsonResponse
    {
        return response()->json($sampleX);
    }

    public function update(UpdateSampleXRequest $request, SampleX $sampleX): JsonResponse
    {
        $sampleX->update($request->validated());

        return response()->json($sampleX);
    }

    public function destroy(SampleX $sampleX): JsonResponse
    {
        $sampleX->delete();

        return response()->json([
            'message' => 'SampleX deleted successfully.',
        ]);
    }
}
