<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function __construct(private EventCalendarService $eventService)
    {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $category = $request->query('category');
        $sort = $request->query('sort', 'start_asc');
        $calMonth = $request->query('cal_month'); // YYYY-MM

        $applyFilters = function ($query) use ($search, $category) {
            if ($search !== '') {
                $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('place', 'like', $term)
                        ->orWhere('prefecture', 'like', $term);
                });
            }
            if ($category && array_key_exists($category, Event::CATEGORIES)) {
                $query->where('category', $category);
            }
        };

        $upcomingQuery = Event::published()->where('start_at', '>=', now()->startOfDay());
        $applyFilters($upcomingQuery);
        match ($sort) {
            'start_desc' => $upcomingQuery->orderBy('start_at', 'desc'),
            'created_desc' => $upcomingQuery->orderBy('created_at', 'desc'),
            'created_asc' => $upcomingQuery->orderBy('created_at', 'asc'),
            default => $upcomingQuery->orderBy('start_at', 'asc'),
        };
        $upcomingEvents = $upcomingQuery->get();

        $groupedUpcoming = $upcomingEvents->groupBy('category');

        $pastQuery = Event::published()->past();
        $applyFilters($pastQuery);
        $pastQuery->orderBy('start_at', 'desc');
        $pastEvents = $pastQuery->limit(80)->get();

        $calendarMonth = $calMonth && preg_match('/^\d{4}-\d{2}$/', $calMonth)
            ? Carbon::createFromFormat('Y-m', $calMonth)->startOfMonth()
            : now()->startOfMonth();
        $calStart = $calendarMonth->copy()->startOfMonth();
        $calEnd = $calendarMonth->copy()->endOfMonth();
        $calendarCounts = [];
        $calendarFirstEventIds = [];
        foreach (
            Event::published()
                ->where('start_at', '>=', $calStart)
                ->where('start_at', '<=', $calEnd)
                ->orderBy('start_at')
                ->get(['id', 'start_at']) as $ev
        ) {
            $d = $ev->start_at->format('Y-m-d');
            $calendarCounts[$d] = ($calendarCounts[$d] ?? 0) + 1;
            if (!isset($calendarFirstEventIds[$d])) {
                $calendarFirstEventIds[$d] = (int) $ev->id;
            }
        }

        $pendingEvents = collect();
        if (Auth::check() && Auth::user()->isAdmin()) {
            $pendingEvents = Event::where('status', Event::STATUS_PENDING)->orderBy('created_at', 'desc')->get();
        }

        return view('events.index', [
            'groupedUpcoming' => $groupedUpcoming,
            'pastEvents' => $pastEvents,
            'pendingEvents' => $pendingEvents,
            'categories' => Event::CATEGORIES,
            'search' => $search,
            'selectedCategory' => $category,
            'sort' => $sort,
            'calendarMonth' => $calendarMonth,
            'calendarCounts' => $calendarCounts,
            'calendarFirstEventIds' => $calendarFirstEventIds,
        ]);
    }

    public function show(int $id)
    {
        $event = Event::findOrFail($id);
        $user = Auth::user();
        if (!$this->eventService->canViewForPrefill($user, $event)) {
            abort(404);
        }

        return view('events.show', ['event' => $event]);
    }

    public function create()
    {
        return view('events.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'end_at' => $request->filled('end_at') ? $request->input('end_at') : null,
        ]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(Event::CATEGORIES)),
            'prefecture' => 'nullable|string|max:20',
            'place' => 'nullable|string|max:255',
            'start_at' => 'required|date|after_or_equal:now',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'url' => 'nullable|url|max:2048',
            'description' => 'nullable|string|max:5000',
            'submitter_name' => 'nullable|string|max:255',
            'submitter_email' => 'nullable|email|max:255',
        ]);

        $user = Auth::user();
        $isLoggedIn = (bool) $user;
        if (!$isLoggedIn && empty($validated['submitter_name'])) {
            return back()->withErrors(['submitter_name' => '非会員の場合は主催者名が必須です。'])->withInput();
        }
        if (!$isLoggedIn && empty($validated['submitter_email'])) {
            return back()->withErrors(['submitter_email' => '非会員の場合はメールアドレスが必須です。'])->withInput();
        }

        $event = Event::create([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'prefecture' => $validated['prefecture'] ?? null,
            'place' => $validated['place'] ?? null,
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'] ?? null,
            'url' => $validated['url'] ?? null,
            'description' => $validated['description'] ?? null,
            'created_by' => $isLoggedIn ? $user->id : null,
            'submitter_name' => $isLoggedIn ? ($validated['submitter_name'] ?? $user->name) : $validated['submitter_name'],
            'submitter_email' => $isLoggedIn ? null : $validated['submitter_email'],
            'email_verified_at' => $isLoggedIn ? now() : null,
            'verification_token' => $isLoggedIn ? null : Str::random(64),
            'verification_token_expires_at' => $isLoggedIn ? null : now()->addHours(24),
            'status' => $isLoggedIn ? Event::STATUS_PUBLISHED : Event::STATUS_PENDING,
        ]);

        if ($isLoggedIn) {
            return redirect()->route('events.show', $event->id)->with('success', 'イベントを公開しました。');
        }

        $verifyUrl = route('events.verify', ['id' => $event->id, 'token' => $event->verification_token]);
        Mail::raw(
            "イベント登録を完了するため、以下のURLを24時間以内に開いてください。\n\n{$verifyUrl}\n\n本メールに心当たりがない場合は破棄してください。",
            function ($message) use ($event) {
                $message->to($event->submitter_email)->subject('【One Step】イベント登録のメール認証');
            }
        );

        return redirect()->route('events.index')->with('success', '確認メールを送信しました。メール内リンクを開くと公開されます。');
    }

    public function verify(Request $request)
    {
        $id = (int) $request->query('id');
        $token = (string) $request->query('token');
        $event = Event::findOrFail($id);

        $valid = $event->status === Event::STATUS_PENDING
            && $event->verification_token
            && hash_equals($event->verification_token, $token)
            && $event->verification_token_expires_at
            && now()->lte($event->verification_token_expires_at);

        if (!$valid) {
            return redirect()->route('events.index')->withErrors(['error' => '認証リンクが無効か期限切れです。再登録してください。']);
        }

        $event->update([
            'email_verified_at' => now(),
            'status' => Event::STATUS_PUBLISHED,
            'verification_token' => null,
            'verification_token_expires_at' => null,
        ]);

        return redirect()->route('events.show', $event->id)->with('success', 'メール認証が完了し、イベントを公開しました。');
    }

    public function adminPublish(int $id)
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403);
        $event = Event::findOrFail($id);
        $event->update([
            'status' => Event::STATUS_PUBLISHED,
            'email_verified_at' => $event->email_verified_at ?? now(),
            'verification_token' => null,
            'verification_token_expires_at' => null,
        ]);

        return back()->with('success', 'イベントを公開しました。');
    }

    public function adminCancel(int $id)
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403);
        $event = Event::findOrFail($id);
        $event->update(['status' => Event::STATUS_CANCELLED]);

        return back()->with('success', 'イベントを取り下げました。');
    }
}
