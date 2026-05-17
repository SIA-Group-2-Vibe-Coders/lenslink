<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Like;
use App\Models\Comment;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class PostController extends Controller
{
    /**
     * Display a listing of social posts.
     */
    public function index(Request $request)
    {
        $posts = Post::with(['user:id,name,avatar', 'comments.user:id,name,avatar'])
            ->withCount(['likes', 'comments'])
            ->orderBy('created_at', 'desc')
            ->get();

        // If authenticated, check if the current user has liked each post
        $user = $request->user('sanctum');
        if ($user) {
            $userLikedPostIds = Like::where('user_id', $user->id)
                ->pluck('post_id')
                ->toArray();

            $posts->each(function ($post) use ($userLikedPostIds) {
                $post->user_has_liked = in_array($post->id, $userLikedPostIds);
            });
        } else {
            $posts->each(function ($post) {
                $post->user_has_liked = false;
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $posts
        ]);
    }

    /**
     * Store a newly created social post.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // max 10MB
            'caption' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        $user = $request->user();

        // Upload to Cloudinary
        $file = $request->file('image');
        $uploadResult = Cloudinary::uploadApi()->upload($file->getRealPath(), [
            'folder' => 'lenslink/posts',
            'resource_type' => 'image',
        ]);

        $imageUrl = $uploadResult['secure_url'];

        $post = Post::create([
            'user_id' => $user->id,
            'image_url' => $imageUrl,
            'caption' => $request->caption,
            'location' => $request->location,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Post created successfully',
            'data' => $post->load('user:id,name,avatar')
        ], 210); // Use 201 Created or 200
    }

    /**
     * Remove the specified social post.
     */
    public function destroy(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $user = $request->user();

        if ($post->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to delete this post'
            ], 403);
        }

        $post->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Post deleted successfully'
        ]);
    }

    /**
     * Toggle like/unlike on a post.
     */
    public function toggleLike(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $user = $request->user();

        $like = Like::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            Like::create([
                'post_id' => $post->id,
                'user_id' => $user->id
            ]);
            $liked = true;
        }

        return response()->json([
            'status' => 'success',
            'liked' => $liked,
            'likes_count' => $post->likes()->count()
        ]);
    }

    /**
     * Add a comment to a post.
     */
    public function addComment(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $post = Post::findOrFail($id);
        $user = $request->user();

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => $request->content
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Comment added successfully',
            'data' => $comment->load('user:id,name,avatar')
        ]);
    }
}
