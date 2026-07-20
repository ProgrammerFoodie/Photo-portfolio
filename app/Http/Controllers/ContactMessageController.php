<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(): View
    {
        return view('admin.messages.index', [
            'messages' => ContactMessage::query()
                ->orderByDesc('created_at')
                ->paginate(20),
        ]);
    }

    public function show(ContactMessage $message): View
    {
        $message->markAsRead();

        ActivityLog::log('message.viewed', "Viewed message from \"{$message->name}\"");

        return view('admin.messages.show', ['message' => $message]);
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $name = $message->name;
        $message->delete();

        ActivityLog::log('message.deleted', "Deleted message from \"{$name}\"");

        return redirect()
            ->route('admin.messages.index')
            ->with('status', 'Message deleted.');
    }
}
