<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GuideAcceptance;
use App\Models\Matching;
use App\Models\PersonalCalendarEntry;
use App\Services\EventCalendarService;
use Carbon\Carbon;
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
        $guideScheduleItems = collect();

        if (Auth::user()?->isGuide()) {
            $guideId = Auth::id();
            $today = Carbon::today()->toDateString();

            $acceptedItems = Matching::query()
                ->where('guide_id', $guideId)
                ->whereIn('status', ['matched', 'in_progress'])
                ->whereHas('request', fn ($q) => $q->whereDate('request_date', '>=', $today))
                ->with('request:id,request_type,prefecture,destination_address,meeting_place,service_content,request_date,start_time,end_time,status')
                ->orderByDesc('matched_at')
                ->get()
                ->map(function (Matching $matching) {
                    $request = $matching->request;
                    if (!$request || !$request->request_date) {
                        return null;
                    }

                    return [
                        'kind' => 'accepted',
                        'title' => ($request->request_type === 'outing' ? '外出' : '自宅') . '支援（承諾済み）',
                        'status_label' => '承諾済み',
                        'prefecture' => $request->prefecture,
                        'place' => $request->destination_address,
                        'meeting_place' => $request->meeting_place,
                        'service_content' => $request->service_content,
                        'request_date' => $this->formatRequestDate($request->request_date),
                        'start_time' => $request->start_time,
                        'end_time' => $request->end_time,
                    ];
                })
                ->filter();

            $pendingItems = GuideAcceptance::query()
                ->where('guide_id', $guideId)
                ->where('status', 'pending')
                ->whereHas('request', fn ($q) => $q->whereDate('request_date', '>=', $today))
                ->with('request:id,request_type,prefecture,destination_address,meeting_place,service_content,request_date,start_time,end_time,status')
                ->latest('created_at')
                ->get()
                ->map(function (GuideAcceptance $acceptance) {
                    $request = $acceptance->request;
                    if (!$request || !$request->request_date) {
                        return null;
                    }

                    return [
                        'kind' => 'pending',
                        'title' => ($request->request_type === 'outing' ? '外出' : '自宅') . '支援（応募中）',
                        'status_label' => '応募中',
                        'prefecture' => $request->prefecture,
                        'place' => $request->destination_address,
                        'meeting_place' => $request->meeting_place,
                        'service_content' => $request->service_content,
                        'request_date' => $this->formatRequestDate($request->request_date),
                        'start_time' => $request->start_time,
                        'end_time' => $request->end_time,
                    ];
                })
                ->filter();

            $guideScheduleItems = $acceptedItems
                ->merge($pendingItems)
                ->sortBy(function (array $item) {
                    $startTime = $item['start_time'] ?: '00:00:00';

                    return ($item['request_date'] ?? '') . ' ' . $startTime;
                })
                ->values();
        }

        return view('calendar.personal.index', [
            'entries' => $entries,
            'guideScheduleItems' => $guideScheduleItems,
        ]);
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

    private function formatRequestDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            $raw = (string) $value;
            return strlen($raw) >= 10 ? substr($raw, 0, 10) : null;
        }
    }
}
