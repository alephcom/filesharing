@if ($canUseStaticLink || $invitationMode)
<div class="mt-5" @if ($canUseStaticLink) x-show="isInvitationMode()" x-cloak @endif>
    <div class="space-y-1">
        <label for="upload-recipients" class="fi-label">
            @lang('invitation.recipients')
            <span class="text-red-500" x-show="bundle.require_otp">*</span>
        </label>
        <p class="text-xs text-gray-500" x-show="bundle.require_otp">@lang('invitation.recipients-help')</p>
        <p class="text-xs text-gray-500" x-show="! bundle.require_otp" x-cloak>@lang('invitation.recipients-help-optional')</p>
        <textarea
            id="upload-recipients"
            name="recipients"
            rows="4"
            class="fi-input"
            placeholder="colleague@company.com&#10;partner@example.org"
            x-model="bundle.recipients_text"
        ></textarea>
    </div>
</div>
@endif
