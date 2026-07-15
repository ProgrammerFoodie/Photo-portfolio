<?php

namespace App\Http\Controllers;

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

        return view('admin.messages.show', ['message' => $message]);
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()
            ->route('admin.messages.index')
            ->with('status', 'Message deleted.');
    }
}
