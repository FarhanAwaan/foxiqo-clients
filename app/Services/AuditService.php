<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function log(string $action, Model $entity, array $oldValues = null): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'company_id' => $this->extractCompanyId($entity),
            'action' => $action,
            'entity_type' => get_class($entity),
            'entity_id' => $entity->id,
            'old_values' => $oldValues,
            'new_values' => $entity->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Extract company_id from various entity types
     */
    protected function extractCompanyId(Model $entity): ?int
    {
        // Direct company_id on the entity
        if (isset($entity->company_id)) {
            return $entity->company_id;
        }

        // If the entity is a Company itself
        if ($entity instanceof \App\Models\Company) {
            return $entity->id;
        }

        // If the entity has a company relationship loaded
        if (method_exists($entity, 'company') && $entity->relationLoaded('company') && $entity->company) {
            return $entity->company->id;
        }

        // Try to get company_id from related entities
        if (method_exists($entity, 'subscription') && $entity->relationLoaded('subscription') && $entity->subscription) {
            return $entity->subscription->company_id;
        }

        if (method_exists($entity, 'agent') && $entity->relationLoaded('agent') && $entity->agent) {
            return $entity->agent->company_id;
        }

        return null;
    }

    /**
     * Log an action without an entity (e.g., login/logout)
     */
    public function logAction(string $action, ?int $companyId = null, array $data = []): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'company_id' => $companyId ?? auth()->user()?->company_id,
            'action' => $action,
            'entity_type' => null,
            'entity_id' => null,
            'old_values' => null,
            'new_values' => !empty($data) ? $data : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
