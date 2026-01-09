<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ImageKit\ImageKit;
use Illuminate\Support\Str;

class AdminArticleImageController extends Controller
{
    private ?ImageKit $imageKit = null;

    /**
     * Get ImageKit instance (lazy loaded)
     */
    private function getImageKit(): ImageKit
    {
        if ($this->imageKit === null) {
            $this->imageKit = new ImageKit(
                env('IMAGEKIT_PUBLIC_KEY'),
                env('IMAGEKIT_PRIVATE_KEY'),
                env('IMAGEKIT_URL_ENDPOINT')
            );
        }
        return $this->imageKit;
    }

    /**
     * Get all images for an article.
     */
    public function index(int $articleId): JsonResponse
    {
        $article = Article::find($articleId);

        if (!$article) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $images = $article->images()->ordered()->get()->map(fn ($img) => [
            'id' => $img->id,
            'filename' => $img->filename,
            'original_name' => $img->original_name,
            'url' => $img->path,
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
            'images.*' => 'required|image|mimes:jpeg,png,gif,webp|max:5120',
        ]);

        $uploadedImages = [];
        $currentOrder = $article->images()->max('order') ?? 0;

        foreach ($request->file('images') as $file) {
            $currentOrder++;
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            try {
                // Upload to ImageKit
                $uploadResult = $this->getImageKit()->uploadFile([
                    'file' => base64_encode(file_get_contents($file->getRealPath())),
                    'fileName' => $fileName,
                    'folder' => '/mehnat/articles/' . $articleId,
                    'useUniqueFileName' => false,
                ]);

                if (!isset($uploadResult->result->url)) {
                    continue; // Skip failed uploads
                }

                $image = ArticleImage::create([
                    'article_id' => $articleId,
                    'filename' => $uploadResult->result->fileId,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $uploadResult->result->url,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'order' => $currentOrder,
                ]);

                $uploadedImages[] = [
                    'id' => $image->id,
                    'filename' => $image->filename,
                    'original_name' => $image->original_name,
                    'url' => $image->path,
                    'size' => $image->human_size,
                    'order' => $image->order,
                ];
            } catch (\Exception $e) {
                // Log error but continue with other images
                \Log::error('ImageKit upload failed: ' . $e->getMessage());
                continue;
            }
        }

        if (empty($uploadedImages)) {
            return $this->error(__('messages.upload_failed'), 'UPLOAD_FAILED', 500);
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

        // Delete from ImageKit
        if ($image->filename) {
            try {
                $this->getImageKit()->deleteFile($image->filename);
            } catch (\Exception $e) {
                // Log but continue - file might not exist in ImageKit
                \Log::warning('ImageKit delete failed: ' . $e->getMessage());
            }
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
