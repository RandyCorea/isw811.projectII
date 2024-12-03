<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use PragmaRX\Google2FALaravel\Google2FA;

class ProfileController extends Controller
{
    // Mostrar la información del perfil
    public function edit(Request $request)
    {
        $google2fa = app(Google2FA::class);
        $user = $request->user();
        $secret = $user->google2fa_secret ?? $google2fa->generateSecretKey();

        // Generar QR para 2FA
        $qrCodeUrl = $google2fa->getQRCodeInline(
            'SocialHub', // Nombre de la aplicación
            $user->email,
            $secret
        );
        

        return view('profile.edit', [
            'user' => $user,
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
        ]);
    }

    // Activar 2FA
    public function enable2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
            'secret' => 'required|string',
        ]);

        $google2fa = app(Google2FA::class);
        $user = $request->user();

        if ($google2fa->verifyKey($request->input('secret'), $request->input('code'))) {
            $user->update(['google2fa_secret' => $request->input('secret')]);

            return redirect()->route('profile.edit')->with('status', 'Two-factor authentication enabled successfully.');
        }

        return redirect()->route('profile.edit')->withErrors(['code' => 'Invalid 2FA code.']);
    }

    // Desactivar 2FA
    public function disable2FA()
    {
        $user = Auth::user();
        $user->update(['google2fa_secret' => null]);

        return redirect()->route('profile.edit')->with('status', 'Two-factor authentication disabled successfully.');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
