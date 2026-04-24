<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\StaffCertificate;
use App\Models\User;
use App\Services\HR\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaffCertificateController extends Controller
{
    public function store(Request $request, User $subject, StaffService $service): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor->can('createFor', [StaffCertificate::class, $subject])) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'issuer' => ['nullable', 'string', 'max:160'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'issued_on' => ['nullable', 'date'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:issued_on'],
            'document_url' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->addCertificate($actor, $subject, $data);

        return redirect()->route('hr.show', $subject)->with('status', 'Certificate added.');
    }

    public function archive(Request $request, StaffCertificate $certificate, StaffService $service): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor->can('archive', $certificate)) {
            abort(403);
        }

        $service->archiveCertificate($actor, $certificate);

        return redirect()->route('hr.show', $certificate->user)->with('status', 'Certificate archived.');
    }
}
