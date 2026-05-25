<?php

namespace App\Repositories;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class PostRepository
{
    /**
     * Get all posts with eager-loaded relations and counts.
     */
    public function getAllWithCounts(): Collection
    {
        return Post::with(['user:id,name,avatar', 'comments.user:id,name,avatar'])
            ->withCount(['likes', 'comments'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get the post IDs liked by a specific user.
     */
    public function getLikedPostIds(int $userId): array
    {
        return Like::where('user_id', $userId)->pluck('post_id')->toArray();
    }

    /**
     * Create a new post.
     */
    public function create(array $data): Post
    {
        return Post::create($data);
    }

    /**
     * Find a post by ID.
     */
    public function findOrFail(int $id): Post
    {
        return Post::findOrFail($id);
    }

    /**
     * Delete a post.
     */
    public function delete(Post $post): void
    {
        $post->delete();
    }

    /**
     * Toggle like on a post — returns whether it was liked (true) or unliked (false).
     */
    public function toggleLike(Post $post, int $userId): bool
    {
        $like = Like::where('post_id', $post->id)->where('user_id', $userId)->first();

        if ($like) {
            $like->delete();
            return false;
        }

        Like::create(['post_id' => $post->id, 'user_id' => $userId]);
        return true;
    }

    /**
     * Add a comment to a post.
     */
    public function addComment(Post $post, int $userId, string $content): Comment
    {
        return Comment::create([
            'post_id' => $post->id,
            'user_id' => $userId,
            'content' => $content,
        ]);
    }

    /**
     * Get the current like count for a post.
     */
    public function getLikeCount(Post $post): int
    {
        return $post->likes()->count();
    }
}
