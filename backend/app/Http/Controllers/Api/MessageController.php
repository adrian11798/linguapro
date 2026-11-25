<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Mensajes directos. Un profesor y un alumno solo pueden escribirse si el
 * alumno está inscrito en alguno de los cursos que imparte ese profesor.
 */
class MessageController extends Controller
{
    /** Conversaciones del usuario, la más reciente primero. */
    public function index(Request $request)
    {
        $me = $request->user()->id;

        $messages = Message::where('sender_id', $me)
            ->orWhere('recipient_id', $me)
            ->orderByDesc('id')
            ->get();

        $otherId = fn (Message $m) => $m->sender_id === $me ? $m->recipient_id : $m->sender_id;
        $users = User::whereIn('id', $messages->map($otherId)->unique()->values())->get()->keyBy('id');

        $threads = $messages->groupBy($otherId)->map(function ($msgs, $id) use ($me, $users) {
            $user = $users[$id] ?? null;
            if (! $user) {
                return null;
            }
            $last = $msgs->first();

            return [
                'user' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role],
                'last_message' => $last->body,
                'last_at' => $last->created_at,
                'unread' => $msgs->where('recipient_id', $me)->whereNull('read_at')->count(),
            ];
        })->filter()->values();

        return response()->json($threads);
    }

    /** Personas a las que el usuario puede escribir. */
    public function contacts(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'teacher') {
            $courseIds = $user->coursesTaught()->pluck('id');
            $ids = Enrollment::whereIn('course_id', $courseIds)->pluck('user_id')->unique();
            $people = User::whereIn('id', $ids)->where('role', 'student')->orderBy('name')->get();
        } elseif ($user->role === 'student') {
            $courseIds = $user->enrollments()->pluck('course_id');
            $people = User::where('role', 'teacher')
                ->whereHas('coursesTaught', fn ($q) => $q->whereIn('id', $courseIds))
                ->orderBy('name')
                ->get();
        } else {
            $people = collect();
        }

        return response()->json(
            $people->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'role' => $u->role])->values()
        );
    }

    /** Mensajes sin leer (para el globo de la barra de navegación). */
    public function unreadCount(Request $request)
    {
        return response()->json([
            'count' => Message::where('recipient_id', $request->user()->id)->whereNull('read_at')->count(),
        ]);
    }

    /** Conversación con otra persona; marca como leídos los mensajes recibidos. */
    public function thread(Request $request, User $user)
    {
        $me = $request->user();

        $messages = Message::where(fn ($q) => $q->where('sender_id', $me->id)->where('recipient_id', $user->id))
            ->orWhere(fn ($q) => $q->where('sender_id', $user->id)->where('recipient_id', $me->id))
            ->orderBy('id')
            ->get();

        // Si no hay historial ni relación de cursos, no tiene por qué verla.
        abort_unless($messages->isNotEmpty() || $this->canMessage($me, $user), 403, 'No puedes ver esta conversación.');

        Message::where('sender_id', $user->id)
            ->where('recipient_id', $me->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'user' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role],
            'messages' => $messages->map(fn (Message $m) => [
                'id' => $m->id,
                'mine' => $m->sender_id === $me->id,
                'body' => $m->body,
                'created_at' => $m->created_at,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_id' => ['required', 'exists:users,id'],
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'body.required' => 'Escribe un mensaje.',
            'body.max' => 'El mensaje es demasiado largo (máximo 2000 caracteres).',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $recipient = User::findOrFail($request->recipient_id);
        abort_unless($this->canMessage($request->user(), $recipient), 403, 'No puedes escribirle a esta persona.');

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $recipient->id,
            'body' => trim($request->body),
        ]);

        return response()->json([
            'id' => $message->id,
            'mine' => true,
            'body' => $message->body,
            'created_at' => $message->created_at,
        ], 201);
    }

    /** Profesor <-> alumno inscrito en un curso de ese profesor. */
    private function canMessage(User $a, User $b): bool
    {
        if ($a->id === $b->id) {
            return false;
        }

        if ($a->role === 'teacher' && $b->role === 'student') {
            [$teacher, $student] = [$a, $b];
        } elseif ($a->role === 'student' && $b->role === 'teacher') {
            [$teacher, $student] = [$b, $a];
        } else {
            return false;
        }

        return Enrollment::where('user_id', $student->id)
            ->whereIn('course_id', $teacher->coursesTaught()->pluck('id'))
            ->exists();
    }
}
