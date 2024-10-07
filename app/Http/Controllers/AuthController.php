<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:user,admin',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
        }

        // Handle location data
        $location = null;
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $location = new Point($request->longitude, $request->latitude);
        }

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'image' => $imagePath,
            'address' => $request->address,
            'location' => $location,
        ]);

        // Generate a JWT token for the new user
        $token = Auth::login($user);

        return response()->json([
            'statusCode' => 201,
            'message' => 'User registered successfully',
            'data' => $this->prepareUserResponse($user, $token),
        ], 201);
    }

    // User login
    public function login(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Handle validation failure
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Attempt to authenticate and generate a token
        if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'statusCode' => 401,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Retrieve the authenticated user
        $user = auth()->user();

        return response()->json([
            'statusCode' => 200,
            'message' => 'User registered successfully',
            'data' => $this->prepareUserResponse($user, $token),
        ], 200);
    }

    // User logout
    public function logout()
    {
        Auth::logout();
        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully logged out',
        ], 200);
    }

    // Update user profile
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Check if the authenticated user is allowed to update this user
        if (Auth::user()->role !== 'admin' && Auth::id() !== $user->id) {
            return response()->json([
                'statusCode' => 403,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
            'role' => 'sometimes|string|in:user,admin',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Update user fields
        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('password')) $user->password = Hash::make($request->password);
        if ($request->has('role')) $user->role = $request->role;
        if ($request->hasFile('image')) $user->image = $request->file('image')->store('images', 'public');
        if ($request->has('address')) $user->address = $request->address;
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $user->location = new Point($request->longitude, $request->latitude);
        }

        // Save updated user data
        $user->save();

        return response()->json([
            'statusCode' => 200,
            'message' => 'User registered successfully',
            'data' => $this->prepareUserResponse($user),
        ], 200);
    }

    // Get user details
    public function detail($id)
    {
        $user = User::findOrFail($id);

        // Check if the authenticated user is allowed to see this user's details
        if (Auth::user()->role !== 'admin' && Auth::id() !== $user->id) {
            return response()->json([
                'statusCode' => 403,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'User registered successfully',
            'data' => $this->prepareUserResponse($user),
        ], 200);
    }

    // Search for users
    public function search(Request $request)
    {
        // Validate the search query
        $validator = Validator::make($request->all(), [
            'query' => 'nullable|string|max:255',
            'role' => 'nullable|string|in:user,admin',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Build the search query
        $query = $request->input('query');
        $role = $request->input('role');

        $users = User::query();
        if ($query) {
            $users->where(function ($q) use ($query) {
                $q->where('name', 'like', "%$query%")
                  ->orWhere('email', 'like', "%$query%");
            });
        }

        if ($role) {
            $users->where('role', $role);
        }

        $result = $users->get();

        // Map the results to include latitude and longitude at the same level
        $formattedResult = $result->map(function ($user) {
            return $this->prepareUserResponse($user);
        });

        return response()->json([
            'statusCode' => 200,
            'message' => 'Users retrieved successfully',
            'data' => $formattedResult,
        ], 200);
    }

    protected function validationErrorResponse($validator)
    {
        return response()->json([
            'statusCode' => 422,
            'message' => $validator->errors()->first(),
        ], 422);
    }

    protected function prepareUserResponse(User $user, $token = null)
    {
        // Extract latitude and longitude from location
        $latitude = $longitude = null;
        if ($user->location) {
            $arrayUserLocation = $user->location->toArray();
            $latitude = $arrayUserLocation['coordinates'][0];
            $longitude = $arrayUserLocation['coordinates'][1];
        }

        // Make the 'location' field hidden
        $user->makeHidden(['location']);

        if ($token == null) {
            return array_merge($user->toArray(), [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        } else {
            return array_merge($user->toArray(), [
                'token' => $token,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        }
    }
}