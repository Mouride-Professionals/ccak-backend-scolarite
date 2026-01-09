<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Notification\StoreAnnouncementRequest;
use App\Http\Requests\Notification\UpdateAnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AnnouncementController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:announcements.view')->only(['index', 'show']);
        $this->middleware('permission:announcements.create')->only('store');
        $this->middleware('permission:announcements.update')->only(['update', 'publish']);
        $this->middleware('permission:announcements.delete')->only('destroy');
    }

    /**
     * NOT-012: Get announcements
     */
    public function index(Request $request): JsonResponse
    {
        $announcements = QueryBuilder::for(Announcement::query())
            ->with('creator:id,name,email')
            ->active()
            ->forUser($request->user())
            ->notDismissedBy($request->user()->id)
            ->allowedFilters([
                AllowedFilter::exact('priority'),
                AllowedFilter::exact('target_audience'),
                AllowedFilter::exact('is_draft'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['priority', 'created_at'])
            ->defaultSort(['-priority', '-created_at'])
            ->paginate($request->get('per_page', 10))
            ->appends($request->query());

        return $this->success(
            AnnouncementResource::collection($announcements),
            'Annonces récupérées avec succès'
        );
    }

    /**
     * NOT-011: Create announcement (admin only)
     */
    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $announcement = DB::transaction(function () use ($request) {
            return Announcement::create([
                'creator_id' => $request->user()->id,
                'title' => $request->title,
                'content' => $request->content,
                'priority' => $request->priority ?? 'medium',
                'target_audience' => $request->target_audience,
                'publish_at' => $request->publish_at,
                'expire_at' => $request->expire_at,
                'is_draft' => $request->is_draft ?? true,
            ]);
        });

        return $this->success(
            new AnnouncementResource($announcement->load('creator')),
            'Annonce créée avec succès',
            201
        );
    }

    /**
     * Get single announcement
     */
    public function show(string $id): JsonResponse
    {
        $announcement = Announcement::with('creator')->findOrFail($id);

        return $this->success(new AnnouncementResource($announcement));
    }

    /**
     * Update announcement (admin only)
     */
    public function update(UpdateAnnouncementRequest $request, string $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        DB::transaction(fn() => $announcement->update($request->validated()));

        return $this->success(
            new AnnouncementResource($announcement->fresh('creator')),
            'Annonce mise à jour'
        );
    }

    /**
     * Delete announcement (admin only)
     */
    public function destroy(string $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);
        DB::transaction(fn() => $announcement->delete());

        return $this->success(null, 'Annonce supprimée');
    }

    /**
     * Dismiss announcement for current user
     */
    public function dismiss(string $id, Request $request): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);
        DB::transaction(fn() => $announcement->dismissFor($request->user()->id));

        return $this->success(null, 'Annonce masquée');
    }

    /**
     * Publish announcement (admin only)
     */
    public function publish(string $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);
        DB::transaction(fn() => $announcement->publish());

        return $this->success(
            new AnnouncementResource($announcement->fresh()),
            'Annonce publiée'
        );
    }
}
