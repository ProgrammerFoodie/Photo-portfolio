<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.about', [
            'title' => Setting::get('about_title'),
            'body' => Setting::get('about_body'),
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact', [
            'title' => Setting::get('contact_title'),
            'body' => Setting::get('contact_body'),
            'socialLinks' => Setting::socialLinks(),
        ]);
    }

    public function submitContact(StoreContactMessageRequest $request): RedirectResponse
    {
        ContactMessage::create($request->validated());

        return redirect()
            ->route('contact')
            ->with('status', 'Thanks for reaching out — your message has been sent.');
    }
}
