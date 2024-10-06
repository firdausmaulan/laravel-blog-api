<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BlogPost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BlogPostController extends Controller
{
    /**
     * Store a newly created blog post in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // Validate image
        ]);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
        }

        // Create a new blog post
        $blogPost = BlogPost::create([
            'title' => $validatedData['title'],
            'content' => $validatedData['content'],
            'image' => $imagePath,
            'user_id' => Auth::id(), // Associate blog post with the authenticated user
        ]);

        return response()->json($blogPost, 201); // Return the newly created blog post
    }

    /**
     * Update the specified blog post in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $blogPost = BlogPost::find($id);

        if (!$blogPost) {
            return response()->json(['error' => 'Blog post not found'], 404);
        }

        // Validate the incoming request
        $validatedData = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // Validate image
        ]);

        // Handle image upload if exists
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($blogPost->image) {
                Storage::delete('public/' . $blogPost->image);
            }

            $path = $request->file('image')->store('images', 'public');
            $blogPost->image = $path;
        }

        // Update blog post with request data
        $blogPost->update($validatedData);

        return response()->json($blogPost, 200); // Return the updated blog post
    }

    /**
     * Display the specified blog post.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id)
    {
        $blogPost = BlogPost::find($id);

        if (!$blogPost) {
            return response()->json(['error' => 'Blog post not found'], 404);
        }

        return response()->json($blogPost, 200);
    }

    /**
     * Search for blog posts by title.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        // Validate search query
        $validatedData = $request->validate([
            'title' => 'nullable|string',
        ]);

        // Search query based on provided title
        $query = BlogPost::query();

        if ($request->filled('title')) {
            $query->where('title', 'LIKE', '%' . $request->title . '%');
        }

        $blogPosts = $query->paginate(10); // Return paginated results

        return response()->json($blogPosts, 200);
    }

    /**
     * Remove the specified blog post from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $blogPost = BlogPost::find($id);

        if (!$blogPost) {
            return response()->json(['error' => 'Blog post not found'], 404);
        }

        // Delete the image file if it exists
        if ($blogPost->image) {
            Storage::delete('public/' . $blogPost->image);
        }

        // Delete the blog post
        $blogPost->delete();

        return response()->json(['message' => 'Blog post deleted successfully'], 200);
    }
}
