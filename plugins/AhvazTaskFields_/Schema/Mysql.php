<?php

namespace Kanboard\Plugin\AhvazTaskFields\Schema;

use PDO;

const VERSION = 2;

function version_1(PDO $pdo)
{
    try {
        $pdo->exec("
            ALTER TABLE `tasks`
            ADD COLUMN `definition_of_done` TEXT NULL
            COMMENT 'Definition of Done'
            AFTER `description`
        ");
    } catch (\Exception $e) {
        // already exists
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `plugin_departments` (
            `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `project_id`    INT UNSIGNED NOT NULL,
            `name`          VARCHAR(255) NOT NULL,
            `position`      INT UNSIGNED NOT NULL DEFAULT 0,
            `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
            `created_at`    INT UNSIGNED DEFAULT NULL,
            `updated_at`    INT UNSIGNED DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_project_id` (`project_id`),
            UNIQUE KEY `uniq_project_name` (`project_id`, `name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    try {
        $pdo->exec("
            INSERT IGNORE INTO `plugin_access_modules` (`name`, `slug`, `description`)
            VALUES ('Departments', 'departments', 'Manage project departments for Unplanned Activity tasks')
        ");
    } catch (\Exception $e) {
    }
}

function version_2(PDO $pdo)
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `plugin_form_fields` (
            `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `project_id`    INT UNSIGNED DEFAULT NULL COMMENT 'NULL = global default',
            `form_key`      VARCHAR(64)  NOT NULL COMMENT 'e.g. task_create',
            `field_key`     VARCHAR(64)  NOT NULL COMMENT 'e.g. complexity',
            `label`         VARCHAR(255) NOT NULL DEFAULT '',
            `is_visible`    TINYINT(1)   NOT NULL DEFAULT 1,
            `is_required`   TINYINT(1)   NOT NULL DEFAULT 0,
            `position`      INT UNSIGNED NOT NULL DEFAULT 0,
            `created_at`    INT UNSIGNED DEFAULT NULL,
            `updated_at`    INT UNSIGNED DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_form_field` (`project_id`, `form_key`, `field_key`),
            KEY `idx_form` (`form_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Seed default task form fields (global)
    $now = time();
    $seeds = array(
        array('task_create', 'title',              'Title',              1, 1, 10),
        array('task_create', 'description',        'Description',        1, 0, 20),
        array('task_create', 'definition_of_done', 'Definition of Done', 1, 1, 30),
        array('task_create', 'category_id',        'Category',           1, 0, 40),
        array('task_create', 'reference',          'Reference',          1, 0, 50),
        array('task_create', 'assignee',           'Assignee',           1, 0, 60),
        array('task_create', 'priority',           'Priority',           1, 0, 70),
        array('task_create', 'color_id',           'Color',              1, 0, 80),
        array('task_create', 'column_id',          'Column',             1, 0, 90),
        array('task_create', 'complexity',         'Complexity',         0, 0, 100),  // HIDDEN by default
        array('task_create', 'time_estimated',     'Original estimate',  1, 0, 110),
        array('task_create', 'time_spent',         'Time spent',         1, 0, 120),
        array('task_create', 'date_due',           'Due Date',           1, 0, 130),
        array('task_create', 'date_started',       'Start Date',         1, 0, 140),
        array('task_create', 'score',              'Score',              1, 0, 150),
        array('task_create', 'tags',               'Tags',               1, 0, 160),
    );

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO `plugin_form_fields`
            (`project_id`, `form_key`, `field_key`, `label`, `is_visible`, `is_required`, `position`, `created_at`, `updated_at`)
        VALUES
            (NULL, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($seeds as $s) {
        $stmt->execute(array($s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $now, $now));
    }
}
