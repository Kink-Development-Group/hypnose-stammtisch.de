<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Models\KnownEventSeries;
use HypnoseStammtisch\Utils\Response;

/**
 * Public endpoint for the "Bekannte Event-Reihen" section on the home page.
 *
 * Returns published, active series only — drafts and archived rows never leave
 * the admin area.
 */
class KnownEventSeriesController
{
    /**
     * GET /api/known-event-series
     */
    public function index(): void
    {
        try {
            $series = KnownEventSeries::getAllPublished();

            // Batched on purpose: this endpoint is hit on every home page view,
            // and resolving each card on its own would cost two queries per
            // linked series.
            $payload = KnownEventSeries::toPublicPayload($series);

            Response::success($payload, 'Known event series retrieved successfully');
        } catch (\Exception $e) {
            error_log('Error fetching public known event series: ' . $e->getMessage());
            Response::error('Failed to fetch known event series', 500);
        }
    }
}
