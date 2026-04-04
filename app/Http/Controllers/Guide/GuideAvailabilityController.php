<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\GuideAvailabilitySlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuideAvailabilityController extends Controller
{
    public function index()
    {
        $slots = GuideAvailabilitySlot::where('user_id', Auth::id())
            ->orderBy('start_at')
            ->paginate(30);

        return view('guide.availability.index', ['slots' => $slots]);
    }

    public function create()
    {
        return view('guide.availability.form', ['mode' => 'create', 'slot' => null]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'end_at' => $request->filled('end_at') ? $request->input('end_at') : null,
        ]);

        $validated = $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ]);

        GuideAvailabilitySlot::create(array_merge($validated, ['user_id' => Auth::id()]));

        return redirect()->route('guide.availability.index')
            ->with('success', '対応可能枠を追加しました。');
    }

    public function edit(int $id)
    {
        $slot = GuideAvailabilitySlot::where('user_id', Auth::id())->findOrFail($id);

        return view('guide.availability.form', ['mode' => 'edit', 'slot' => $slot]);
    }

    public function update(Request $request, int $id)
    {
        $slot = GuideAvailabilitySlot::where('user_id', Auth::id())->findOrFail($id);

        $request->merge([
            'end_at' => $request->filled('end_at') ? $request->input('end_at') : null,
        ]);

        $validated = $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ]);

        $slot->update($validated);

        return redirect()->route('guide.availability.index')
            ->with('success', '対応可能枠を更新しました。');
    }

    public function destroy(int $id)
    {
        $slot = GuideAvailabilitySlot::where('user_id', Auth::id())->findOrFail($id);
        $slot->delete();

        return redirect()->route('guide.availability.index')
            ->with('success', '対応可能枠を削除しました。');
    }
}
