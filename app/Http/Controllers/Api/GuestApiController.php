<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wedding;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestApiController extends Controller
{
    public function index($slug, Request $request)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $query = Guest::where('wedding_id', $wedding->id);

        if ($request->has('q')) {
            $search = $request->query('q');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $guests = $query->get();
        return response()->json($guests->map(fn($g) => $this->formatGuest($g))->toArray());
    }

    public function show($slug, $id)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $guest = Guest::where('wedding_id', $wedding->id)->where('id', $id)->firstOrFail();

        return response()->json($this->formatGuest($guest));
    }

    public function store(Request $request, $slug)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        
        $id = $request->input('id', (string) Str::uuid());

        $guest = Guest::create([
            'id' => $id,
            'wedding_id' => $wedding->id,
            'name' => $request->name,
            'group' => $request->group,
            'code' => $request->code,
            'phone' => $request->phone,
            'is_vip' => filter_var($request->isVip ?? false, FILTER_VALIDATE_BOOLEAN),
            'max_pax' => (int) ($request->maxPax ?? 1),
            'rsvp_status' => $request->rsvpStatus ?? 'pending',
        ]);

        return response()->json($this->formatGuest($guest), 201);
    }

    public function batchStore(Request $request, $slug)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $guestsData = $request->input('guests', []);

        $created = [];
        foreach ($guestsData as $data) {
            $id = $data['id'] ?? (string) Str::uuid();
            $guest = Guest::create([
                'id' => $id,
                'wedding_id' => $wedding->id,
                'name' => $data['name'],
                'group' => $data['group'] ?? null,
                'code' => $data['code'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_vip' => filter_var($data['isVip'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'max_pax' => (int) ($data['maxPax'] ?? 1),
                'rsvp_status' => $data['rsvpStatus'] ?? 'pending',
            ]);
            $created[] = $this->formatGuest($guest);
        }

        return response()->json($created, 201);
    }

    public function update(Request $request, $slug, $id)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $guest = Guest::where('wedding_id', $wedding->id)->where('id', $id)->firstOrFail();

        $guest->update([
            'name' => $request->name ?? $guest->name,
            'group' => $request->has('group') ? $request->group : $guest->group,
            'code' => $request->has('code') ? $request->code : $guest->code,
            'phone' => $request->has('phone') ? $request->phone : $guest->phone,
            'is_vip' => $request->has('isVip') ? filter_var($request->isVip, FILTER_VALIDATE_BOOLEAN) : $guest->is_vip,
            'max_pax' => $request->has('maxPax') ? (int) $request->maxPax : $guest->max_pax,
            'rsvp_status' => $request->rsvpStatus ?? $guest->rsvp_status,
        ]);

        return response()->json($this->formatGuest($guest));
    }

    public function destroy($slug, $id)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $guest = Guest::where('wedding_id', $wedding->id)->where('id', $id)->firstOrFail();

        $guest->delete();
        return response()->json(['success' => true]);
    }

    public function checkin($slug, $id)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $guest = Guest::where('wedding_id', $wedding->id)->where('id', $id)->firstOrFail();

        $guest->update([
            'checked_in_at' => now(),
        ]);

        return response()->json($this->formatGuest($guest));
    }

    private function formatGuest(Guest $guest)
    {
        return [
            'id' => $guest->id,
            'name' => $guest->name,
            'group' => $guest->group,
            'code' => $guest->code,
            'phone' => $guest->phone,
            'isVip' => $guest->is_vip,
            'maxPax' => $guest->max_pax,
            'rsvpStatus' => $guest->rsvp_status,
            'checkedInAt' => $guest->checked_in_at ? $guest->checked_in_at->toIso8601String() : null,
            'createdAt' => $guest->created_at ? $guest->created_at->toIso8601String() : null,
            'updatedAt' => $guest->updated_at ? $guest->updated_at->toIso8601String() : null,
        ];
    }
}
