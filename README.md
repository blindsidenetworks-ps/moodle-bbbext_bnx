# BigBlueButton BN Experience

A Moodle BigBlueButton extension plugin that extends the core `mod_bigbluebuttonbn` experience with a growing set of UX enhancements and shared capabilities used by the BNX extension family.

## Description

This plugin extends the BigBlueButton activity module with foundational UX improvements and shared behaviors that can be reused by other `bbbext_*` sidecar plugins. It includes configurable options (such as moderator approval before join) today, and is designed to grow as new core UX enhancements are added.

**Note:** This plugin is the parent for other `bbbext_bnx_*` extensions and must be enabled for those plugins to function.

## Features

- **Moderator approval before join**: Enable/disable guest approval at the activity level
- **Admin defaults**: Configure default state and editability for the feature
- **Per-activity overrides**: Teachers can override defaults when allowed
- **API integration**: Injects `guestPolicy=ASK_MODERATOR` on create and `guest=true` on join
- **GDPR Compliant**: No personal user data is stored (null privacy provider)

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
| Default moderator approval state | Default on/off state for approval before join | Enabled |
| Allow users to change default | Allow teachers to override in activity settings | Enabled |

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
| `mod_form_addons` | Adds approval checkbox to the activity form |
| `mod_instance_helper` | Handles per-activity settings storage |
| `action_url_parameters` | Computes create/join parameters based on settings |

### API Integration

The plugin uses the `action_url_addons` hook to append parameters:

```php
// create
['guestPolicy' => 'ASK_MODERATOR']

// join
['guest' => 'true']
```

### Settings Resolution

1. If the feature is editable, the per-activity value is used.
2. Otherwise, the admin default is used.

## Privacy

This plugin **does not store any personal data**. It only stores configuration values and per-activity feature toggles.

## Version History

- **0.1.0-alpha.3** (2026-01-20)
  - Initial alpha release with approval-before-join feature

## Credits

**Author**: Jesus Federico, Shamiso Jaravaza  
**Copyright**: 2025 onwards, Blindside Networks Inc  
**License**: GNU GPL v3 or later

## Related Plugins

- **bbbext_bnx_insights**: Student insights and alerts
- **bbbext_bnx_adoption**: Feature adoption tracking
- **bbbext_bnx_locksettings**: Lock settings controls
- **bbbext_bnx_datahub**: Data pipeline and analytics integrations
- **bbbext_bnx_preuploads**: Pre-upload workflows for meeting content
- **mod_bigbluebuttonbn**: Core BigBlueButton activity module
