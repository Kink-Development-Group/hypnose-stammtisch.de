<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Controllers;

use HypnoseStammtisch\Controllers\AdminKnownEventSeriesController;
use HypnoseStammtisch\Middleware\AdminAuth;
use PHPUnit\Framework\TestCase;

/**
 * Authorization matrix and payload validation of the "Bekannte Event-Reihen"
 * management endpoints.
 */
class AdminKnownEventSeriesControllerTest extends TestCase
{
    /**
     * The section is editorial: head admins and admins decide what the home page
     * advertises, event managers and moderators do not.
     */
    public function testOnlyHeadAdminsAndAdminsMayManageTheSection(): void
    {
        $allowed = ['head', 'admin'];
        $denied = ['event_manager', 'moderator'];

        foreach ($allowed as $role) {
            $this->assertTrue(
                AdminAuth::userHasRole(['role' => $role], AdminAuth::EVENT_FULL_ACCESS_ROLES),
                "Role {$role} should be allowed to manage known event series"
            );
        }

        foreach ($denied as $role) {
            $this->assertFalse(
                AdminAuth::userHasRole(['role' => $role], AdminAuth::EVENT_FULL_ACCESS_ROLES),
                "Role {$role} must not be allowed to manage known event series"
            );
        }
    }

    /**
     * Event managers keep their event permissions — the new restriction is
     * deliberately narrower than event management, not a replacement for it.
     */
    public function testEventManagersKeepEventPermissions(): void
    {
        $eventManager = ['role' => 'event_manager'];

        $this->assertTrue(AdminAuth::userHasRole($eventManager, AdminAuth::EVENT_MANAGEMENT_ROLES));
        $this->assertFalse(AdminAuth::userHasRole($eventManager, AdminAuth::EVENT_FULL_ACCESS_ROLES));
    }

    public function testMissingUserIsRejected(): void
    {
        $this->assertFalse(AdminAuth::userHasRole(null, AdminAuth::EVENT_FULL_ACCESS_ROLES));
    }

    public function testValidPayloadProducesNoErrors(): void
    {
        $errors = AdminKnownEventSeriesController::collectValidationErrors([
            'title' => 'Hamburger Hypnose Munch',
            'location' => 'Hamburg • Club Catonium',
            'frequency' => 'Monatlich • Jeden 1. Freitag',
            'description' => 'Monatliche Treffen im Club Catonium.',
            'formats' => ['Hypnose 101', 'Koalabox'],
            'price' => '10€ Eintritt',
            'tags' => ['Alle Levels'],
            'detail_url' => '/events',
            'next_event_source' => 'none',
            'status' => 'published',
            'sort_order' => 1,
        ]);

        $this->assertSame([], $errors);
    }

    public function testCreateRequiresTheCoreFields(): void
    {
        $errors = AdminKnownEventSeriesController::collectValidationErrors([]);

        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('location', $errors);
        $this->assertArrayHasKey('frequency', $errors);
    }

    /**
     * A PUT that only flips the status must not be rejected for the fields it
     * did not send.
     */
    public function testPartialUpdateSkipsUntouchedRequiredFields(): void
    {
        $errors = AdminKnownEventSeriesController::collectValidationErrors(
            ['status' => 'published'],
            true
        );

        $this->assertSame([], $errors);
    }

    public function testTitleLengthIsBounded(): void
    {
        $tooShort = AdminKnownEventSeriesController::collectValidationErrors([
            'title' => 'ab',
            'location' => 'Hamburg',
            'frequency' => 'Monatlich',
        ]);
        $this->assertArrayHasKey('title', $tooShort);

        $tooLong = AdminKnownEventSeriesController::collectValidationErrors([
            'title' => str_repeat('a', 256),
            'location' => 'Hamburg',
            'frequency' => 'Monatlich',
        ]);
        $this->assertArrayHasKey('title', $tooLong);
    }

    public function testManualNextDateNeedsText(): void
    {
        $errors = AdminKnownEventSeriesController::collectValidationErrors([
            'title' => 'Eine Reihe',
            'location' => 'Hamburg',
            'frequency' => 'Monatlich',
            'next_event_source' => 'manual',
        ]);

        $this->assertArrayHasKey('next_event_text', $errors);
    }

    public function testManualNextDateAcceptsText(): void
    {
        $errors = AdminKnownEventSeriesController::collectValidationErrors([
            'title' => 'Eine Reihe',
            'location' => 'Hamburg',
            'frequency' => 'Monatlich',
            'next_event_source' => 'manual',
            'next_event_text' => 'Fr, 6. Sep 2026',
        ]);

        $this->assertSame([], $errors);
    }

    /**
     * A partial update may touch the text alone on a row that is already
     * `manual`, so its shape is checked whenever it is sent — not only when the
     * request also names the mode. The column stops at 255 characters.
     */
    public function testManualTextIsBoundedEvenWithoutTheSource(): void
    {
        $errors = AdminKnownEventSeriesController::collectValidationErrors(
            ['next_event_text' => str_repeat('a', 256)],
            true
        );

        $this->assertArrayHasKey('next_event_text', $errors);
    }

    public function testManualTextOfUsableLengthPassesOnItsOwn(): void
    {
        $errors = AdminKnownEventSeriesController::collectValidationErrors(
            ['next_event_text' => 'Fr, 6. Sep 2026'],
            true
        );

        $this->assertSame([], $errors);
    }

    public function testUnknownNextEventSourceIsRejected(): void
    {
        $errors = AdminKnownEventSeriesController::collectValidationErrors([
            'title' => 'Eine Reihe',
            'location' => 'Hamburg',
            'frequency' => 'Monatlich',
            'next_event_source' => 'somewhere-else',
        ]);

        $this->assertArrayHasKey('next_event_source', $errors);
    }

    public function testUnknownStatusIsRejected(): void
    {
        $errors = AdminKnownEventSeriesController::collectValidationErrors(
            ['status' => 'live'],
            true
        );

        $this->assertArrayHasKey('status', $errors);
    }

    /**
     * The detail button may point at an internal route or an external https URL,
     * but not at a javascript: payload.
     */
    public function testDetailUrlMustBeInternalPathOrHttpUrl(): void
    {
        $internal = AdminKnownEventSeriesController::collectValidationErrors(
            ['detail_url' => '/events'],
            true
        );
        $this->assertSame([], $internal);

        $external = AdminKnownEventSeriesController::collectValidationErrors(
            ['detail_url' => 'https://example.org/reihe'],
            true
        );
        $this->assertSame([], $external);

        $scripted = AdminKnownEventSeriesController::collectValidationErrors(
            ['detail_url' => 'javascript:alert(1)'],
            true
        );
        $this->assertArrayHasKey('detail_url', $scripted);
    }

    public function testListFieldsRejectNonStringEntries(): void
    {
        $errors = AdminKnownEventSeriesController::collectValidationErrors(
            ['tags' => ['ok', ['nested']]],
            true
        );

        $this->assertArrayHasKey('tags', $errors);
    }

    public function testListFieldsAreBounded(): void
    {
        $errors = AdminKnownEventSeriesController::collectValidationErrors(
            ['formats' => array_fill(0, 13, 'Format')],
            true
        );

        $this->assertArrayHasKey('formats', $errors);
    }

    public function testNegativeSortOrderIsRejected(): void
    {
        $errors = AdminKnownEventSeriesController::collectValidationErrors(
            ['sort_order' => -1],
            true
        );

        $this->assertArrayHasKey('sort_order', $errors);
    }
}
