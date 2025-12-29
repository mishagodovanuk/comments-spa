<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentIndexRequest;
use App\Http\Requests\CommentPreviewRequest;
use App\Http\Requests\CommentSearchRequest;
use App\Http\Requests\CommentStoreRequest;
use App\Http\Resources\CommentCreatedResource;
use App\Http\Resources\CommentListResource;
use App\Services\Captcha\TextCaptcha;
use App\Services\Comment\CommentListService;
use App\Services\Comment\CommentSearchService;
use App\Services\Comment\CommentService;
use Illuminate\Http\JsonResponse;

/**
 * CommentController.
 */
final class CommentController extends Controller
{
    /**
     * @param CommentService $service
     * @param CommentListService $list
     * @param CommentSearchService $search
     * @param TextCaptcha $captcha
     */
    public function __construct(
        private readonly CommentService $service,
        private readonly CommentListService $list,
        private readonly CommentSearchService $search,
        private readonly TextCaptcha $captcha,
    ) {}

    /**
     * Comment list.
     *
     * @param CommentIndexRequest $request
     * @return JsonResponse
     */
    public function index(CommentIndexRequest $request): JsonResponse
    {
        $result = $this->list->list(page: $request->page(), sort: $request->sort(), direction: $request->direction());

        return response()->json(CommentListResource::make($result));
    }

    /**
     * Comment store.
     *
     * @param CommentStoreRequest $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function store(CommentStoreRequest $request): JsonResponse
    {
        $comment = $this->service->create($request->validated(), $request);

        return response()->json(CommentCreatedResource::make($comment), 201);
    }

    /**
     * Comment preview.
     *
     * @param CommentPreviewRequest $request
     * @return JsonResponse
     */
    public function preview(CommentPreviewRequest $request): JsonResponse
    {
        return response()->json([
            'html' => $this->service->preview((string) $request->input('text')),
        ]);
    }

    /**
     * Captcha.
     *
     * @return JsonResponse
     */
    public function captcha(): JsonResponse
    {
        return response()->json($this->captcha->issue());
    }

    /**
     * Comment search.
     *
     * @param CommentSearchRequest $request
     * @return JsonResponse
     */
    public function search(CommentSearchRequest $request): JsonResponse
    {
        return response()->json(
            $this->search->search(
                q: $request->q(),
                page: $request->page(),
                perPage: $request->perPage(),
            )
        );
    }
}
