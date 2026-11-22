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
 * Result of a text generation request.
 *
 * The content comes from an external AI model and must be treated as
 * untrusted input by every caller.
 *
 * @package    aiplacement_aiauthoring
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class generation_result {
    /**
     * Create a result.
     *
     * @param bool $success Whether a provider returned text.
     * @param string $content The generated text (untrusted).
     * @param string $finishreason Why the model stopped, for example "stop" or "length".
     * @param string $errormessage Error message of the AI subsystem if the request failed.
     */
    public function __construct(
        /** @var bool Whether a provider returned text. */
        public readonly bool $success,
        /** @var string The generated text (untrusted). */
        public readonly string $content,
        /** @var string Why the model stopped. */
        public readonly string $finishreason,
        /** @var string Error message of the AI subsystem. */
        public readonly string $errormessage,
    ) {
    }

    /**
     * Whether the text was cut off because the length limit was reached.
     *
     * @return bool
     */
    public function is_truncated(): bool {
        return in_array($this->finishreason, ['length', 'max_tokens'], true);
    }
}
