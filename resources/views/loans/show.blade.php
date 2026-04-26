@extends('layouts.tenant')

@section('title', 'Loan Details')

@section('content')
@php
    $tenantParameter = ['tenant' => request()->route('tenant')];
    $loanStatusClasses = [
        'active' => 'primary',
        'fully_paid' => 'success',
        'overdue' => 'danger',
        'restructured' => 'warning text-dark',
    ];
    $scheduleStatusClasses = [
        'pending' => 'secondary',
        'paid' => 'success',
        'overdue' => 'danger',
    ];
    $loanDocumentTypes = [
        'Loan Application Form',
        'Promissory Note',
        'Collateral Photo',
        'Signed Agreement',
        'Co-Maker Form',
        'Government ID',
        'Other',
    ];
    $user = auth()->user();
    $canViewDocuments = $user?->hasTenantPermission(\App\Support\TenantPermissions::LOAN_DOCUMENTS_VIEW, ['tenant_admin', 'branch_manager', 'loan_officer', 'viewer']) ?? false;
    $canUploadDocuments = $user?->hasTenantPermission(\App\Support\TenantPermissions::LOAN_DOCUMENTS_UPLOAD, ['tenant_admin', 'branch_manager', 'loan_officer']) ?? false;
    $canDeleteDocuments = $user?->hasTenantPermission(\App\Support\TenantPermissions::LOAN_DOCUMENTS_DELETE, ['tenant_admin']) ?? false;
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
            <h1 class="h3 fw-bold mb-1">Loan Details</h1>
            <p class="text-muted mb-0">Review the amortization schedule, balances, and payment history.</p>
        </div>
        <div class="d-flex gap-2">
            @can('create', \App\Models\LoanPayment::class)
                <a href="{{ route('loan-payments.create', [...$tenantParameter, 'loan' => $loan->id]) }}" class="btn btn-primary">
                    <i class="bi bi-wallet2 me-2"></i>Record Payment
                </a>
            @endcan
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-2"></i>Print
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
                <div>
                    <div class="small text-muted text-uppercase fw-semibold mb-2">Loan Number</div>
                    <h2 class="display-6 fw-bold mb-2">{{ $loan->loan_number }}</h2>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-{{ $loanStatusClasses[$loan->status] ?? 'secondary' }}">
                            {{ str_replace('_', ' ', ucfirst($loan->status)) }}
                        </span>
                        <span class="badge bg-light text-dark">{{ $loan->member?->full_name ?? 'Unknown Member' }}</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase fw-semibold">Loan Type</div>
                            <div>{{ $loan->loanType?->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase fw-semibold">Branch</div>
                            <div>{{ $loan->branch?->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase fw-semibold">Released By</div>
                            <div>{{ $loan->user?->name ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
                <div class="border rounded-3 bg-light p-3" style="min-width: 280px;">
                    <div class="text-muted small text-uppercase fw-semibold mb-2">Loan Summary</div>
                    <div class="d-flex justify-content-between mb-2"><span>Principal</span><strong>P{{ number_format((float) $loan->principal_amount, 2) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Total Payable</span><strong>P{{ number_format((float) $loan->total_payable, 2) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Total Paid</span><strong class="text-success">P{{ number_format((float) $loan->amount_paid, 2) }}</strong></div>
                    <div class="d-flex justify-content-between"><span>Outstanding</span><strong class="text-danger">P{{ number_format((float) $loan->outstanding_balance, 2) }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted text-uppercase small fw-semibold mb-2">Principal</div>
                    <div class="h3 fw-bold mb-0">P{{ number_format((float) $loan->principal_amount, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted text-uppercase small fw-semibold mb-2">Total Payable</div>
                    <div class="h3 fw-bold mb-0">P{{ number_format((float) $loan->total_payable, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted text-uppercase small fw-semibold mb-2">Total Paid</div>
                    <div class="h3 fw-bold text-success mb-0">P{{ number_format((float) $loan->amount_paid, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-danger">
                <div class="card-body">
                    <div class="text-muted text-uppercase small fw-semibold mb-2">Outstanding Balance</div>
                    <div class="h3 fw-bold text-danger mb-0">P{{ number_format((float) $loan->outstanding_balance, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0 fw-bold">Loan Details</h2>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase fw-semibold">Interest Rate</div>
                    <div>{{ number_format((float) $loan->interest_rate, 2) }}%</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase fw-semibold">Interest Type</div>
                    <div>{{ ucfirst($loan->interest_type) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase fw-semibold">Term</div>
                    <div>{{ $loan->term_months }} months</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase fw-semibold">Release Date</div>
                    <div>{{ $loan->release_date?->format('M d, Y') ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase fw-semibold">Due Date</div>
                    <div>{{ $loan->due_date?->format('M d, Y') ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase fw-semibold">Monthly Payment</div>
                    <div>P{{ number_format((float) $loan->monthly_payment, 2) }}</div>
                </div>
                <div class="col-12">
                    <div class="text-muted small text-uppercase fw-semibold">Notes</div>
                    <div>{{ $loan->notes ?: 'No notes recorded.' }}</div>
                </div>
            </div>
        </div>
    </div>

    @if($showDocumentsSection)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h2 class="h5 mb-0 fw-bold">Loan Documents</h2>
                    <p class="text-muted small mb-0 mt-1">Store application forms, agreements, collateral photos, and related files.</p>
                </div>
                @if($canUploadDocuments)
                    <button type="button" class="btn btn-primary" @click="uploadDocumentOpen = true">
                        <i class="bi bi-upload me-2"></i>Upload Document
                    </button>
                @endif
            </div>
            <div class="card-body p-4">
                @if($canViewDocuments && $loan->documents->isNotEmpty())
                    <div class="row g-3">
                        @foreach($loan->documents as $document)
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
                                        <a href="{{ route('loan.documents.download', [...$tenantParameter, 'document' => $document]) }}" class="btn btn-outline-primary btn-sm">
                                            Download
                                        </a>
                                        @if($canDeleteDocuments)
                                            <form action="{{ route('loan.documents.destroy', [...$tenantParameter, 'document' => $document]) }}" method="POST"
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
                        <p class="mb-0">Upload loan files to keep application packets and signed agreements in one place.</p>
                    </div>
                @else
                    <div class="text-center text-muted py-5">
                        <div class="fw-semibold mb-1">Documents are restricted.</div>
                        <p class="mb-0">You do not have permission to view loan documents.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0 fw-bold">Amortization Schedule</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Period #</th>
                        <th>Due Date</th>
                        <th class="text-end">Amount Due</th>
                        <th class="text-end">Principal</th>
                        <th class="text-end">Interest</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loan->loanSchedules->sortBy('period_number') as $schedule)
                        <tr>
                            <td>{{ $schedule->period_number }}</td>
                            <td>{{ $schedule->due_date?->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="text-end">P{{ number_format((float) $schedule->amount_due, 2) }}</td>
                            <td class="text-end">P{{ number_format((float) $schedule->principal_portion, 2) }}</td>
                            <td class="text-end">P{{ number_format((float) $schedule->interest_portion, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $scheduleStatusClasses[$schedule->status] ?? 'secondary' }}">
                                    {{ ucfirst($schedule->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">No amortization schedule has been generated.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-5" id="record-payment">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0 fw-bold">Record Payment</h2>
                </div>
                <div class="card-body">
                    @can('create', \App\Models\LoanPayment::class)
                        <form action="{{ route('loan-payments.store', $tenantParameter) }}" method="POST" class="row g-3">
                            @csrf
                            <input type="hidden" name="loan_id" value="{{ $loan->id }}">

                            <div class="col-12">
                                <label for="amount" class="form-label fw-semibold">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">P</span>
                                    <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="form-control @error('amount') is-invalid @enderror" required>
                                </div>
                                @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label for="payment_date" class="form-label fw-semibold">Payment Date</label>
                                <input type="date" id="payment_date" name="payment_date" value="{{ now()->toDateString() }}" class="form-control @error('payment_date') is-invalid @enderror" required>
                                @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label for="period_covered" class="form-label fw-semibold">Period Covered</label>
                                <input type="text" id="period_covered" name="period_covered" class="form-control @error('period_covered') is-invalid @enderror" placeholder="Example: March 2026">
                                @error('period_covered') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label for="payment_notes" class="form-label fw-semibold">Notes</label>
                                <textarea id="payment_notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="Optional payment notes"></textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Save Payment
                                </button>
                            </div>
                        </form>
                    @else
                        <p class="text-muted mb-0">You do not have permission to record payments for this loan.</p>
                    @endcan
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0 fw-bold">Payment History</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                                <th>Period Covered</th>
                                <th>Recorded By</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($loan->loanPayments as $payment)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $payment->payment_date?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td class="text-end">P{{ number_format((float) $payment->amount, 2) }}</td>
                                    <td>{{ $payment->period_covered ?: 'N/A' }}</td>
                                    <td>{{ $payment->user?->name ?? 'N/A' }}</td>
                                    <td>{{ $payment->notes ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">No payment history recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
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

                <form action="{{ route('loan.documents.store', [...$tenantParameter, 'loan' => $loan]) }}" method="POST" enctype="multipart/form-data" class="p-4">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="document_type" class="form-label fw-semibold">Document Type*</label>
                            <input
                                type="text"
                                id="document_type"
                                name="document_type"
                                value="{{ old('document_type') }}"
                                list="loan-document-types"
                                class="form-control @error('document_type') is-invalid @enderror"
                                placeholder="Choose or type a document type"
                                required
                            >
                            <datalist id="loan-document-types">
                                @foreach($loanDocumentTypes as $documentType)
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
