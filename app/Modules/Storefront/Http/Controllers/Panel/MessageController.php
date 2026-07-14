<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\ContactMessage;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::orderByDesc('id')->get();

        return Inertia::render('Panel/Messages/Index', [
            'items' => $messages->map(fn (ContactMessage $m) => $this->present($m))->values(),
            'unread' => $messages->where('is_read', false)->count(),
        ]);
    }

    public function show(ContactMessage $message)
    {
        if (! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return Inertia::render('Panel/Messages/Show', [
            'message' => $this->present($message) + ['message' => $message->message],
            'indexUrl' => route('panel.messages.index'),
        ]);
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();

        return redirect()->route('panel.messages.index')->with('success', 'Wiadomość usunięta.');
    }

    /** Serializacja wiadomości dla React (Inertia). */
    private function present(ContactMessage $m): array
    {
        return [
            'id' => $m->id,
            'name' => $m->name,
            'email' => $m->email,
            'phone' => $m->phone,
            'subject' => $m->subject,
            'is_read' => (bool) $m->is_read,
            'created_at' => $m->created_at?->format('Y-m-d H:i'),
            'show_url' => route('panel.messages.show', $m),
            'destroy_url' => route('panel.messages.destroy', $m),
        ];
    }
}
