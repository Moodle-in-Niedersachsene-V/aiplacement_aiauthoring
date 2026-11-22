# AI authoring workshop placement (aiplacement_aiauthoring)

AI placement for the **AI authoring workshop** (`local_aiauthoring`) of Moodle in Niedersachsen e. V.

The placement connects the authoring workshop to the Moodle AI subsystem (`core_ai`). It offers the
action **Generate text** and does nothing on its own. Teachers use it through `local_aiauthoring`.

## Why a separate placement

In the Moodle AI subsystem only placements talk to the AI manager; providers and placements never
know about each other. A placement also gives site administrators the usual switches under
*Site administration > General > AI > AI placements*. `local_aiauthoring` therefore never calls a
provider directly.

## Requirements

- Moodle 5.1 to 5.3
- PHP 8.2, 8.3 or 8.4
- MariaDB, MySQL or PostgreSQL
- At least one enabled AI provider with the action *Generate text*
  (for example Anthropic Claude from Moodle 5.3, OpenAI, Azure AI, Gemini, Amazon Bedrock)

## Installation and setup

1. Copy the folder `aiauthoring` to `ai/placement/` (Moodle 5.1 and later: `public/ai/placement/`).
2. Visit *Site administration > Notifications* to install.
3. *Site administration > General > AI > AI providers*: create and enable a provider instance with
   the action *Generate text*.
4. *Site administration > General > AI > AI placements*: enable **AI authoring workshop placement**
   and check that its action *Generate text* is enabled.
5. Install `local_aiauthoring`.

New placements are disabled after installation.

## Checks before every request

Text is only generated when all of the following are true:

- the placement is enabled,
- the user has `aiplacement/aiauthoring:generate_text` in the course
  (default: editing teacher and manager; students never),
- the action *Generate text* is enabled for this placement,
- at least one provider with this action is enabled,
- AI tools are not disabled in the course settings,
- the user has accepted the AI usage policy of the site.

## Capabilities

| Capability | Context | Default |
|---|---|---|
| `aiplacement/aiauthoring:generate_text` | Course | editingteacher, manager |

## Privacy

The placement stores no personal data. Requests, generated text and the acceptance of the AI usage
policy are stored by the Moodle AI subsystem (AI usage report, policy acceptance report).
Do not enter personal data of students in requests.

## Licence

GNU GPL v3 or later. Maintainer: Moodle in Niedersachsen e. V.
