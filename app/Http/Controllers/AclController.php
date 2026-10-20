<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AclController extends Controller
{
    /**
     * Show all standard users with their feature permissions.
     */
    public function index(): Response
    {
        $users = User::role('user')
            ->latest()
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'allowed_features' => $u->allowed_features ?? [
                        "undangan_digital",
                        "rsvp_online",
                        "informasi_acara",
                        "love_story",
                        "manajemen_tamu",
                        "amplop_digital",
                        "galeri",
                        "countdown",
                        "qr_checkin",
                        "live_streaming",
                        "ucapan_doa",
                        "wishlist"
                    ],
                ];
            });

        return Inertia::render('Cms/Acl/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Toggle a specific feature for a standard user.
     */
    public function toggle(Request $request, User $user)
    {
        $request->validate([
            'feature' => ['required', 'string'],
        ]);

        $feature = $request->input('feature');
        $features = $user->allowed_features ?? [
            "undangan_digital",
            "rsvp_online",
            "informasi_acara",
            "love_story",
            "manajemen_tamu",
            "amplop_digital",
            "galeri",
            "countdown",
            "qr_checkin",
            "live_streaming",
            "ucapan_doa",
            "wishlist"
        ];

        if (in_array($feature, $features)) {
            // Remove the feature
            $features = array_values(array_diff($features, [$feature]));
            $status = 'dinonaktifkan';
        } else {
            // Add the feature
            $features[] = $feature;
            $status = 'diaktifkan';
        }

        $user->update(['allowed_features' => $features]);

        return redirect()->route('cms.acl.index')
            ->with('success', "Fitur \"{$feature}\" berhasil {$status} untuk pengguna {$user->name}.");
    }
}
