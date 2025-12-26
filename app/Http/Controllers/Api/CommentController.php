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

final class CommentController extends Controller
{
    public function __construct(
        private readonly CommentService $service,
        private readonly CommentListService $list,
        private readonly CommentSearchService $search,
        private readonly TextCaptcha $captcha,
    ) {}

    public function index(CommentIndexRequest $request): JsonResponse
    {
        $result = $this->list->list(
            page: $request->page(),
            sort: $request->sort(),
            direction: $request->direction(),
        );

        return response()->json(new CommentListResource($result));
    }

    public function store(CommentStoreRequest $request): JsonResponse
    {
        $comment = $this->service->create($request->validated(), $request);

        return response()->json(new CommentCreatedResource($comment), 201);
    }

    public function preview(CommentPreviewRequest $request): JsonResponse
    {
        return response()->json([
            'html' => $this->service->preview($request->text()),
        ]);
    }

    public function captcha(): JsonResponse
    {
        return response()->json($this->captcha->issue());
    }

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
