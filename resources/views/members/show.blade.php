@extends('layouts.tenant')

@section('title', 'Member Profile')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $loanStatusClasses = [
        'active' => 'primary',
        'fully_paid' => 'success',
        'overdue' => 'danger',
        'restructured' => 'warning text-dark',
    ];
    $memberDocumentTypes = [
        'Valid ID',
        'Barangay Certificate',
        'Income Statement',
        'Co-Maker Agreement',
        'Promissory Note',
        'Other',
    ];
    $user = auth()->user();
    $canViewDocuments = $user?->hasTenantPermission(\App\Support\TenantPermissions::MEMBER_DOCUMENTS_VIEW, ['tenant_admin', 'branch_manager', 'loan_officer', 'viewer']) ?? false;
    $canUploadDocuments = $user?->hasTenantPermission(\App\Support\TenantPermissions::MEMBER_DOCUMENTS_UPLOAD, ['tenant_admin', 'branch_manager', 'loan_officer']) ?? false;
    $canDeleteDocuments = $user?->hasTenantPermission(\App\Support\TenantPermissions::MEMBER_DOCUMENTS_DELETE, ['tenant_admin']) ?? false;
    $showDocumentsSection = $canViewDocuments || $canUploadDocuments || $canDeleteDocuments;
    $openDocumentModal = $errors->has('document_type') || $errors->has('file') || $errors->has('notes');
@endphp

<div
    x-data="{
        uploadDocumentOpen: {{ $openDocumentModal ? 'true' : 'false' }},
        imagePreview: null,
        selectedFileName: '',
        handleFileChange(event) {
            const [file] = event.target.files || [];
            this.selectedFileName = file ? file.name : '';
            this.imagePreview = null;

            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (readerEvent) => {
                    this.imagePreview = readerEvent.target?.result || null;
                };
                reader.readAsDataURL(file);
            }
        },
        closeDocumentModal() {
            this.uploadDocumentOpen = false;
            this.imagePreview = null;
            this.selectedFileName = '';
        }
    }"
>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Member Profile</h1>
            <p class="text-muted mb-0">View borrower details, balances, and loan history.</p>
        </div>
        <div class="d-flex gap-2">
            @can('create', \App\Models\Loan::class)
                <a href="{{ route('loans.create', [...$tenantParameter, 'member_id' => $member->id]) }}" class="btn btn-primary">
                    <i class="bi bi-cash-coin me-2"></i>New Loan for this Member
                </a>
            @endcan
            @can('update', $member)
                <a href="{{ route('members.edit', [...$tenantParameter, 'member' => $member]) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-pencil-square me-2"></i>Edit Member
                </a>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-4 align-items-start">
                <div class="col-lg-8">
                    <h2 class="h2 fw-bold mb-2">{{ $member->full_name }}</h2>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-primary-subtle text-primary">{{ $member->member_number }}</span>
                        <span class="badge bg-{{ $member->is_active ? 'success' : 'secondary' }}">
                            {{ $member->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase fw-semibold">Phone</div>
                            <div>{{ $member->phone ?: 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase fw-semibold">Email</div>
                            <div>{{ $member->email ?: 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase fw-semibold">Branch</div>
                            <div>{{ $member->branch?->name ?? 'Unassigned' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase fw-semibold">Joined Date</div>
                            <div>{{ $member->joined_at?->format('M d, Y') ?? 'N/A' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small text-uppercase fw-semibold">Address</div>
                            <div>{{ $member->address ?: 'No address recorded.' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="text-muted small text-uppercase fw-semibold mb-2">Borrower Snapshot</div>
                        <div class="mb-2"><strong>Birthdate:</strong> {{ $member->birthdate?->format('M d, Y') ?? 'N/A' }}</div>
                        <div class="mb-2"><strong>Gender:</strong> {{ $member->gender ? ucfirst($member->gender) : 'N/A' }}</div>
                        <div class="mb-2"><strong>Civil Status:</strong> {{ $member->civil_status ? ucfirst($member->civil_status) : 'N/A' }}</div>
                        <div class="mb-0"><strong>Occupation:</strong> {{ $member->occupation ?: 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted text-uppercase small fw-semibold mb-2">Active Loans</div>
                    <div class="display-6 fw-bold text-primary">{{ number_format($activeLoans->count()) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted text-uppercase small fw-semibold mb-2">Total Borrowed</div>
                    <div class="h3 fw-bold mb-0">P{{ number_format($totalBorrowed, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted text-uppercase small fw-semibold mb-2">Total Paid</div>
                    <div class="h3 fw-bold text-success mb-0">P{{ number_format($totalPaid, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-danger">
                <div class="card-body">
                    <div class="text-muted text-uppercase small fw-semibold mb-2">Outstanding Balance</div>
                    <div class="h3 fw-bold text-danger mb-0">P{{ number_format($totalOutstanding, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    @if($showDocumentsSection)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h2 class="h5 mb-0 fw-bold">Member Documents</h2>
                    <p class="text-muted small mb-0 mt-1">Attach IDs, certificates, supporting files, and agreements to this profile.</p>
                </div>
                @if($canUploadDocuments)
                    <button type="button" class="btn btn-primary" @click="uploadDocumentOpen = true">
                        <i class="bi bi-upload me-2"></i>Upload Document
                    </button>
                @endif
            </div>
            <div class="card-body p-4">
                @if($canViewDocuments && $member->documents->isNotEmpty())
                    <div class="row g-3">
                        @foreach($member->documents as $document)
                            <div class="col-md-6 col-xl-4">
                                <div class="border rounded-4 p-3 bg-light h-100">
                                    <div class="d-flex align-items-start justify-content-between gap-3">
                                        <div class="d-flex align-items-center gap-3 min-w-0">
                                            <div class="rounded-3 bg-white bg-opacity-10 p-3">
                                                <i class="bi {{ $document->file_icon }} fs-3"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="fw-semibold">{{ $document->document_type }}</div>
                                                <div class="text-muted small text-truncate">{{ $document->file_name }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 small text-muted">
                                        <div>Size: {{ $document->file_size_formatted }}</div>
                                        <div>Uploaded by: {{ $document->uploadedBy?->name ?? 'System' }}</div>
                                        <div>Date: {{ $document->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</div>
                                        @if($document->notes)
                                            <div class="mt-2">Notes: {{ $document->notes }}</div>
                                        @endif
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        <a href="{{ route('member.documents.download', [...$tenantParameter, 'document' => $document]) }}" class="btn btn-outline-primary btn-sm">
                                            Download
                                        </a>
                                        @if($canDeleteDocuments)
                                            <form action="{{ route('member.documents.destroy', [...$tenantParameter, 'document' => $document]) }}" method="POST"
                                                data-confirm="Delete this document?"
                                                data-confirm-title="Delete document?"
                                                data-confirm-confirm-text="Delete"
                                                data-confirm-tone="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif($canViewDocuments)
                    <div class="text-center text-muted py-5">
                        <div class="fw-semibold mb-1">No documents uploaded yet.</div>
                        <p class="mb-0">Upload member files to keep IDs, certifications, and agreements in one place.</p>
                    </div>
                @else
                    <div class="text-center text-muted py-5">
                        <div class="fw-semibold mb-1">Documents are restricted.</div>
                        <p class="mb-0">You do not have permission to view member documents.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0 fw-bold">Loan History</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Loan #</th>
                        <th>Type</th>
                        <th class="text-end">Principal</th>
                        <th class="text-end">Total Payable</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loanHistory as $loan)
                        <tr>
                            <td>{{ $loan->loan_number }}</td>
                            <td>{{ $loan->loanType?->name ?? 'N/A' }}</td>
                            <td class="text-end">P{{ number_format((float) $loan->principal_amount, 2) }}</td>
                            <td class="text-end">P{{ number_format((float) $loan->total_payable, 2) }}</td>
                            <td class="text-end">P{{ number_format((float) $loan->amount_paid, 2) }}</td>
                            <td class="text-end text-danger">P{{ number_format((float) $loan->outstanding_balance, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $loanStatusClasses[$loan->status] ?? 'secondary' }}">
                                    {{ str_replace('_', ' ', ucfirst($loan->status)) }}
                                </span>
                            </td>
                            <td>{{ $loan->release_date?->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="text-end">
                                <a href="{{ route('loans.show', [...$tenantParameter, 'loan' => $loan]) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">This member has no loan history yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canUploadDocuments)
        <div
            x-cloak
            x-show="uploadDocumentOpen"
            x-transition.opacity
            class="fixed inset-0 z-[70] flex items-center justify-center bg-black/70 px-3 py-4"
            style="display: none;"
        >
            <div
                x-show="uploadDocumentOpen"
                x-transition.scale
                class="w-full max-w-2xl rounded-4 border border-white/10 bg-slate-950 shadow-2xl"
                @click.away="closeDocumentModal()"
            >
                <div class="d-flex align-items-start justify-content-between gap-3 border-bottom border-white/10 px-4 py-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1 text-white">Upload Document</h2>
                        <p class="mb-0 text-sm text-slate-400">Accepted formats: PDF, JPG, PNG, DOC, DOCX. Max size: 10MB.</p>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="closeDocumentModal()">Close</button>
                </div>

                <form action="{{ route('member.documents.store', [...$tenantParameter, 'member' => $member]) }}" method="POST" enctype="multipart/form-data" class="p-4">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="document_type" class="form-label fw-semibold">Document Type*</label>
                            <input
                                type="text"
                                id="document_type"
                                name="document_type"
                                value="{{ old('document_type') }}"
                                list="member-document-types"
                                class="form-control @error('document_type') is-invalid @enderror"
                                placeholder="Choose or type a document type"
                                required
                            >
                            <datalist id="member-document-types">
                                @foreach($memberDocumentTypes as $documentType)
                                    <option value="{{ $documentType }}"></option>
                                @endforeach
                            </datalist>
                            @error('document_type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="file" class="form-label fw-semibold">File Upload*</label>
                            <input
                                type="file"
                                id="file"
                                name="file"
                                class="form-control @error('file') is-invalid @enderror"
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                @change="handleFileChange($event)"
                                required
                            >
                            @error('file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            <div class="form-text">PDF, JPG, PNG, DOC, DOCX • Max size: 10MB</div>
                        </div>

                        <div class="col-12" x-show="selectedFileName">
                            <div class="border rounded-4 bg-light p-3">
                                <div class="small text-muted mb-2">Selected File</div>
                                <div class="fw-semibold" x-text="selectedFileName"></div>
                                <template x-if="imagePreview">
                                    <img :src="imagePreview" alt="Preview" class="mt-3 rounded-3 border object-cover" style="max-height: 220px; width: auto;">
                                </template>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label fw-semibold">Notes</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="Optional notes about this document">{{ old('notes') }}</textarea>
                            @error('notes') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-outline-secondary" @click="closeDocumentModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
