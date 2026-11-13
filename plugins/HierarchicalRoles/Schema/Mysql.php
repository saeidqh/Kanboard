<?php

namespace Kanboard\Plugin\HierarchicalRoles\Schema;

use PDO;

const VERSION = 2;

function version_1(PDO $pdo)
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plugin_hierarchical_roles (
            id INT NOT NULL AUTO_INCREMENT,
            project_id INT NOT NULL,
            title VARCHAR(150) NOT NULL,
            parent_id INT DEFAULT NULL,
            level TINYINT NOT NULL DEFAULT 1,
            description TEXT DEFAULT NULL,
            position INT NOT NULL DEFAULT 0,
            created_at INT NOT NULL DEFAULT 0,
            updated_at INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_hr_project (project_id),
            KEY idx_hr_parent (parent_id),
            KEY idx_hr_project_parent (project_id, parent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plugin_hierarchical_role_users (
            id INT NOT NULL AUTO_INCREMENT,
            role_id INT NOT NULL,
            user_id INT NOT NULL,
            project_id INT NOT NULL,
            created_at INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_project_user (project_id, user_id),
            KEY idx_hru_role (role_id),
            KEY idx_hru_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plugin_access_modules (
            id INT NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(80) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        INSERT IGNORE INTO plugin_access_modules (id, name, slug, description)
        VALUES (1, 'Organizational Structure', 'org_structure', 'Manage hierarchical roles and user assignments')
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plugin_role_access (
            id INT NOT NULL AUTO_INCREMENT,
            role_id INT NOT NULL,
            module_id INT NOT NULL,
            project_id INT NOT NULL,
            created_at INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_role_module_project (role_id, module_id, project_id),
            KEY idx_ra_role (role_id),
            KEY idx_ra_module (module_id),
            KEY idx_ra_project (project_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function version_2(PDO $pdo)
{
    // Extra modules for expanded access control
    $pdo->exec("
        INSERT IGNORE INTO plugin_access_modules (id, name, slug, description) VALUES
        (2, 'Project Settings', 'project_settings', 'Access to project settings'),
        (3, 'Analytics / Charts', 'analytics', 'Burndown, CFD, Sunburst and other analytics'),
        (4, 'Board Management', 'board_management', 'Edit columns, swimlanes, automatic actions'),
        (5, 'Members & Permissions', 'members', 'Manage project members and permissions'),
        (6, 'Categories', 'categories', 'Manage task categories')
    ");
}
