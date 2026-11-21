<?php

namespace Kanboard\Plugin\RequestPlugin\Schema;

const VERSION = 1;

function version_1($pdo)
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS requests (
            id SERIAL PRIMARY KEY,
            reference VARCHAR(32) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            requester_id INTEGER NOT NULL,
            assignee_id INTEGER NOT NULL,
            project_id INTEGER NOT NULL,
            category VARCHAR(64) DEFAULT 'general',
            priority SMALLINT DEFAULT 2,
            status VARCHAR(32) NOT NULL DEFAULT 'submitted',
            due_date DATE NULL,
            needed_by DATE NULL,
            acceptance_note TEXT NULL,
            rejection_reason TEXT NULL,
            linked_task_id INTEGER NULL,
            converted_by INTEGER NULL,
            converted_at TIMESTAMP NULL,
            current_holder_id INTEGER NOT NULL,
            created_at TIMESTAMP NOT NULL,
            updated_at TIMESTAMP NOT NULL,
            submitted_at TIMESTAMP NULL,
            closed_at TIMESTAMP NULL
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_requests_assignee ON requests(assignee_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_requests_requester ON requests(requester_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_requests_holder ON requests(current_holder_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_requests_status ON requests(status)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS request_comments (
            id SERIAL PRIMARY KEY,
            request_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_request_comments_request ON request_comments(request_id)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS request_history (
            id SERIAL PRIMARY KEY,
            request_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            action VARCHAR(64) NOT NULL,
            from_user_id INTEGER NULL,
            to_user_id INTEGER NULL,
            note TEXT NULL,
            created_at TIMESTAMP NOT NULL
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_request_history_request ON request_history(request_id)");
}
