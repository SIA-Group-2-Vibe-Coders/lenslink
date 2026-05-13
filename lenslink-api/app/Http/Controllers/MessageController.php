<?php

namespace App\Http\Controllers;

use App\Services\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    protected $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * GET /messages?gallery_id=X OR /messages?receiver_id=Y
     * Fetch message history.
     */
    public function index(Request $request)
    {
        $request->validate([
            'gallery_id'  => 'nullable|exists:galleries,id',
            'receiver_id' => 'nullable|exists:users,id',
        ]);

        if (!$request->gallery_id && !$request->receiver_id) {
            return response()->json(['error' => 'Either gallery_id or receiver_id is required.'], 422);
        }

        $messages = $this->messageService->getHistory(
            $request->user(),
            $request->gallery_id,
            $request->receiver_id
        );

        return response()->json([
            'status' => 'success',
            'data'   => $messages,
        ]);
    }

    /**
     * POST /messages
     * Send a new message.
     */
    public function store(Request $request)
    {
        $request->validate([
            'gallery_id'  => 'nullable|exists:galleries,id',
            'receiver_id' => 'nullable|exists:users,id',
            'body'        => 'required|string|max:2000',
        ]);

        if (!$request->gallery_id && !$request->receiver_id) {
            return response()->json(['error' => 'Either gallery_id or receiver_id is required.'], 422);
        }

        $message = $this->messageService->sendMessage($request->user(), $request->all());

        return response()->json([
            'status'  => 'success',
            'message' => 'Message sent.',
            'data'    => $message,
        ], 201);
    }

    /**
     * GET /conversations
     * Fetch list of users the current user has chatted with.
     */
    public function conversations(Request $request)
    {
        $contacts = $this->messageService->getConversations($request->user());

        return response()->json([
            'status' => 'success',
            'data'   => $contacts,
        ]);
    }
}

