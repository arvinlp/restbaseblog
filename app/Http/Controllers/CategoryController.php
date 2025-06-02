<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\SearchFilters\SearchFilter;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/categories",
     *     summary="لیست دسته‌بندی‌های اصلی (عمومی)",
     *     tags={"Category"},
     *     @OA\Response(response=200, description="لیست دسته‌بندی‌ها")
     * )
     */
    public function index(Request $request)
    {
        $data = $result = SearchFilter::apply($request, BlogCategory::where('status', 1)->where('parent_id', null));
        $data = $data->makeHidden(['parent_id', 'content', 'status']);
        $result->data = $data;
        return response()->json($result, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/categories/list",
     *     summary="لیست دسته‌بندی‌ها (خصوصی)",
     *     tags={"Category"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="لیست دسته‌بندی‌ها"),
     *     @OA\Response(response=401, description="نیازمند احراز هویت")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/categories/{id}",
     *     summary="نمایش یک دسته‌بندی (عمومی)",
     *     tags={"Category"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="دسته‌بندی پیدا شد"),
     *     @OA\Response(response=404, description="دسته‌بندی پیدا نشد")
     * )
     */
    public function show(Request $request, $id = null)
    {
        $result = BlogCategory::with(['parent', 'children'])->where('status', 1)->findOrFail($id);
        $result = $result->makeVisible(['content']);
        $result = $result->makeHidden(['parent_id', 'status']);
        return response()->json($result, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/categories/new",
     *     summary="ایجاد دسته‌بندی جدید (نیازمند احراز هویت)",
     *     tags={"Category"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="content", type="string"),
     *             @OA\Property(property="parent_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="دسته‌بندی با موفقیت ایجاد شد"),
     *     @OA\Response(response=401, description="نیازمند احراز هویت")
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/v1/categories/{id}",
     *     summary="ویرایش دسته‌بندی (نیازمند احراز هویت)",
     *     tags={"Category"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="content", type="string"),
     *             @OA\Property(property="parent_id", type="integer"),
     *             @OA\Property(property="status", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="دسته‌بندی با موفقیت ویرایش شد"),
     *     @OA\Response(response=401, description="نیازمند احراز هویت"),
     *     @OA\Response(response=404, description="دسته‌بندی پیدا نشد")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/v1/categories/{id}",
     *     summary="حذف دسته‌بندی (نیازمند احراز هویت)",
     *     tags={"Category"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="دسته‌بندی با موفقیت حذف شد"),
     *     @OA\Response(response=401, description="نیازمند احراز هویت"),
     *     @OA\Response(response=404, description="دسته‌بندی پیدا نشد")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/categories/trashed/{id}",
     *     summary="لیست دسته‌بندی‌های حذف‌شده (نیازمند احراز هویت)",
     *     tags={"Category"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="لیست دسته‌بندی‌های حذف‌شده"),
     *     @OA\Response(response=401, description="نیازمند احراز هویت")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/categories/restore/{id}",
     *     summary="بازیابی دسته‌بندی حذف‌شده (نیازمند احراز هویت)",
     *     tags={"Category"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="دسته‌بندی با موفقیت بازیابی شد"),
     *     @OA\Response(response=401, description="نیازمند احراز هویت"),
     *     @OA\Response(response=404, description="دسته‌بندی پیدا نشد")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/v1/categories/force-delete/{id}",
     *     summary="حذف دائمی دسته‌بندی (نیازمند احراز هویت)",
     *     tags={"Category"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="دسته‌بندی با موفقیت به صورت دائمی حذف شد"),
     *     @OA\Response(response=401, description="نیازمند احراز هویت"),
     *     @OA\Response(response=404, description="دسته‌بندی پیدا نشد")
     * )
     */
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
