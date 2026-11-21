# Request Plugin for Kanboard

Send formal **requests** to any member of any project. Separate from tasks: requests can go upward or sideways; tasks stay for subordinates / project work.

## Features

- **Inbox / Sent / All** views
- Create request: choose **project** → searchable member list → submit
- Recipient can **Accept**, **Reject**, **Forward**, **Comment**
- Requester or holder can **Convert to Task** (once only)
- Full **timeline** (who held it, what stage)
- Linked task indicator when converted
- Optional completion without converting to task

## Install

1. Copy the `RequestPlugin` folder into `plugins/` (folder name must be `RequestPlugin`).
2. Enable **Request** in **Settings → Plugins**.
3. Schema is created automatically on first load.
4. Open **Requests** from the sidebar or create via header menu **New Request**.

## Workflow

```
Create → Submitted → (Forward*) → Accept / Reject
                         ↓
              Convert to Task (optional, once)
                         ↓
                    Completed
```

\* Forward moves current holder to another project member.

## Database tables

- `requests`
- `request_comments`
- `request_history`

## Compatibility

Kanboard ≥ 1.2.0
