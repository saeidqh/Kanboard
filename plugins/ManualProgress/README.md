# Manual Progress (Kanboard)

Self-reported task progress percentage.

## Behaviour
- Expert enters **0–100%** (sidebar, task edit form, or board prompt).
- Moving task to a **Done** column (or closing the task) sets progress to **100%**.
- Moving **out of Done** to any other column clears progress and **asks for a new percentage**.
- Progress bar is always **green**; only the number changes.

## Install
1. **Disable/remove** `AutoProgressRollup` (same bar slot / metadata conflict).
2. Copy `ManualProgress` into `plugins/`.
3. Settings → Plugins → install if needed; refresh board.

## Data storage
Uses **task metadata** (no DB migration):
- `manual_progress` — integer 0–100 as string
- `needs_progress_prompt` — `1` when a new value is required after leaving Done

No SQL required. Optional later: a real column is not needed.

## Done column detection
Column title contains (case-insensitive): done, closed, complete, تمام، انجام، تکمیل، بسته، پایان  
Also: closing a task forces 100%.
