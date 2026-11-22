<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace aiplacement_aiauthoring;

use core_ai\ai_test_trait;
use core_ai\aiactions\generate_text;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../../tests/ai_test_trait.php');

/**
 * Tests for the availability checks of the AI authoring workshop placement.
 *
 * @package    aiplacement_aiauthoring
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \aiplacement_aiauthoring\generator
 * @covers     \aiplacement_aiauthoring\placement
 */
final class generator_test extends \advanced_testcase {
    use ai_test_trait;

    /** @var \stdClass Test course. */
    private \stdClass $course;

    /** @var \context_course Course context. */
    private \context_course $context;

    /** @var \stdClass Editing teacher in the course. */
    private \stdClass $teacher;

    /** @var \stdClass Student in the course. */
    private \stdClass $student;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $this->context = \context_course::instance($this->course->id);
        $this->teacher = $generator->create_and_enrol($this->course, 'editingteacher');
        $this->student = $generator->create_and_enrol($this->course, 'student');
    }

    /**
     * The placement offers only the action generate_text and only in course context.
     */
    public function test_placement_offers_generate_text_in_course_context_only(): void {
        $this->assertSame([generate_text::class], placement::get_action_list());
        $this->assertTrue(placement::is_available_in_context($this->context));
        $this->assertFalse(placement::is_available_in_context(\context_system::instance()));
    }

    /**
     * A new installation keeps the placement disabled until an administrator enables it.
     */
    public function test_disabled_placement_is_reported(): void {
        $this->setUser($this->teacher);

        $this->assertSame('unavailable:placementdisabled', generator::get_unavailable_reason($this->context));
        $this->assertSame([], placement::get_actions_available($this->context));
    }

    /**
     * Students never get access, even when everything else is configured.
     */
    public function test_student_has_no_access(): void {
        set_config('enabled', 1, generator::COMPONENT);
        $this->create_ai_provider(['generate_text'], \aiprovider_openai\provider::class);
        $this->setUser($this->student);

        $this->assertSame('unavailable:nocapability', generator::get_unavailable_reason($this->context));
    }

    /**
     * Without an enabled provider the teacher is told that no provider exists.
     */
    public function test_missing_provider_is_reported(): void {
        set_config('enabled', 1, generator::COMPONENT);
        $this->setUser($this->teacher);

        $this->assertSame('unavailable:noprovider', generator::get_unavailable_reason($this->context));
    }

    /**
     * The administrator can switch off the action for this placement.
     */
    public function test_disabled_action_is_reported(): void {
        set_config('enabled', 1, generator::COMPONENT);
        set_config('generate_text', 0, generator::COMPONENT);
        $this->create_ai_provider(['generate_text'], \aiprovider_openai\provider::class);
        $this->setUser($this->teacher);

        $this->assertSame('unavailable:actiondisabled', generator::get_unavailable_reason($this->context));
    }

    /**
     * With placement, action and provider enabled, the teacher can generate text.
     */
    public function test_teacher_has_access_when_configured(): void {
        set_config('enabled', 1, generator::COMPONENT);
        $this->create_ai_provider(['generate_text'], \aiprovider_openai\provider::class);
        $this->setUser($this->teacher);

        $this->assertNull(generator::get_unavailable_reason($this->context));
        $actions = placement::get_actions_available($this->context);
        $this->assertCount(1, $actions);
        $this->assertSame('generate_text', $actions[0]['action']);
    }

    /**
     * Generating text is refused while the placement is disabled.
     */
    public function test_generate_text_refused_when_unavailable(): void {
        $this->setUser($this->teacher);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('unavailable:placementdisabled', generator::COMPONENT));
        generator::generate_text($this->context, 'Test');
    }

    /**
     * Generating text is refused until the teacher accepts the AI usage policy.
     */
    public function test_generate_text_refused_without_policy(): void {
        set_config('enabled', 1, generator::COMPONENT);
        $this->create_ai_provider(['generate_text'], \aiprovider_openai\provider::class);
        $this->setUser($this->teacher);
        $this->assertFalse(generator::has_accepted_policy($this->teacher->id));

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('error:policynotaccepted', generator::COMPONENT));
        generator::generate_text($this->context, 'Test');
    }

    /**
     * Accepting the policy is stored for the current user.
     */
    public function test_policy_acceptance_is_stored(): void {
        global $DB;
        $this->setUser($this->teacher);

        $this->assertTrue(generator::accept_policy_for_current_user($this->context));

        $this->assertTrue(generator::has_accepted_policy($this->teacher->id));
        $this->assertTrue($DB->record_exists('ai_policy_register', ['userid' => $this->teacher->id]));
    }

    /**
     * A result reports truncation for the finish reasons of the common providers.
     */
    public function test_result_detects_truncation(): void {
        $this->assertTrue((new generation_result(true, 'Text', 'length', ''))->is_truncated());
        $this->assertTrue((new generation_result(true, 'Text', 'max_tokens', ''))->is_truncated());
        $this->assertFalse((new generation_result(true, 'Text', 'stop', ''))->is_truncated());
    }
}
