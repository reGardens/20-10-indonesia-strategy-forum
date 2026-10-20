<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wedding;
use App\Models\Wish;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WishApiController extends Controller
{
    public function index($slug, Request $request)
    {
        if (str_starts_with($slug, 'demo-')) {
            return response()->json([
                [
                    'id' => '1',
                    'name' => 'Ahmad & Fatimah',
                    'message' => 'Selamat menempuh hidup baru Reza & Reza! Semoga menjadi keluarga yang sakinah, mawaddah, warahmah.',
                    'isApproved' => true,
                    'status' => 'approved',
                    'createdAt' => now()->toIso8601String(),
                ],
                [
                    'id' => '2',
                    'name' => 'Budi Utomo',
                    'message' => 'Barakallahu lakuma wa baraka alaikuma wa jamaa bainakuma fii khair.',
                    'isApproved' => true,
                    'status' => 'approved',
                    'createdAt' => now()->toIso8601String(),
                ]
            ]);
        }

        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $query = Wish::where('wedding_id', $wedding->id);

        if ($request->has('status')) {
            $status = $request->query('status');
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $wishes = $query->orderBy('created_at', 'desc')->get();
        return response()->json($wishes->map(fn($w) => $this->formatWish($w))->toArray());
    }

    public function store(Request $request, $slug)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $request->validate([
            'name' => 'required|string',
            'message' => 'required|string',
        ]);

        $id = $request->input('id', (string) Str::uuid());

        $wish = Wish::create([
            'id' => $id,
            'wedding_id' => $wedding->id,
            'name' => $request->name,
            'message' => $request->message,
            'is_approved' => $request->has('isApproved') ? filter_var($request->isApproved, FILTER_VALIDATE_BOOLEAN) : true,
            'status' => $request->status ?? 'pending',
        ]);

        return response()->json($this->formatWish($wish), 201);
    }

    public function updateStatus(Request $request, $slug, $id)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $wish = Wish::where('wedding_id', $wedding->id)->where('id', $id)->firstOrFail();

        $request->validate([
            'status' => 'required|string',
        ]);

        $status = $request->status;
        $isApproved = ($status === 'approved');

        $wish->update([
            'status' => $status,
            'is_approved' => $isApproved,
        ]);

        return response()->json($this->formatWish($wish));
    }

    public function destroy($slug, $id)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $wish = Wish::where('wedding_id', $wedding->id)->where('id', $id)->firstOrFail();

        $wish->delete();
        return response()->json(['success' => true]);
    }

    private function formatWish(Wish $wish)
    {
        return [
            'id' => $wish->id,
            'name' => $wish->name,
            'message' => $wish->message,
            'isApproved' => (bool)$wish->is_approved,
            'status' => $wish->status,
            'createdAt' => $wish->created_at ? $wish->created_at->toIso8601String() : null,
            'updatedAt' => $wish->updated_at ? $wish->updated_at->toIso8601String() : null,
        ];
    }
}
