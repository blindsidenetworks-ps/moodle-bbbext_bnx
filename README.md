# BigBlueButton BN Experience

BigBlueButton BN Experience is a foundational UX enhancement extension for BigBlueButton in Moodle — featuring moderator approval workflows and shared capabilities that power the entire BNX extension family. Developed and supported by Blindside Networks, the creators of BigBlueButton.

## Description

This plugin extends the BigBlueButton activity module with foundational UX improvements and shared behaviours that can be reused by other `bbbext_bnx_*` sidecar plugins.

**Note:** This plugin is the parent for other `bbbext_bnx_*` extensions and must be enabled for those plugins to function.

## Features

- **Waiting Room (moderator approval before join)**: Replaces the built-in "Wait for Moderator" screen with a Waiting Room lobby. Participants wait until a moderator approves their entry — configurable site-wide with per-activity override for teachers.
- **Enhanced recording experience**: Replaces the core recordings table with a fully functional implementation including search, sorting, pagination, editable recording name and description, and recording import. Driven by dedicated AJAX web services.
- **Navigation label override**: Replaces the default BigBlueButton activity navigation label with "BigBlueButton +" to surface the enhanced experience to users.
- **Sidecar state management**: When BNX is disabled, all dependent `bbbext_bnx_*` sidecars are automatically disabled and their previous state is snapshotted so they are restored when BNX is re-enabled.
- **Backup and restore**: Full backup and restore support for per-activity settings.
- **GDPR Compliant**: No personal user data is stored (null privacy provider).

## Requirements

- Moodle 5.1 or later
- BigBlueButton plugin (`mod_bigbluebuttonbn`)

## Installation

1. Copy the plugin to `mod/bigbluebuttonbn/extension/bnx/`
2. Visit Site Administration > Notifications to complete installation
3. Configure settings at Site Administration > Plugins > Activity modules > BigBlueButton > BigBlueButton BN Experience

## Configuration

### Admin Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Waiting Room enabled by default | Default on/off state for the Waiting Room across all activities | Enabled |
| Allow teachers to change the Waiting Room setting per activity | Allow teachers to override the Waiting Room on or off for individual activities | Enabled |

### Per-Activity Settings

Teachers can configure this on the BigBlueButton activity form under **Room Settings +**:

- **Moderator approval required to join session**

When enabled, the plugin sets:
- `guestPolicy=ASK_MODERATOR` for `create`
- `guest=true` for `join`

## Architecture

### Key Classes

| Class | Purpose |
|-------|---------|
| `action_url_addons` | Injects guest policy parameters into BBB API calls |
| `mod_form_addons` | Adds the Waiting Room checkbox and per-activity fields to the activity form |
| `mod_instance_helper` | Handles per-activity settings storage |
| `action_url_parameters` | Computes create/join parameters based on settings |
| `view_page_addons` | Overrides the BBB view page to embed the enhanced recordings experience |
| `page_context_builder` | Builds the full template context for the view page |
| `hook_callbacks` | Injects the navigation label override on BigBlueButton module pages |
| `sidecar_state_manager` | Manages enable/disable state of dependent BNX sidecars |
| `recording_helper` | Helpers for recording data retrieval and formatting |
| `joinurl_helper` | Builds custom join URLs |
| `sidecar_helper` | Utilities shared across BNX sidecar plugins |

### Web Services

| Service | Description |
|---------|-------------|
| `bbbext_bnx_get_meeting_info` | Returns meeting info with BNX extensions |
| `bbbext_bnx_get_recordings` | Returns recordings list for the enhanced recordings table |
| `bbbext_bnx_get_recordings_to_import` | Returns recordings available to import |

### API Integration

The plugin uses the `action_url_addons` hook to append parameters:

```php
// create
['guestPolicy' => 'ASK_MODERATOR']

// join
['guest' => 'true']
```

### Settings Resolution

1. If the Waiting Room setting is editable, the per-activity value is used.
2. Otherwise, the site-wide admin default is used.

## Privacy

This plugin **does not store any personal data**. It only stores configuration values and per-activity feature toggles.

## Version History

- **1.0** (March 11, 2026) — First stable open-source release. See [RELEASENOTES](RELEASENOTES) for full history.

## Credits

**Author**: Jesus Federico, Shamiso Jaravaza  
**Copyright**: 2026 onwards, Blindside Networks Inc  
**License**: GNU GPL v3 or later

## Related Plugins

- **bbbext_bnx_insights**: Sends student insights from Moodle directly into the BigBlueButton live session, per student.
- **bbbext_bnx_datahub**: Integrates with Moodle report builder to provide advanced reporting and analytics.
- **bbbext_bnx_preuploads**: Allows multiple preuploaded presentations for meeting content.
- **bbbext_bnx_earlyaccess**: Allows teachers to join the room for testing before the activity starts.
- **bbbext_bnx_locksettings**: Extends join settings to align with the lock controls offered by the BigBlueButton API.
- **mod_bigbluebuttonbn**: Core BigBlueButton activity module
