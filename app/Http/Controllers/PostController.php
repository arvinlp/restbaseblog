<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\SearchFilters\SearchFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/posts",
     *     summary="لیست پست‌های منتشر شده (عمومی)",
     *     tags={"Post"},
     *     @OA\Response(
     *         response=200,
     *         description="لیست پست‌ها"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $result = $data = SearchFilter::apply($request, BlogPost::where('status', 1), 'pagination', ['categories', 'author']);
        $data = $data->makeHidden(['content', 'author_id', 'status', 'deleted_at']);
        $result->data = $data;
        return response()->json($result, 200);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/posts/list",
     *     summary="لیست پست‌ها (خصوصی)",
     *     tags={"Post"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="لیست پست‌ها"
     *     ),
     *     @OA\Response(response=401, description="نیازمند احراز هویت",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized"))
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/posts/{id}",
     *     summary="نمایش یک پست (عمومی)",
     *     tags={"Post"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="پست پیدا شد"
     *     ),
     *     @OA\Response(response=404, description="پست پیدا نشد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Post not found"))
     *     )
     * )
     */
    public function show(Request $request, $id = null)
    {
        $result = BlogPost::with(['categories', 'author'])->where('status', 1)->findOrFail($id);
        $result = $result->makeHidden(['author_id', 'status', 'deleted_at']);
        return response()->json($result, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/posts/new",
     *     summary="ایجاد پست جدید (نیازمند احراز هویت)",
     *     tags={"Post"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","short","content"},
     *             @OA\Property(property="title", type="string", example="عنوان پست"),
     *             @OA\Property(property="short", type="string", example="خلاصه پست"),
     *             @OA\Property(property="content", type="string", example="متن کامل پست"),
     *             @OA\Property(property="thumb", type="string", example="/images/thumb.jpg"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="categories", type="array", @OA\Items(type="integer"), example={1,2})
     *         )
     *     ),
     *     @OA\Response(response=201, description="پست با موفقیت ایجاد شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post created successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="نیازمند احراز هویت",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized"))
     *     ),
     *     @OA\Response(response=422, description="خطای اعتبارسنجی",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="The given data was invalid."))
     *     )
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

    /**
     * @OA\Put(
     *     path="/api/v1/posts/{id}",
     *     summary="ویرایش پست (نیازمند احراز هویت)",
     *     tags={"Post"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","short","content"},
     *             @OA\Property(property="title", type="string", example="عنوان جدید"),
     *             @OA\Property(property="short", type="string", example="خلاصه جدید"),
     *             @OA\Property(property="content", type="string", example="متن جدید"),
     *             @OA\Property(property="thumb", type="string", example="/images/thumb.jpg"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="categories", type="array", @OA\Items(type="integer"), example={1,2})
     *         )
     *     ),
     *     @OA\Response(response=200, description="پست با موفقیت ویرایش شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post updated successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="نیازمند احراز هویت",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized"))
     *     ),
     *     @OA\Response(response=404, description="پست پیدا نشد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Post not found"))
     *     ),
     *     @OA\Response(response=422, description="خطای اعتبارسنجی",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="The given data was invalid."))
     *     )
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

    /**
     * @OA\Delete(
     *     path="/api/v1/posts/{id}",
     *     summary="حذف پست (نیازمند احراز هویت)",
     *     tags={"Post"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="پست با موفقیت حذف شد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Post deleted successfully"))
     *     ),
     *     @OA\Response(response=401, description="نیازمند احراز هویت",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized"))
     *     ),
     *     @OA\Response(response=404, description="پست پیدا نشد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Post not found"))
     *     )
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

    /**
     * @OA\Get(
     *     path="/api/v1/posts/trash",
     *     summary="لیست پست‌های حذف‌شده (نیازمند احراز هویت)",
     *     tags={"Post"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="لیست پست‌های حذف‌شده"
     *     ),
     *     @OA\Response(response=401, description="نیازمند احراز هویت",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized"))
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/posts/restore/{id}",
     *     summary="بازیابی پست حذف‌شده (نیازمند احراز هویت)",
     *     tags={"Post"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="پست با موفقیت بازیابی شد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Post restored successfully"))
     *     ),
     *     @OA\Response(response=401, description="نیازمند احراز هویت",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized"))
     *     ),
     *     @OA\Response(response=404, description="پست پیدا نشد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Post not found"))
     *     )
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

    /**
     * @OA\Delete(
     *     path="/api/v1/posts/force/{id}",
     *     summary="حذف دائمی پست (نیازمند احراز هویت)",
     *     tags={"Post"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="پست با موفقیت به طور دائمی حذف شد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Post permanently deleted successfully"))
     *     ),
     *     @OA\Response(response=401, description="نیازمند احراز هویت",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized"))
     *     ),
     *     @OA\Response(response=404, description="پست پیدا نشد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Post not found"))
     *     )
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

/**
 * @OA\Schema(
 *   schema="PostResponse",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="title", type="string", example="عنوان پست"),
 *   @OA\Property(property="short", type="string", example="خلاصه پست"),
 *   @OA\Property(property="content", type="string", example="متن کامل پست"),
 *   @OA\Property(property="thumb", type="string", example="/images/thumb.jpg"),
 *   @OA\Property(property="status", type="integer", example=1),
 *   @OA\Property(property="author", type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="first_name", type="string", example="Ali"),
 *     @OA\Property(property="last_name", type="string", example="Rezaei")
 *   ),
 *   @OA\Property(property="categories", type="array", @OA\Items(type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="دسته‌بندی")
 *   )),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
