<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Traits\ApiResponse;
use App\Repositories\PostRepository;
use Illuminate\Http\Request;

class PostController extends Controller
{
    use ApiResponse;

    public function __construct(protected PostRepository $postRepository) {}

    /**
     * GET /posts
     * List all social posts. Optionally marks liked posts if user is authenticated.
     */
    public function index(Request $request)
    {
        $posts = $this->postRepository->getAllWithCounts();

        $user = $request->user('sanctum');
        if ($user) {
            $likedIds = $this->postRepository->getLikedPostIds($user->id);
            $posts->each(fn ($post) => $post->user_has_liked = in_array($post->id, $likedIds));
        } else {
            $posts->each(fn ($post) => $post->user_has_liked = false);
        }

        return $this->successResponse($posts);
    }

    /**
     * POST /posts
     * Create a new social post with a Cloudinary image upload.
     */
    public function store(StorePostRequest $request)
    {
        $user = $request->user();

        $uploadResult = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::uploadApi()->upload(
            $request->file('image')->getRealPath(),
            ['folder' => 'lenslink/posts', 'resource_type' => 'image']
        );

        $post = $this->postRepository->create([
            'user_id'   => $user->id,
            'image_url' => $uploadResult['secure_url'],
            'caption'   => $request->validated('caption'),
            'location'  => $request->validated('location'),
        ]);

        return $this->createdResponse(
            $post->load('user:id,name,avatar'),
            'Post created successfully'
        );
    }

    /**
     * DELETE /posts/{id}
     * Delete a post — only the owner can delete it.
     */
    public function destroy(Request $request, int $id)
    {
        $post = $this->postRepository->findOrFail($id);

        if ($post->user_id !== $request->user()->id) {
            return $this->forbiddenResponse('You are not authorized to delete this post.');
        }

        $this->postRepository->delete($post);

        return $this->successResponse(null, 'Post deleted successfully');
    }

    /**
     * POST /posts/{id}/like
     * Toggle like/unlike on a post.
     */
    public function toggleLike(Request $request, int $id)
    {
        $post  = $this->postRepository->findOrFail($id);
        $liked = $this->postRepository->toggleLike($post, $request->user()->id);

        return $this->successResponse([
            'liked'       => $liked,
            'likes_count' => $this->postRepository->getLikeCount($post),
        ]);
    }

    /**
     * POST /posts/{id}/comment
     * Add a comment to a post.
     */
    public function addComment(Request $request, int $id)
    {
        $request->validate(['content' => 'required|string|max:1000']);

        $post    = $this->postRepository->findOrFail($id);
        $comment = $this->postRepository->addComment(
            $post,
            $request->user()->id,
            strip_tags(trim($request->content))
        );

        return $this->createdResponse(
            $comment->load('user:id,name,avatar'),
            'Comment added successfully'
        );
    }
}
