@extends('layouts.app')

@section('title', $subject->fullName() . ' — HR')

@section('content')
    @if (session('status'))
        <div class="alert success" style="margin-bottom:12px;">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert error" style="margin-bottom:12px;">
            @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
        </div>
    @endif

    <div class="card">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <h1 style="margin:0;">{{ $subject->fullName() }}</h1>
                <p class="muted" style="margin:6px 0 0 0;">
                    {{ $subject->role?->name ?? 'Unassigned' }}
                    · @username {{ $subject->username }}
                    @if ($subject->school) · {{ $subject->school->name }} @endif
                </p>
            </div>
            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px;">
                <span class="badge"
                      style="background:
                        @switch($subject->status)
                            @case('active') rgba(22,163,74,0.12) @break
                            @case('suspended') rgba(217,119,6,0.12) @break
                            @case('deactivated') rgba(15,23,42,0.08) @break
                        @endswitch
                        ; color:
                        @switch($subject->status)
                            @case('active') #15803d @break
                            @case('suspended') #b45309 @break
                            @case('deactivated') #475569 @break
                        @endswitch
                        ;">
                    {{ $subject->status }}
                </span>
                @if (app(\App\Policies\StaffProfilePolicy::class)->isProtectedFromHr($subject))
                    <span class="muted" style="font-size:12px;">HR edits blocked for this role</span>
                @endif

                @if ($actor->can('suspend', [\App\Models\StaffProfile::class, $subject]))
                    <div style="display:flex; gap:6px; margin-top:4px; flex-wrap:wrap; justify-content:flex-end;">
                        @if ($subject->status === 'active')
                            <form method="POST" action="{{ route('hr.suspend', $subject) }}"
                                  onsubmit="return confirm('Suspend {{ $subject->fullName() }}?');">
                                @csrf
                                <button class="btn ghost" type="submit">Suspend</button>
                            </form>
                            <form method="POST" action="{{ route('hr.deactivate', $subject) }}"
                                  onsubmit="return confirm('Deactivate {{ $subject->fullName() }}? They will lose access.');">
                                @csrf
                                <button class="btn danger" type="submit">Deactivate</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('hr.activate', $subject) }}">
                                @csrf
                                <button class="btn" type="submit">Reactivate</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0;">HR profile</h3>
            @if ($profile?->lastEditor)
                <span class="muted" style="font-size:12px;">
                    Last edited by {{ $profile->lastEditor->fullName() }}
                    · {{ $profile->updated_at?->diffForHumans() }}
                </span>
            @endif
        </div>

        <form class="form" method="POST" action="{{ route('hr.profile.update', $subject) }}" style="margin-top:12px;">
            @csrf
            <div class="grid cols-3">
                <div class="field">
                    <label>Employee #</label>
                    <input name="employee_no" type="text" maxlength="60" value="{{ old('employee_no', $profile?->employee_no) }}" @disabled(!$canEdit)>
                </div>
                <div class="field">
                    <label>Employment type</label>
                    <select name="employment_type" @disabled(!$canEdit)>
                        <option value="">—</option>
                        @foreach ($employmentTypes as $type)
                            <option value="{{ $type }}" @selected(old('employment_type', $profile?->employment_type) === $type)>
                                {{ ucfirst(str_replace('_',' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Hired on</label>
                    <input name="hired_on" type="date" value="{{ old('hired_on', $profile?->hired_on?->toDateString()) }}" @disabled(!$canEdit)>
                </div>
            </div>

            <div class="grid cols-3">
                <div class="field">
                    <label>NHIF</label>
                    <input name="nhif_number" type="text" maxlength="60" value="{{ old('nhif_number', $profile?->nhif_number) }}" @disabled(!$canEdit)>
                </div>
                <div class="field">
                    <label>NSSF</label>
                    <input name="nssf_number" type="text" maxlength="60" value="{{ old('nssf_number', $profile?->nssf_number) }}" @disabled(!$canEdit)>
                </div>
                <div class="field">
                    <label>TIN</label>
                    <input name="tin_number" type="text" maxlength="60" value="{{ old('tin_number', $profile?->tin_number) }}" @disabled(!$canEdit)>
                </div>
            </div>

            <div class="grid cols-3">
                <div class="field">
                    <label>Bank</label>
                    <input name="bank_name" type="text" maxlength="120" value="{{ old('bank_name', $profile?->bank_name) }}" @disabled(!$canEdit)>
                </div>
                <div class="field">
                    <label>Bank account</label>
                    <input name="bank_account" type="text" maxlength="60" value="{{ old('bank_account', $profile?->bank_account) }}" @disabled(!$canEdit)>
                </div>
                <div class="field">
                    <label>Branch</label>
                    <input name="bank_branch" type="text" maxlength="120" value="{{ old('bank_branch', $profile?->bank_branch) }}" @disabled(!$canEdit)>
                </div>
            </div>

            <div class="grid cols-2">
                <div class="field">
                    <label>Phone</label>
                    <input name="phone" type="text" maxlength="40" value="{{ old('phone', $profile?->phone) }}" @disabled(!$canEdit)>
                </div>
                <div class="field">
                    <label>Email</label>
                    <input name="email" type="email" maxlength="160" value="{{ old('email', $profile?->email) }}" @disabled(!$canEdit)>
                </div>
            </div>

            <div class="grid cols-3">
                <div class="field">
                    <label>Next of kin</label>
                    <input name="next_of_kin_name" type="text" maxlength="120" value="{{ old('next_of_kin_name', $profile?->next_of_kin_name) }}" @disabled(!$canEdit)>
                </div>
                <div class="field">
                    <label>Relation</label>
                    <input name="next_of_kin_relation" type="text" maxlength="60" value="{{ old('next_of_kin_relation', $profile?->next_of_kin_relation) }}" @disabled(!$canEdit)>
                </div>
                <div class="field">
                    <label>Next of kin phone</label>
                    <input name="next_of_kin_phone" type="text" maxlength="40" value="{{ old('next_of_kin_phone', $profile?->next_of_kin_phone) }}" @disabled(!$canEdit)>
                </div>
            </div>

            <div class="field">
                <label>Notes</label>
                <textarea name="notes" rows="3" maxlength="2000" @disabled(!$canEdit)>{{ old('notes', $profile?->notes) }}</textarea>
            </div>

            @if ($canEdit)
                <div style="display:flex; gap:10px;">
                    <button class="btn" type="submit">Save profile</button>
                </div>
            @endif
        </form>
    </div>

    {{-- Certificates --}}
    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Certificates</h3>

        @if ($certificates->isEmpty())
            <p class="muted" style="margin:0;">No certificates on record.</p>
        @else
            <div class="grid" style="gap:8px;">
                @foreach ($certificates as $cert)
                    <div style="display:grid; grid-template-columns:1fr auto; gap:8px; padding:10px 12px; border:1px solid rgba(15,23,42,0.08); border-radius:12px; background:rgba(255,255,255,0.75);">
                        <div>
                            <div style="font-weight:600;">{{ $cert->title }}</div>
                            <div class="muted" style="font-size:12px; margin-top:2px;">
                                @if ($cert->issuer) {{ $cert->issuer }} @endif
                                @if ($cert->issued_on) · issued {{ $cert->issued_on->format('Y-m-d') }} @endif
                                @if ($cert->expires_on) · expires {{ $cert->expires_on->format('Y-m-d') }}
                                    @if ($cert->isExpired()) <strong style="color:#dc2626;">(expired)</strong> @endif
                                @endif
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span class="badge" style="background:{{ $cert->is_active ? 'rgba(22,163,74,0.12)' : 'rgba(15,23,42,0.08)' }}; color:{{ $cert->is_active ? '#15803d' : '#475569' }};">
                                {{ $cert->is_active ? 'active' : 'archived' }}
                            </span>
                            @can('archive', $cert)
                                @if ($cert->is_active)
                                    <form method="POST" action="{{ route('hr.certificates.archive', $cert) }}">
                                        @csrf
                                        <button class="btn ghost" type="submit">Archive</button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($canAddCert)
            <details style="margin-top:12px;">
                <summary style="cursor:pointer; font-weight:600;">Add a certificate</summary>
                <form class="form" method="POST" action="{{ route('hr.certificates.store', $subject) }}" style="margin-top:10px;">
                    @csrf
                    <div class="grid cols-2">
                        <div class="field">
                            <label>Title</label>
                            <input name="title" type="text" required maxlength="160" value="{{ old('title') }}">
                        </div>
                        <div class="field">
                            <label>Issuer</label>
                            <input name="issuer" type="text" maxlength="160" value="{{ old('issuer') }}">
                        </div>
                    </div>
                    <div class="grid cols-3">
                        <div class="field">
                            <label>Reference</label>
                            <input name="reference_no" type="text" maxlength="120" value="{{ old('reference_no') }}">
                        </div>
                        <div class="field">
                            <label>Issued on</label>
                            <input name="issued_on" type="date" value="{{ old('issued_on') }}">
                        </div>
                        <div class="field">
                            <label>Expires on</label>
                            <input name="expires_on" type="date" value="{{ old('expires_on') }}">
                        </div>
                    </div>
                    <div class="field">
                        <label>Document URL</label>
                        <input name="document_url" type="url" maxlength="500" value="{{ old('document_url') }}" placeholder="https://...">
                    </div>
                    <div class="field">
                        <label>Notes</label>
                        <textarea name="notes" rows="2" maxlength="1000">{{ old('notes') }}</textarea>
                    </div>
                    <div><button class="btn" type="submit">Add certificate</button></div>
                </form>
            </details>
        @endif
    </div>

    {{-- Leaves --}}
    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Leave</h3>

        @if ($leaves->isEmpty())
            <p class="muted" style="margin:0;">No leave records.</p>
        @else
            <div class="grid" style="gap:8px;">
                @foreach ($leaves as $leave)
                    <div style="padding:10px 12px; border:1px solid rgba(15,23,42,0.08); border-radius:12px; background:rgba(255,255,255,0.75);">
                        <div style="display:grid; grid-template-columns:1fr auto; gap:8px; align-items:center;">
                            <div>
                                <div style="font-weight:600;">
                                    {{ ucfirst($leave->type) }}
                                    · {{ $leave->starts_on->format('Y-m-d') }} → {{ $leave->ends_on->format('Y-m-d') }}
                                    ({{ $leave->durationDays() }} day{{ $leave->durationDays() === 1 ? '' : 's' }})
                                </div>
                                <div class="muted" style="font-size:12px; margin-top:2px;">
                                    Requested by {{ $leave->requester?->fullName() ?? '—' }}
                                    · {{ $leave->created_at?->diffForHumans() }}
                                    @if ($leave->decider)
                                        · decided by {{ $leave->decider->fullName() }}
                                        @if ($leave->decided_at) {{ $leave->decided_at->diffForHumans() }} @endif
                                    @endif
                                </div>
                                @if ($leave->reason)
                                    <div style="font-size:13px; margin-top:6px; white-space:pre-wrap;">{{ $leave->reason }}</div>
                                @endif
                                @if ($leave->decision_comment)
                                    <div class="muted" style="font-size:13px; margin-top:4px;">Note: {{ $leave->decision_comment }}</div>
                                @endif
                            </div>
                            <span class="badge" style="background:
                                @switch($leave->status)
                                    @case('approved') rgba(22,163,74,0.12) @break
                                    @case('rejected') rgba(220,38,38,0.12) @break
                                    @case('cancelled') rgba(15,23,42,0.08) @break
                                    @default rgba(217,119,6,0.12)
                                @endswitch
                                ; color:
                                @switch($leave->status)
                                    @case('approved') #15803d @break
                                    @case('rejected') #b91c1c @break
                                    @case('cancelled') #475569 @break
                                    @default #b45309
                                @endswitch
                                ;">
                                {{ $leave->status }}
                            </span>
                        </div>

                        @canany(['decide', 'cancel'], $leave)
                            <div style="display:flex; gap:6px; margin-top:10px; flex-wrap:wrap;">
                                @can('decide', $leave)
                                    <form method="POST" action="{{ route('hr.leaves.approve', $leave) }}">
                                        @csrf
                                        <button class="btn" type="submit">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('hr.leaves.reject', $leave) }}">
                                        @csrf
                                        <button class="btn ghost" type="submit">Reject</button>
                                    </form>
                                @endcan
                                @can('cancel', $leave)
                                    <form method="POST" action="{{ route('hr.leaves.cancel', $leave) }}">
                                        @csrf
                                        <button class="btn ghost" type="submit">Cancel</button>
                                    </form>
                                @endcan
                            </div>
                        @endcanany
                    </div>
                @endforeach
            </div>
        @endif

        @if ($canRequestLeaveFor)
            <details style="margin-top:12px;">
                <summary style="cursor:pointer; font-weight:600;">Request leave</summary>
                <form class="form" method="POST" action="{{ route('hr.leaves.store', $subject) }}" style="margin-top:10px;">
                    @csrf
                    <div class="grid cols-3">
                        <div class="field">
                            <label>Type</label>
                            <select name="type" required>
                                @foreach ($leaveTypes as $type)
                                    <option value="{{ $type }}" @selected(old('type') === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Starts on</label>
                            <input name="starts_on" type="date" required value="{{ old('starts_on') }}">
                        </div>
                        <div class="field">
                            <label>Ends on</label>
                            <input name="ends_on" type="date" required value="{{ old('ends_on') }}">
                        </div>
                    </div>
                    <div class="field">
                        <label>Reason</label>
                        <textarea name="reason" rows="2" maxlength="1000">{{ old('reason') }}</textarea>
                    </div>
                    <div><button class="btn" type="submit">Submit request</button></div>
                </form>
            </details>
        @endif
    </div>

    <div style="margin-top:16px; display:flex; gap:8px;">
        <a class="btn ghost" href="{{ route('hr.index') }}">Back to directory</a>
    </div>
@endsection
