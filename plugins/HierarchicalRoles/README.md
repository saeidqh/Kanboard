# Hierarchical Roles Plugin for Kanboard

Organizational hierarchy for task assignment + role-based access to modules.

## Features

1. Hierarchical Roles (tree) – parent/child with automatic level
2. One role per user per project
3. Filtered Assignee dropdown – only self + subordinates
4. Module Access Control – grant roles access to specific modules
5. Modal forms for create/edit role (like task create)

## Modules (plugin_access_modules)

| ID | Slug                 | Name                      |
|----|----------------------|---------------------------|
| 1  | org_structure        | Organizational Structure  |
| 2  | project_settings      | Project Settings          |
| 3  | analytics            | Analytics / Charts         |
| 4  | board_management     | Board Management          |
| 5  | members              | Members & Permissions     |
| 6  | categories           | Categories                |

## How to check access in code

In a template:
```php
<?php if ($this->hierarchicalRoleHelper->canAccessModule($project['id'], 'analytics')): ?>
    <!-- show analytics link -->
<?php endif ?>
```

In a controller:
```php
if (!$this->helper->hierarchicalRoleHelper->canAccessModule($project['id'], 'project_settings')) {
    $this->flash->failure(t('You do not have access to this section.'));
    $this->response->redirect(...);
    return;
}
```

## Installation

1. Run install_hierarchical_roles.sql (or only upgrade_modules_v2.sql if tables already exist).
2. Copy HierarchicalRoles into kanboard/plugins/.
3. Enable plugin if needed.
4. Project menu → Organizational Structure.

## First-time setup

1. Create a Level 1 role (no parent).
2. Assign Users → assign yourself / manager to that role.
3. Module Access → tick modules for that role.
4. Test with a non-admin user.

## Author

Ahvaz005 Team – v1.2.0

## Analytics scope (v1.3)

- Level 1 / Admin → full project charts
- Other levels → only tasks assigned to self + subordinates
- Core: User repartition + Task distribution are scoped automatically
- Sunburst: see SUNBURST_INTEGRATION.md
