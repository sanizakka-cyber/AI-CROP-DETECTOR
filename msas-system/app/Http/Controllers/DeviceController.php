<?php

namespace App\Http\Controllers;

use App\Models\TrustedDevice;
use App\Services\TrustedDeviceService;
use Illuminate\Http\RedirectResponse;

class DeviceController extends Controller
{
    public function destroy(TrustedDevice $device): RedirectResponse
    {
        abort_unless($device->user_id === auth()->id(), 403);
        $device->delete();
        return back()->with('success', 'Trusted device removed.');
    }

    public function destroyAll(): RedirectResponse
    {
        $svc         = app(TrustedDeviceService::class);
        $forgetCookie = $svc->revokeAll(auth()->user());

        return redirect()->route('profile.security')
            ->with('success', 'All trusted devices removed. You will be asked to verify on your next login from each device.')
            ->withCookie($forgetCookie);
    }
}
