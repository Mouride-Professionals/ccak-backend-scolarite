<?php

namespace App\Observers;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function __construct(private AuditLogger $logger) {}

    public function created(Model $model): void
    {
        $this->logger->log('created', $model, null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->logger->log('updated', $model, $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->logger->log('deleted', $model, $model->getOriginal(), null);
    }
}
