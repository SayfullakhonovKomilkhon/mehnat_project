<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminArticleImageController extends Controller
{
    /**
     * Get all images for an article.
     */
    public function index(int $articleId): JsonResponse
    {
        $article = Article::find($articleId);

        if (!$article) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $images = $article->images()->get()->map(fn ($img) => [
            'id' => $img->id,
            'filename' => $img->filename,
            'original_name' => $img->original_name,
            'url' => $img->url,
            'size' => $img->human_size,
            'order' => $img->order,
        ]);

        return $this->success(['images' => $images]);
    }

    /**
     * Upload images for an article.
     */
    public function store(Request $request, int $articleId): JsonResponse
    {
        $article = Article::find($articleId);

        if (!$article) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'required|image|mimes:jpeg,png,gif,webp|max:5120', // 5MB
        ]);

        $uploadedImages = [];
        $currentOrder = $article->images()->max('order') ?? 0;

        foreach ($request->file('images') as $file) {
            $currentOrder++;
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('article-images/' . $articleId, $filename, 'public');

            $image = ArticleImage::create([
                'article_id' => $articleId,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'order' => $currentOrder,
            ]);

            $uploadedImages[] = [
                'id' => $image->id,
                'filename' => $image->filename,
                'original_name' => $image->original_name,
                'url' => $image->url,
                'size' => $image->human_size,
                'order' => $image->order,
            ];
        }

        return $this->success([
            'message' => __('messages.images_uploaded'),
            'images' => $uploadedImages,
        ], 201);
    }

    /**
     * Delete an image.
     */
    public function destroy(int $imageId): JsonResponse
    {
        $image = ArticleImage::find($imageId);

        if (!$image) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($image->path)) {
            Storage::disk('public')->delete($image->path);
        }

        $image->delete();

        return $this->success(['message' => __('messages.deleted')]);
    }

    /**
     * Reorder images.
     */
    public function reorder(Request $request, int $articleId): JsonResponse
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:article_images,id',
        ]);

        foreach ($request->order as $index => $imageId) {
            ArticleImage::where('id', $imageId)
                ->where('article_id', $articleId)
                ->update(['order' => $index + 1]);
        }

        return $this->success(['message' => __('messages.updated')]);
    }
}

