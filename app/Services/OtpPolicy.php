<?php

namespace App\Services;

use App\Enums\ShareMode;
use App\Models\User;
use InvalidArgumentException;

class OtpPolicy
{
    public function __construct(
        private readonly SharingSettings $sharingSettings,
    ) {}

    public function defaultRequireOtp(): bool
    {
        return $this->sharingSettings->invitationRequireOtp();
    }

    public function canSkipOtp(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->groups()->where('allow_invitation_without_otp', true)->exists();
    }

    public function canChooseOtpSetting(?User $user): bool
    {
        return $this->canSkipOtp($user) || ! $this->defaultRequireOtp();
    }

    public function effectiveRequireOtp(?User $user, ?bool $requested = null, ?ShareMode $shareMode = null): bool
    {
        if ($shareMode === ShareMode::StaticLink) {
            return true;
        }

        $requested ??= $this->defaultRequireOtp();

        if ($requested === false && ! $this->canSkipOtp($user) && $this->defaultRequireOtp()) {
            return true;
        }

        return $requested;
    }

    public function resolveRequireOtp(?User $user, ?bool $requested): bool
    {
        if ($requested === false && ! $this->canSkipOtp($user) && $this->defaultRequireOtp()) {
            throw new InvalidArgumentException(__('sharing.otp-skip-not-allowed'));
        }

        return $requested ?? $this->defaultRequireOtp();
    }
}
