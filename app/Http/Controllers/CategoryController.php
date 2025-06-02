<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\SearchFilters\SearchFilter;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $data = $result = SearchFilter::apply($request, BlogCategory::where('status', 1)->where('parent_id', null));
        $data = $data->makeHidden(['parent_id', 'content', 'status']);
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
        $data = $result = SearchFilter::apply($request, BlogCategory::where('parent_id', null));
        $data = $data->makeHidden(['content']);
        $result->data = $data;
        return response()->json($result, 200);
    }

    public function show(Request $request, $id = null)
    {
        $result = BlogCategory::with(['parent', 'children'])->where('status', 1)->findOrFail($id);
        $result = $result->makeVisible(['content']);
        $result = $result->makeHidden(['parent_id', 'status']);
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
                'name' => 'required|string',
                'content' => 'nullable|string',
                'parent_id' => 'nullable|exists:blog_categories,id',
                'status' => 'integer',
            ]);
            $data = new BlogCategory;
            if ($request->has('name'))      $data->name = $request->input('name');
            if ($request->has('content'))   $data->content = $request->input('content');
            if ($request->has('parent_id')) $data->parent_id = $request->input('parent_id');
            if ($request->has('status'))    $data->status = $request->input('status', 1);
            $data->save();

            return response()->json([
                'message' => 'Category created successfully',
                'data' => $data->makeHidden(['status', 'deleted_at'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating Category: ' . $e->getMessage()
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
                'name' => 'required|string',
                'content' => 'nullable|string',
                'parent_id' => 'nullable|exists:blog_categories,id',
                'status' => 'integer',
            ]);
            $data = BlogCategory::findOrFail($id);
            if ($request->has('name'))      $data->name = $request->input('name', $data->name);
            if ($request->has('content'))   $data->content = $request->input('content', $data->content);
            if ($request->has('parent_id')) $data->parent_id = $request->input('parent_id', $data->parent_id);
            if ($request->has('status'))    $data->status = $request->input('status', $data->status);
            $data->save();
            return response()->json([
                'message' => 'Category updated successfully',
                'data' => $data->makeHidden(['status', 'deleted_at'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating Category: ' . $e->getMessage()
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
            if (BlogCategory::destroy($id)) {
                return response()->json([
                    'message' => 'Category deleted successfully'
                ], 200);
            } else {
                if (BlogCategory::forceDestroy($id)) {
                    return response()->json([
                        'message' => 'Category permanently deleted successfully'
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'Category not found'
                    ], 404);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting Category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function trashed(Request $request, $id = null)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        $data = SearchFilter::apply($request, BlogCategory::where('parent_id', $id)->onlyTrashed());
        $data = $data->makeHidden(['parent_id', 'content', 'status']);
        return response()->json($data, 200);
    }

    public function restore($id)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        try {
            $Category = BlogCategory::withTrashed()->findOrFail($id);
            if ($Category->restore()) {
                return response()->json([
                    'message' => 'Category restored successfully',
                    'data' => $Category->makeHidden(['status', 'deleted_at'])
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error restoring Category: ' . $e->getMessage()
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
            if (BlogCategory::forceDestroy($id)) {
                return response()->json([
                    'message' => 'Category permanently deleted successfully'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error permanently deleting Category: ' . $e->getMessage()
            ], 500);
        }
    }
}
