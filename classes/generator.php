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

use core_ai\aiactions\generate_text;
use core_ai\manager;

/**
 * Sends text generation requests through the Moodle AI subsystem.
 *
 * All checks that decide whether a person may use AI here live in this class:
 * placement enabled, capability, action enabled for the placement, an enabled
 * provider, the course level AI setting and the AI usage policy.
 *
 * @package    aiplacement_aiauthoring
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generator {
    /** @var string Frankenstyle name of this placement. */
    public const COMPONENT = 'aiplacement_aiauthoring';

    /** @var string Capability needed to generate text. */
    public const CAPABILITY = 'aiplacement/aiauthoring:generate_text';

    /**
     * Whether the site administrator has enabled this placement.
     *
     * @return bool
     */
    public static function is_placement_enabled(): bool {
        $pluginclass = \core_plugin_manager::resolve_plugininfo_class('aiplacement');
        return $pluginclass::is_plugin_enabled('aiauthoring');
    }

    /**
     * Whether the current user can generate text in the context.
     *
     * @param \context $context The course context.
     * @param bool $checkcontext Whether to check the course level AI settings.
     * @return bool
     */
    public static function is_available(\context $context, bool $checkcontext = true): bool {
        return self::get_unavailable_reason($context, $checkcontext) === null;
    }

    /**
     * Get the reason why text generation is not available, or null if it is.
     *
     * The reason is a language string key of this component.
     *
     * @param \context $context The course context.
     * @param bool $checkcontext Whether to check the course level AI settings.
     * @return string|null
     */
    public static function get_unavailable_reason(\context $context, bool $checkcontext = true): ?string {
        if (!self::is_placement_enabled()) {
            return 'unavailable:placementdisabled';
        }
        if (!has_capability(self::CAPABILITY, $context)) {
            return 'unavailable:nocapability';
        }
        $manager = \core\di::get(manager::class);
        if (!$manager->is_action_enabled(self::COMPONENT, generate_text::class)) {
            return 'unavailable:actiondisabled';
        }
        if (!$manager->is_action_available(generate_text::class)) {
            return 'unavailable:noprovider';
        }
        if ($checkcontext && !$manager->is_action_enabled_in_context($context, generate_text::class)) {
            return 'unavailable:disabledincourse';
        }
        return null;
    }

    /**
     * Whether a user has accepted the AI usage policy of the site.
     *
     * @param int $userid The user id.
     * @return bool
     */
    public static function has_accepted_policy(int $userid): bool {
        return (bool) manager::get_user_policy_status($userid);
    }

    /**
     * Record that the current user accepts the AI usage policy.
     *
     * @param \context $context The context in which the policy was shown.
     * @return bool True if the acceptance was stored.
     */
    public static function accept_policy_for_current_user(\context $context): bool {
        global $USER;
        require_capability('moodle/ai:acceptpolicy', \context_user::instance($USER->id));
        return manager::user_policy_accepted($USER->id, $context->id);
    }

    /**
     * Generate text for the current user.
     *
     * Throws if the user may not use AI here. A failure of the AI provider is
     * returned in the result so that the page can show the message.
     *
     * @param \context $context The course context.
     * @param string $prompttext The complete prompt.
     * @return generation_result
     * @throws \moodle_exception If generation is not available or the policy is not accepted.
     */
    public static function generate_text(\context $context, string $prompttext): generation_result {
        global $USER;

        $reason = self::get_unavailable_reason($context);
        if ($reason !== null) {
            throw new \moodle_exception($reason, self::COMPONENT);
        }
        if (!self::has_accepted_policy($USER->id)) {
            throw new \moodle_exception('error:policynotaccepted', self::COMPONENT);
        }

        $action = new generate_text(
            contextid: $context->id,
            userid: $USER->id,
            prompttext: $prompttext,
        );
        $response = \core\di::get(manager::class)->process_action($action);
        $data = $response->get_response_data();

        return new generation_result(
            success: $response->get_success(),
            content: (string) ($data['generatedcontent'] ?? ''),
            finishreason: (string) ($data['finishreason'] ?? ''),
            errormessage: $response->get_errormessage(),
        );
    }
}
