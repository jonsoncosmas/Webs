<?php

namespace App\Services\HR;

use App\Models\StaffCertificate;
use App\Models\StaffLeave;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\ActivityLogger;

class StaffService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function upsertProfile(User $actor, User $subject, array $attributes): StaffProfile
    {
        $profile = StaffProfile::firstOrNew(['user_id' => $subject->id]);
        $profile->fill($attributes);
        $profile->user_id = $subject->id;
        $profile->school_id = $subject->school_id;
        $profile->updated_by = $actor->id;
        $profile->save();

        $this->logger->log('staff.profile.updated', $profile, [
            'subject_id' => $subject->id,
        ]);

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addCertificate(User $actor, User $subject, array $attributes): StaffCertificate
    {
        $certificate = StaffCertificate::create(array_merge($attributes, [
            'user_id' => $subject->id,
            'school_id' => $subject->school_id,
            'added_by' => $actor->id,
            'is_active' => true,
        ]));

        $this->logger->log('staff.certificate.added', $certificate, [
            'subject_id' => $subject->id,
            'title' => $certificate->title,
        ]);

        return $certificate;
    }

    public function archiveCertificate(User $actor, StaffCertificate $certificate): StaffCertificate
    {
        $certificate->update(['is_active' => false]);

        $this->logger->log('staff.certificate.archived', $certificate);

        return $certificate;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function requestLeave(User $actor, User $subject, array $attributes): StaffLeave
    {
        $leave = StaffLeave::create([
            'user_id' => $subject->id,
            'school_id' => $subject->school_id,
            'type' => $attributes['type'],
            'starts_on' => $attributes['starts_on'],
            'ends_on' => $attributes['ends_on'],
            'reason' => $attributes['reason'] ?? null,
            'status' => StaffLeave::STATUS_PENDING,
            'requested_by' => $actor->id,
        ]);

        $this->logger->log('staff.leave.requested', $leave, [
            'subject_id' => $subject->id,
            'type' => $leave->type,
        ]);

        return $leave;
    }

    public function approveLeave(User $actor, StaffLeave $leave, ?string $comment = null): StaffLeave
    {
        $leave->update([
            'status' => StaffLeave::STATUS_APPROVED,
            'decided_by' => $actor->id,
            'decided_at' => now(),
            'decision_comment' => $comment,
        ]);

        $this->logger->log('staff.leave.approved', $leave, [
            'subject_id' => $leave->user_id,
        ]);

        return $leave;
    }

    public function rejectLeave(User $actor, StaffLeave $leave, ?string $comment = null): StaffLeave
    {
        $leave->update([
            'status' => StaffLeave::STATUS_REJECTED,
            'decided_by' => $actor->id,
            'decided_at' => now(),
            'decision_comment' => $comment,
        ]);

        $this->logger->log('staff.leave.rejected', $leave, [
            'subject_id' => $leave->user_id,
        ]);

        return $leave;
    }

    public function setStatus(User $actor, User $subject, string $status): User
    {
        $allowed = [User::STATUS_ACTIVE, User::STATUS_SUSPENDED, User::STATUS_DEACTIVATED];
        if (! in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $previous = $subject->status;
        $subject->update(['status' => $status]);

        $this->logger->log('staff.status.changed', $subject, [
            'from' => $previous,
            'to' => $status,
        ]);

        return $subject;
    }

    public function cancelLeave(User $actor, StaffLeave $leave): StaffLeave
    {
        $leave->update([
            'status' => StaffLeave::STATUS_CANCELLED,
            'decided_by' => $actor->id,
            'decided_at' => now(),
        ]);

        $this->logger->log('staff.leave.cancelled', $leave);

        return $leave;
    }
}
