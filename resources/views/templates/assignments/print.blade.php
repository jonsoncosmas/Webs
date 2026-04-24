@extends('layouts.app')

@section('title', 'Print — ' . ($assignment->template?->name ?? 'Assignment'))

@section('content')
    <div class="print-toolbar no-print">
        <button class="btn" type="button" onclick="window.print()">Print</button>
        <a class="btn ghost" href="{{ route('assignments.show', $assignment) }}">Back</a>
    </div>

    @includeIf($layoutView, ['assignment' => $assignment])
@endsection
