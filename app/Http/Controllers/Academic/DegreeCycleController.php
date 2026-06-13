<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Resources\Academic\DegreeCycleResource;
use App\Models\DegreeCycle;

class DegreeCycleController extends BaseApiController
{
    public function index()
    {
        $cycles = DegreeCycle::with(['levels' => function ($query) {
            $query->where('is_active', true)->orderBy('numero');
        }])->orderBy('name')->get();

        return $this->success(DegreeCycleResource::collection($cycles));
    }
}
