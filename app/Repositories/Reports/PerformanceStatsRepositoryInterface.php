<?php

namespace App\Repositories\Reports;

interface PerformanceStatsRepositoryInterface
{
    public function countCreated(array $filters): int;

    public function countResolved(array $filters): int;

    public function countClosed(array $filters): int;

    public function countOpen(array $filters): int;

    public function avgResolutionMinutes(array $filters): ?float;

    public function slaMetCount(array $filters): int;

    public function slaBreachedCount(array $filters): int;

    public function groupByStatus(array $filters): array;

    public function groupByPriority(array $filters): array;

    public function groupByDepartment(array $filters): array;

    public function groupByRegion(array $filters): array;

    public function groupByCategory(array $filters): array;

    public function createdPerDay(array $filters): array;

    public function resolvedPerDay(array $filters): array;
}
