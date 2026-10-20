<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wedding;
use App\Models\Rsvp;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RsvpApiController extends Controller
{
    public function index($slug)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $rsvps = Rsvp::where('wedding_id', $wedding->id)->orderBy('created_at', 'desc')->get();

        return response()->json($rsvps->map(fn($r) => $this->formatRsvp($r))->toArray());
    }

    public function store(Request $request, $slug)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $request->validate([
            'name' => 'required|string',
            'status' => 'required|string',
        ]);

        $id = $request->input('id', (string) Str::uuid());

        $rsvp = DB::transaction(function() use ($request, $wedding, $id) {
            $r = Rsvp::create([
                'id' => $id,
                'wedding_id' => $wedding->id,
                'guest_id' => $request->guestId,
                'name' => $request->name,
                'status' => $request->status,
                'guests_count' => (int) ($request->guestsCount ?? 1),
                'wishes' => $request->wishes,
            ]);

            // Update guest status if guestId is provided
            if ($request->guestId) {
                Guest::where('wedding_id', $wedding->id)
                     ->where('id', $request->guestId)
                     ->update(['rsvp_status' => $request->status]);
            } else {
                // Try matching by exact name to keep it consistent
                Guest::where('wedding_id', $wedding->id)
                     ->where('name', $request->name)
                     ->update(['rsvp_status' => $request->status]);
            }

            return $r;
        });

        return response()->json($this->formatRsvp($rsvp), 201);
    }

    public function updateStatus(Request $request, $slug, $id)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $rsvp = Rsvp::where('wedding_id', $wedding->id)->where('id', $id)->firstOrFail();

        $request->validate([
            'status' => 'required|string',
        ]);

        $rsvp->update([
            'status' => $request->status,
        ]);

        // Also update guest status
        if ($rsvp->guest_id) {
            Guest::where('wedding_id', $wedding->id)
                 ->where('id', $rsvp->guest_id)
                 ->update(['rsvp_status' => $request->status]);
        }

        return response()->json($this->formatRsvp($rsvp));
    }

    public function summary($slug)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        
        $hadir = Rsvp::where('wedding_id', $wedding->id)->where('status', 'hadir')->count();
        $tidakHadir = Rsvp::where('wedding_id', $wedding->id)->where('status', 'tidak_hadir')->count();
        $mungkin = Rsvp::where('wedding_id', $wedding->id)->where('status', 'mungkin')->count();

        return response()->json([
            'hadir' => $hadir,
            'tidakHadir' => $tidakHadir,
            'mungkin' => $mungkin,
        ]);
    }

    private function formatRsvp(Rsvp $rsvp)
    {
        return [
            'id' => $rsvp->id,
            'guestId' => $rsvp->guest_id,
            'name' => $rsvp->name,
            'status' => $rsvp->status,
            'guestsCount' => $rsvp->guests_count,
            'wishes' => $rsvp->wishes,
            'createdAt' => $rsvp->created_at ? $rsvp->created_at->toIso8601String() : null,
            'updatedAt' => $rsvp->updated_at ? $rsvp->updated_at->toIso8601String() : null,
        ];
    }
}
