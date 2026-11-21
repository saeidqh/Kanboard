<?php
/**
 * Native Kanboard activity for this task — right sidebar.
 * Avoids non-existent ProjectActivityModel::getTask().
 */
$events = array();
$taskId = (int) $task['id'];

try {
    // 1) Helper API (varies by Kanboard version)
    if (isset($this->projectActivity) && method_exists($this->projectActivity, 'getTaskEvents')) {
        $events = $this->projectActivity->getTaskEvents($taskId);
    }
} catch (Exception $e) {
    $events = array();
}

if (empty($events)) {
    try {
        // 2) Direct query on project_activities (core table)
        $events = $this->task->db->table('project_activities')
            ->eq('task_id', $taskId)
            ->desc('date_creation')
            ->limit(100)
            ->findAll();
    } catch (Exception $e) {
        $events = array();
    }
}

if (empty($events)) {
    try {
        // 3) Via container model query builder if available
        if (method_exists($this->task->projectActivityModel, 'getQuery')) {
            $events = $this->task->projectActivityModel->getQuery()
                ->eq('task_id', $taskId)
                ->desc('date_creation')
                ->limit(100)
                ->findAll();
        }
    } catch (Exception $e) {
        $events = array();
    }
}

if (!is_array($events)) {
    $events = array();
}
?>
<aside class="tas-rail" id="tas-rail">
    <div class="tas-rail-inner">
        <div class="tas-header">
            <h3><?= t('Activity') ?></h3>
            <a class="tas-link" href="<?= $this->url->href('ActivityController', 'task', array('task_id' => $task['id'], 'project_id' => $task['project_id'])) ?>">
                <?= t('Full stream') ?>
            </a>
        </div>

        <?php if (empty($events)): ?>
            <p class="tas-empty"><?= t('No activity yet.') ?></p>
        <?php else: ?>
            <div class="tas-list">
                <?php foreach ($events as $event): ?>
                    <div class="tas-item">
                        <div class="tas-when">
                            <?php
                            $ts = 0;
                            if (!empty($event['date_creation'])) {
                                $ts = $event['date_creation'];
                            } elseif (!empty($event['date'])) {
                                $ts = $event['date'];
                            }
                            // date_creation may already be unix int
                            if (is_numeric($ts)) {
                                echo $this->dt->datetime((int) $ts);
                            } else {
                                echo $this->text->e((string) $ts);
                            }
                            ?>
                        </div>
                        <div class="tas-author">
                            <?php
                            $author = '';
                            if (!empty($event['author_name'])) {
                                $author = $event['author_name'];
                            } elseif (!empty($event['creator_name'])) {
                                $author = $event['creator_name'];
                            } elseif (!empty($event['name'])) {
                                $author = $event['name'];
                            } elseif (!empty($event['username'])) {
                                $author = $event['username'];
                            } elseif (!empty($event['author'])) {
                                $author = $event['author'];
                            }
                            echo $this->text->e($author);
                            ?>
                        </div>
                        <div class="tas-event">
                            <?php
                            if (!empty($event['event_content'])) {
                                echo $event['event_content'];
                            } elseif (!empty($event['content'])) {
                                // content is often serialized JSON in older KB — show safely
                                $c = $event['content'];
                                if (is_string($c) && strlen($c) && $c[0] === '{') {
                                    $decoded = json_decode($c, true);
                                    if (is_array($decoded)) {
                                        if (!empty($decoded['title'])) {
                                            echo $this->text->e($decoded['title']);
                                        } else {
                                            echo $this->text->e(isset($event['event_name']) ? $event['event_name'] : 'Activity');
                                        }
                                    } else {
                                        echo $this->text->e($c);
                                    }
                                } else {
                                    echo is_string($c) ? $c : $this->text->e((string) $c);
                                }
                            } elseif (!empty($event['event_title'])) {
                                echo $this->text->e($event['event_title']);
                            } elseif (!empty($event['title'])) {
                                echo $this->text->e($event['title']);
                            } else {
                                echo $this->text->e(isset($event['event_name']) ? $event['event_name'] : 'Activity');
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>
</aside>
