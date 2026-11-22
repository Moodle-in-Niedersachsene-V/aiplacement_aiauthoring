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

/**
 * AI placement for the AI authoring workshop (local_aiauthoring).
 *
 * The placement is the only way the authoring workshop talks to the Moodle AI
 * subsystem. Site administrators enable or disable it under AI placements.
 *
 * @package    aiplacement_aiauthoring
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class placement extends \core_ai\placement {
    /**
     * Get the actions that this placement supports.
     *
     * @return array An array of action class names.
     */
    public static function get_action_list(): array {
        return [
            \core_ai\aiactions\generate_text::class,
        ];
    }

    /**
     * The authoring workshop works in course context only.
     *
     * @param \context $context The context to check.
     * @return bool Whether the placement is available in the context.
     */
    #[\Override]
    public static function is_available_in_context(\context $context): bool {
        return $context->contextlevel == CONTEXT_COURSE;
    }

    /**
     * Get the actions of this placement that are available in a context.
     *
     * @param \context $context The context.
     * @param bool $checkcontext Whether to check the course level AI settings.
     * @return array The available actions.
     */
    #[\Override]
    public static function get_actions_available(\context $context, bool $checkcontext = true): array {
        if (!self::is_available_in_context($context) || !generator::is_available($context, $checkcontext)) {
            return [];
        }
        return [
            [
                'action' => 'generate_text',
                'buttontext' => get_string('action_generate_text', 'core_ai'),
                'title' => get_string('action_generate_text_desc', 'core_ai'),
            ],
        ];
    }
}
