<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanDocument;
use App\Services\AuditService;
use App\Support\TenantPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoanDocumentController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function store(Request $request, string $tenant, Loan $loan): RedirectResponse
    {
        abort_unless(
            $request->user()?->hasTenantPermission(TenantPermissions::LOAN_DOCUMENTS_UPLOAD, ['tenant_admin', 'branch_manager', 'loan_officer']),
            403,
            'This action is unauthorized.'
        );

        $this->authorize('view', $loan);

        $validated = $request->validate([
            'document_type' => ['required', 'string', 'max:100'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $request->file('file');
        ['path' => $path, 'mime_type' => $mimeType] = $this->storeUploadedFile(
            $file,
            'loan-documents/'.$loan->id,
        );

        $document = LoanDocument::query()->create([
            'loan_id' => $loan->id,
            'uploaded_by' => $request->user()?->id,
            'document_type' => $validated['document_type'],
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => (int) $file->getSize(),
            'mime_type' => $mimeType,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->auditService->log('created', $document, [], $document->toArray());

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function destroy(Request $request, string $tenant, LoanDocument $document): RedirectResponse
    {
        abort_unless(
            $request->user()?->hasTenantPermission(TenantPermissions::LOAN_DOCUMENTS_DELETE, ['tenant_admin']),
            403,
            'This action is unauthorized.'
        );

        $document->loadMissing('loan');
        $this->authorize('view', $document->loan);

        $oldValues = $document->toArray();

        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $this->auditService->log('deleted', $document, $oldValues, []);
        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    public function download(Request $request, string $tenant, LoanDocument $document)
    {
        abort_unless(
            $request->user()?->hasTenantPermission(TenantPermissions::LOAN_DOCUMENTS_VIEW, ['tenant_admin', 'branch_manager', 'loan_officer', 'viewer']),
            403,
            'This action is unauthorized.'
        );

        $document->loadMissing('loan');
        $this->authorize('view', $document->loan);
        abort_unless(Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    /**
     * @return array{path:string,mime_type:string}
     */
    private function storeUploadedFile(UploadedFile $file, string $directory): array
    {
        $sourcePath = $file->getRealPath() ?: $file->getPathname();

        if (! is_string($sourcePath) || trim($sourcePath) === '' || ! is_file($sourcePath)) {
            throw ValidationException::withMessages([
                'file' => 'Uploaded file could not be read. Please try selecting the file again.',
            ]);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $fileName = trim((string) $file->hashName(), '/');

        if ($fileName === '') {
            $fileName = Str::random(40).($extension !== '' ? '.'.$extension : '');
        }

        $path = trim($directory.'/'.$fileName, '/');
        $stream = fopen($sourcePath, 'r');

        if ($stream === false) {
            throw ValidationException::withMessages([
                'file' => 'Uploaded file could not be opened. Please try again.',
            ]);
        }

        try {
            $stored = Storage::disk('public')->put($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $stored) {
            throw ValidationException::withMessages([
                'file' => 'The document could not be stored. Please try again.',
            ]);
        }

        return [
            'path' => $path,
            'mime_type' => (string) ($file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream'),
        ];
    }
}
