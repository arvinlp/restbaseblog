<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\SearchFilters\SearchFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $result = $data = SearchFilter::apply($request, BlogPost::where('status', 1), 'pagination', ['categories', 'author']);
        $data = $data->makeHidden(['content', 'author_id', 'status', 'deleted_at']);
        $result->data = $data;
        return response()->json($result, 200);
    }
    
    public function list(Request $request)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        $result = $data = SearchFilter::apply($request, new BlogPost, 'pagination', ['categories', 'author']);
        $data = $data->makeHidden(['content', 'deleted_at']);
        $result->data = $data;
        return response()->json($result, 200);
    }

    public function show(Request $request, $id = null)
    {
        $result = BlogPost::with(['categories', 'author'])->where('status', 1)->findOrFail($id);
        $result = $result->makeHidden(['author_id', 'status', 'deleted_at']);
        return response()->json($result, 200);
    }

    public function create(Request $request)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        try {
            $request->validate([
                'title' => 'required|string',
                'short' => 'required|string',
                'content' => 'required|string',
                'thumb' => 'string',
                'status' => 'integer',
            ]);
            $data = new BlogPost;
            $data->author_id = Auth::user()->id;
            if ($request->has('title'))  $data->title = $request->input('title');
            if ($request->has('short'))  $data->short = $request->input('short');
            if ($request->has('content')) $data->content = $request->input('content');
            if ($request->has('thumb'))  $data->thumb = $request->input('thumb');
            if ($request->has('status')) {
                if ($request->input('status') == 3) {
                    $data->status = $request->input('status', 3);
                    $data->created_at = $request->input('scheduled_at', now());
                    $data->updated_at = $request->input('scheduled_at', now());
                } else {
                    $data->status = $request->input('status', 1);
                    $data->created_at = now();
                    $data->updated_at = now();
                }
            }
            $data->save();
            $data->categories()->attach($request->input('categories', []));
            return response()->json([
                'message' => 'Post created successfully',
                'data' => $data->makeHidden(['author_id', 'status', 'deleted_at'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id = null)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        try {
            $request->validate([
                'title' => 'required|string',
                'short' => 'required|string',
                'content' => 'required|string',
                'thumb' => 'string',
                'status' => 'integer',
            ]);
            $data = BlogPost::findOrFail($id);
            if ($request->has('title'))  $data->title = $request->input('title', $data->title);
            if ($request->has('short'))  $data->short = $request->input('short', $data->short);
            if ($request->has('content')) $data->content = $request->input('content', $data->content);
            if ($request->has('thumb'))  $data->thumb = $request->input('thumb', $data->thumb);
            if ($request->has('status')) {
                if ($request->input('status') == 3) {
                    $data->status = $request->input('status', 3);
                    $data->created_at = $request->input('scheduled_at', now());
                    $data->updated_at = $request->input('scheduled_at', now());
                } else {
                    $data->status = $request->input('status', $data->status);
                }
            }
            $data->save();
            $data->categories()->detach();
            $data->categories()->attach($request->input('categories', []));
            return response()->json([
                'message' => 'Post updated successfully',
                'data' => $data->makeHidden(['author_id', 'status', 'deleted_at'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function delete($id = null)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        try {
            if (BlogPost::destroy($id)) {
                return response()->json([
                    'message' => 'Post deleted successfully'
                ], 200);
            } else {
                if (BlogPost::forceDestroy($id)) {
                    return response()->json([
                        'message' => 'Post permanently deleted successfully'
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'Post not found'
                    ], 404);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function trash(Request $request)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        $result = $data = SearchFilter::apply($request, BlogPost::onlyTrashed(), 'pagination', ['categories', 'author']);
        $data = $data->makeHidden(['content', 'author_id', 'status', 'deleted_at']);
        $result->data = $data;
        return response()->json($result, 200);
    }

    public function restore($id)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        try {
            $post = BlogPost::withTrashed()->findOrFail($id);
            if ($post->restore()) {
                return response()->json([
                    'message' => 'Post restored successfully',
                    'data' => $post->makeHidden(['author_id', 'status', 'deleted_at'])
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Post not found'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error restoring post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function forceDelete($id = null)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        try {
            if (BlogPost::forceDestroy($id)) {
                return response()->json([
                    'message' => 'Post permanently deleted successfully'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Post not found'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error permanently deleting post: ' . $e->getMessage()
            ], 500);
        }
    }
}
