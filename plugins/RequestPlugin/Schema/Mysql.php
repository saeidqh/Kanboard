<?php

namespace Kanboard\Plugin\RequestPlugin\Schema;

const VERSION = 1;

function version_1($pdo)
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS requests (
            id INT NOT NULL AUTO_INCREMENT,
            reference VARCHAR(32) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            requester_id INT NOT NULL,
            assignee_id INT NOT NULL,
            project_id INT NOT NULL,
            category VARCHAR(64) DEFAULT 'general',
            priority TINYINT DEFAULT 2,
            status VARCHAR(32) NOT NULL DEFAULT 'submitted',
            due_date DATE NULL,
            needed_by DATE NULL,
            acceptance_note TEXT NULL,
            rejection_reason TEXT NULL,
            linked_task_id INT NULL,
            converted_by INT NULL,
            converted_at DATETIME NULL,
            current_holder_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            submitted_at DATETIME NULL,
            closed_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_reference (reference),
            KEY idx_assignee (assignee_id),
            KEY idx_requester (requester_id),
            KEY idx_holder (current_holder_id),
            KEY idx_status (status),
            KEY idx_project (project_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS request_comments (
            id INT NOT NULL AUTO_INCREMENT,
            request_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_request (request_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS request_history (
            id INT NOT NULL AUTO_INCREMENT,
            request_id INT NOT NULL,
            user_id INT NOT NULL,
            action VARCHAR(64) NOT NULL,
            from_user_id INT NULL,
            to_user_id INT NULL,
            note TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_request (request_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
