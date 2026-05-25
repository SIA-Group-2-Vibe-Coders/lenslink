<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Http\Traits\ApiResponse;
use App\Services\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    use ApiResponse;

    public function __construct(protected MessageService $messageService) {}

    /**
     * GET /messages?gallery_id=X OR /messages?receiver_id=Y
     * Fetch message history.
     */
    public function index(Request $request)
    {
        $request->validate([
            'gallery_id'  => 'nullable|integer|exists:galleries,id',
            'receiver_id' => 'nullable|integer|exists:users,id',
        ]);

        if (!$request->gallery_id && !$request->receiver_id) {
            return $this->errorResponse('Either gallery_id or receiver_id is required.', 422);
        }

        $messages = $this->messageService->getHistory(
            $request->user(),
            $request->gallery_id ? (int) $request->gallery_id : null,
            $request->receiver_id ? (int) $request->receiver_id : null,
        );

        return $this->successResponse($messages);
    }

    /**
     * POST /messages
     * Send a new message.
     */
    public function store(StoreMessageRequest $request)
    {
        $message = $this->messageService->sendMessage($request->user(), $request->validated());

        return $this->createdResponse($message, 'Message sent.');
    }

    /**
     * GET /conversations
     * Fetch list of users the current user has chatted with.
     */
    public function conversations(Request $request)
    {
        $contacts = $this->messageService->getConversations($request->user());

        return $this->successResponse($contacts);
    }
}
