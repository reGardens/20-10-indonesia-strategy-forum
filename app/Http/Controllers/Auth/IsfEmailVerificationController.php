<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MemberEmailVerificationMail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class IsfEmailVerificationController extends Controller
{
    /**
     * Handle email verification link click (signed URL).
     *
     * On success, marks user as email-verified, logs them in,
     * and redirects to the ISF Member Portal with a success banner.
     */
    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        // 1. Verify cryptographic URL signature and expiration
        if (! $request->hasValidSignature()) {
            Log::warning("Email verification failed: invalid signature for user ID {$id}");
            return redirect('/anggota?verified=invalid');
        }

        // 2. Locate user
        $user = User::find($id);
        if (! $user) {
            Log::warning("Email verification failed: user ID {$id} not found");
            return redirect('/anggota?verified=not_found');
        }

        // 3. Compare hash with user's email
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            Log::warning("Email verification failed: hash mismatch for user {$user->email}");
            return redirect('/anggota?verified=invalid');
        }

        $redirectUrl = $request->query('redirect');
        $safeRedirect = ($redirectUrl && str_starts_with($redirectUrl, '/')) ? $redirectUrl : null;

        // 4. Handle already verified accounts
        if ($user->hasVerifiedEmail()) {
            if (! Auth::check() || Auth::id() !== $user->id) {
                Auth::login($user);
            }

            return redirect($safeRedirect ?: '/anggota?verified=already');
        }

        // 5. Mark as verified and trigger standard Laravel event
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // 6. Automatically log in the user so the Member Portal session is active
        Auth::login($user);

        Log::info("Email verified successfully for user {$user->email} (ID: {$user->id})");

        return redirect($safeRedirect ?: '/anggota?verified=success');
    }

    /**
     * Resend email verification link.
     */
    public function resend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::where('email', $email)->first();

        if (! $user) {
            // Keep generic message to avoid email enumeration
            return response()->json([
                'success' => true,
                'message' => 'Jika email Anda terdaftar dan belum diverifikasi, tautan verifikasi baru telah dikirim.',
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Alamat email ini sudah terverifikasi sebelumnya. Silakan masuk ke akun Anda.',
                'already_verified' => true,
            ]);
        }

        try {
            $verificationUrl = URL::temporarySignedRoute(
                'isf.verification.verify',
                now()->addHours(24),
                [
                    'id' => $user->id,
                    'hash' => sha1($user->getEmailForVerification()),
                ]
            );

            Mail::to($user->email)->send(new MemberEmailVerificationMail($user, $verificationUrl));

            return response()->json([
                'success' => true,
                'message' => 'Tautan verifikasi baru telah dikirimkan ke email Anda. Silakan periksa kotak masuk atau spam.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim ulang email verifikasi: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email verifikasi: ' . $e->getMessage(),
            ], 500);
        }
    }
}

