<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BlogPost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
        // Validate the incoming request using Validator::make
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // Validate image
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
        }

        // Create a new blog post
        $blogPost = new BlogPost();
        $blogPost->title = $request->title;
        $blogPost->content = $request->content;
        $blogPost->image = $imagePath;
        $blogPost->user_id = Auth::id(); // Associate blog post with the authenticated user
        $blogPost->save();

        return response()->json([
            'statusCode' => 201,
            'message' => 'Blog Post Successfully Created',
            'data' => $blogPost
        ], 201); // Return the newly created blog post with consistent format
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
            return response()->json([
                'statusCode' => 404,
                'message' => 'Blog post not found',
            ], 404);
        }

        // Validate the incoming request using Validator::make
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // Validate image
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

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
        $blogPost->title = $request->title;
        $blogPost->content = $request->content;
        $blogPost->save(); // Explicitly save the blog post

        return response()->json([
            'statusCode' => 200,
            'message' => 'Blog Post Successfully Updated',
            'data' => $blogPost
        ], 200); // Return the updated blog post
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
            return response()->json([
                'statusCode' => 404,
                'message' => 'Blog post not found',
            ], 404);
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'Blog Post Retrieved Successfully',
            'data' => $blogPost
        ], 200);
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
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string',
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Search query based on provided title
        $query = BlogPost::query();

        if ($request->filled('title')) {
            $query->where('title', 'LIKE', '%' . $request->title . '%');
        }

        $blogPosts = $query->paginate(10); // Return paginated results

        // Convert pagination data to an array and merge with custom attributes
        $response = array_merge($blogPosts->toArray(), [
            'statusCode' => 200,
            'message' => 'Blog Posts Retrieved Successfully',
        ]);

        return response()->json($response, 200);
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
            return response()->json([
                'statusCode' => 404,
                'message' => 'Blog post not found',
            ], 404);
        }

        // Delete the image file if it exists
        if ($blogPost->image) {
            Storage::delete('public/' . $blogPost->image);
        }

        // Delete the blog post
        $blogPost->delete();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Blog Post Successfully Deleted',
            'data' => null
        ], 200);
    }

    protected function validationErrorResponse($validator)
    {
        return response()->json([
            'statusCode' => 422,
            'message' => $validator->errors()->first(),
        ], 422);
    }
}
