<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\StaffProfile;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class StaffCertificatePdfController extends Controller
{
    public function download(Request $request, User $subject): Response
    {
        if (! $request->user()->can('viewUser', [StaffProfile::class, $subject])) {
            abort(403);
        }

        $subject->loadMissing(['role', 'school', 'staffProfile']);

        $filename = sprintf('certificate-of-service-%s.pdf', Str::slug($subject->fullName() ?: 'staff'));

        $pdf = Pdf::loadView('pdf.certificate', [
            'subject' => $subject,
            'issuer' => $request->user(),
            'title' => 'Certificate of Service — '.$subject->fullName(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }
}
