<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function __construct(private EventCalendarService $eventService)
    {
    }

    public function index()
    {
        $events = Event::published()->orderBy('start_at')->paginate(20);
        $pendingEvents = collect();
        if (Auth::check() && Auth::user()->isAdmin()) {
            $pendingEvents = Event::where('status', Event::STATUS_PENDING)->orderBy('created_at', 'desc')->get();
        }

        return view('events.index', [
            'events' => $events,
            'pendingEvents' => $pendingEvents,
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
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'prefecture' => 'nullable|string|max:20',
            'place' => 'nullable|string|max:255',
            'start_at' => 'required|date|after_or_equal:now',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'url' => 'nullable|url|max:2048',
            'description' => 'nullable|string|max:5000',
            'submitter_email' => 'nullable|email|max:255',
        ]);

        $user = Auth::user();
        $isLoggedIn = (bool) $user;
        if (!$isLoggedIn && empty($validated['submitter_email'])) {
            return back()->withErrors(['submitter_email' => '非会員の場合はメールアドレスが必須です。'])->withInput();
        }

        $event = Event::create([
            'title' => $validated['title'],
            'prefecture' => $validated['prefecture'] ?? null,
            'place' => $validated['place'] ?? null,
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'] ?? null,
            'url' => $validated['url'] ?? null,
            'description' => $validated['description'] ?? null,
            'created_by' => $isLoggedIn ? $user->id : null,
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
