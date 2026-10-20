<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wedding;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventApiController extends Controller
{
    public function index($slug)
    {
        if (str_starts_with($slug, 'demo-')) {
            return response()->json([
                [
                    'id' => 1,
                    'name' => 'Akad Nikah',
                    'date' => '2026-08-18',
                    'timeStart' => '08:00',
                    'timeEnd' => '10:00',
                    'timezone' => 'WIB',
                    'locationName' => 'Masjid Raya Agung',
                    'locationAddress' => 'Jl. Kebahagiaan No. 1, Jakarta',
                    'locationMapUrl' => 'https://maps.google.com',
                ],
                [
                    'id' => 2,
                    'name' => 'Resepsi Pernikahan',
                    'date' => '2026-08-18',
                    'timeStart' => '11:00',
                    'timeEnd' => '14:00',
                    'timezone' => 'WIB',
                    'locationName' => 'Gedung Agung Nusantara',
                    'locationAddress' => 'Jl. Persatuan No. 100, Jakarta',
                    'locationMapUrl' => 'https://maps.google.com',
                ]
            ]);
        }

        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $events = Event::where('wedding_id', $wedding->id)->get();

        return response()->json($events->map(fn($e) => $this->formatEvent($e))->toArray());
    }

    public function show($slug, $id)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $event = Event::where('wedding_id', $wedding->id)->where('id', $id)->firstOrFail();

        return response()->json($this->formatEvent($event));
    }

    public function store(Request $request, $slug)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $request->validate(['name' => 'required|string']);

        $id = $request->input('id', (string) Str::uuid());

        $event = Event::create([
            'id' => $id,
            'wedding_id' => $wedding->id,
            'name' => $request->name,
            'date' => $request->date,
            'time_start' => $request->timeStart,
            'time_end' => $request->timeEnd,
            'timezone' => $request->timezone,
            'location_name' => $request->locationName,
            'location_address' => $request->locationAddress,
            'location_map_url' => $request->locationMapUrl,
        ]);

        return response()->json($this->formatEvent($event), 201);
    }

    public function update(Request $request, $slug, $id)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $event = Event::where('wedding_id', $wedding->id)->where('id', $id)->firstOrFail();

        $event->update([
            'name' => $request->name ?? $event->name,
            'date' => $request->date ?? $event->date,
            'time_start' => $request->timeStart ?? $event->time_start,
            'time_end' => $request->timeEnd ?? $event->time_end,
            'timezone' => $request->timezone ?? $event->timezone,
            'location_name' => $request->locationName ?? $event->location_name,
            'location_address' => $request->locationAddress ?? $event->location_address,
            'location_map_url' => $request->locationMapUrl ?? $event->location_map_url,
        ]);

        return response()->json($this->formatEvent($event));
    }

    public function destroy($slug, $id)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $event = Event::where('wedding_id', $wedding->id)->where('id', $id)->firstOrFail();

        $event->delete();
        return response()->json(['success' => true]);
    }

    private function formatEvent(Event $event)
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'date' => $event->date,
            'timeStart' => $event->time_start,
            'timeEnd' => $event->time_end,
            'timezone' => $event->timezone,
            'locationName' => $event->location_name,
            'locationAddress' => $event->location_address,
            'locationMapUrl' => $event->location_map_url,
            'createdAt' => $event->created_at ? $event->created_at->toIso8601String() : null,
            'updatedAt' => $event->updated_at ? $event->updated_at->toIso8601String() : null,
        ];
    }
}
