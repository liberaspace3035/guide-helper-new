<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\PersonalCalendarEntry;
use App\Services\EventCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalCalendarController extends Controller
{
    public function __construct(private EventCalendarService $eventService)
    {
    }

    public function index()
    {
        $entries = PersonalCalendarEntry::where('user_id', Auth::id())->orderBy('start_at')->paginate(30);

        return view('calendar.personal.index', ['entries' => $entries]);
    }

    public function create(Request $request)
    {
        $prefill = null;
        $eventId = $request->query('event_id');
        if ($eventId) {
            $event = Event::findOrFail((int) $eventId);
            if (!$this->eventService->canViewForPrefill(Auth::user(), $event)) {
                abort(404);
            }
            $prefill = $this->eventService->toPrefillForPersonal($event);
        }

        return view('calendar.personal.form', ['mode' => 'create', 'entry' => null, 'prefill' => $prefill]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'nullable|exists:events,id',
            'title' => 'required|string|max:255',
            'prefecture' => 'nullable|string|max:20',
            'place' => 'nullable|string|max:255',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'url' => 'nullable|url|max:2048',
            'description' => 'nullable|string|max:5000',
        ]);

        PersonalCalendarEntry::create(array_merge($validated, ['user_id' => Auth::id()]));

        return redirect()->route('calendar.personal.index')->with('success', 'マイカレンダーに追加しました。');
    }

    public function edit(int $id)
    {
        $entry = PersonalCalendarEntry::where('user_id', Auth::id())->findOrFail($id);

        return view('calendar.personal.form', ['mode' => 'edit', 'entry' => $entry, 'prefill' => null]);
    }

    public function update(Request $request, int $id)
    {
        $entry = PersonalCalendarEntry::where('user_id', Auth::id())->findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'prefecture' => 'nullable|string|max:20',
            'place' => 'nullable|string|max:255',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'url' => 'nullable|url|max:2048',
            'description' => 'nullable|string|max:5000',
        ]);
        $entry->update($validated);

        return redirect()->route('calendar.personal.index')->with('success', '予定を更新しました。');
    }

    public function destroy(int $id)
    {
        $entry = PersonalCalendarEntry::where('user_id', Auth::id())->findOrFail($id);
        $entry->delete();

        return redirect()->route('calendar.personal.index')->with('success', '予定を削除しました。');
    }
}
