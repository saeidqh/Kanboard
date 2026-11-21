<?php

namespace Kanboard\Plugin\ManualProgress;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;

class Plugin extends Base
{
    const META_PROGRESS = 'manual_progress';
    const META_PROMPT   = 'needs_progress_prompt';

    public function initialize()
    {
        // Board progress bar
        $this->template->hook->attach('template:board:task:footer', 'ManualProgress:board/progress_bar');

        // Task sidebar (detail page)
        $this->template->hook->attach('template:task:sidebar:information', 'ManualProgress:task/sidebar');

        // Visible bar on task detail summary
        $this->template->hook->attach('template:task:details:first-column', 'ManualProgress:task/details_bar');

        // NOTE: Progress is NOT on create/edit task form.
        // Putting manual_progress in that form makes Kanboard SQL-update tasks.manual_progress (column does not exist).
        // Use sidebar Save or board prompt only.

        // Assets
        $this->hook->on('template:layout:css', array('template' => 'plugins/ManualProgress/Assets/css/progress.css'));
        $this->hook->on('template:layout:js', array('template' => 'plugins/ManualProgress/Assets/js/progress.js'));

        // Routes
        $this->route->addRoute('/manual-progress/save/:task_id', 'ManualProgressController', 'save', 'ManualProgress');
        $this->route->addRoute('/manual-progress/prompt/:task_id', 'ManualProgressController', 'prompt', 'ManualProgress');

        // Events
        $events = array('task.move.column', 'task.close', 'task.open');
        foreach ($events as $eventName) {
            $this->container['dispatcher']->addListener($eventName, function ($event) use ($eventName) {
                $this->onTaskEvent($event, $eventName);
            });
        }

        // Persist progress to METADATA when task form is saved
        $this->container['dispatcher']->addListener('task.create', function ($event) {
            $this->persistFromRequest($event);
        });
        $this->container['dispatcher']->addListener('task.update', function ($event) {
            $this->persistFromRequest($event);
        });

        // CRITICAL: strip manual_progress from task row values so SQL does not
        // try to write a non-existent tasks.manual_progress column.
        $strip = function (array &$values, array &$errors = array()) {
            unset($values['manual_progress']);
        };
        $this->hook->on('controller:task-modification:form:after-validation', $strip);
        $this->hook->on('controller:task-creation:form:after-validation', $strip);
        $this->hook->on('controller:task:form:after-validation', $strip);
        // Some Kanboard versions / validators expose these:
        $this->hook->on('model:task:modification:form:after-validation', $strip);
        $this->hook->on('model:task:creation:form:after-validation', $strip);
    }

    protected function onTaskEvent($event, $eventName)
    {
        $taskId = 0;
        if (method_exists($event, 'getTaskId')) {
            $taskId = (int) $event->getTaskId();
        } elseif (method_exists($event, 'getTask')) {
            $task = $event->getTask();
            $taskId = isset($task['id']) ? (int) $task['id'] : 0;
        }
        if ($taskId <= 0) {
            return;
        }

        $task = $this->taskFinderModel->getById($taskId);
        if (empty($task)) {
            return;
        }

        if ($eventName === 'task.close') {
            $this->setProgress($taskId, 100);
            $this->clearPrompt($taskId);
            return;
        }

        if ($eventName === 'task.open') {
            // Reopened: ask for a new baseline percentage
            $this->requestProgressPrompt($taskId);
            return;
        }

        if ($eventName === 'task.move.column') {
            $column = $this->columnModel->getById($task['column_id']);
            if ($this->isDoneColumn($column, $task)) {
                $this->setProgress($taskId, 100);
                $this->clearPrompt($taskId);
                return;
            }

            // Left a done-like state toward another column → ask manager/user for %
            $srcColumnId = 0;
            if (method_exists($event, 'getValues')) {
                $values = $event->getValues();
                if (!empty($values['src_column_id'])) {
                    $srcColumnId = (int) $values['src_column_id'];
                }
            }
            // Fallback: if progress was 100 and now not on done column → prompt
            $current = $this->taskMetadataModel->get($taskId, self::META_PROGRESS, null);
            $srcCol = $srcColumnId ? $this->columnModel->getById($srcColumnId) : null;
            if ($this->isDoneColumn($srcCol, null) || (string) $current === '100') {
                $this->requestProgressPrompt($taskId);
            }
        }
    }

    protected function persistFromRequest($event)
    {
        $taskId = 0;
        if (method_exists($event, 'getTaskId')) {
            $taskId = (int) $event->getTaskId();
        } elseif (method_exists($event, 'getTask')) {
            $t = $event->getTask();
            $taskId = isset($t['id']) ? (int) $t['id'] : 0;
        }
        if ($taskId <= 0) {
            return;
        }

        $values = $this->request->getValues();
        if (!isset($values['manual_progress']) && !array_key_exists('manual_progress', $values)) {
            return;
        }
        $raw = $values['manual_progress'];
        if ($raw === '' || $raw === null) {
            return;
        }
        $pct = max(0, min(100, (int) $raw));
        $this->setProgress($taskId, $pct);
        $this->clearPrompt($taskId);
    }

    protected function isDoneColumn($column, $task)
    {
        if (!empty($task) && isset($task['is_active']) && (int) $task['is_active'] === 0) {
            return true;
        }
        if (empty($column) || empty($column['title'])) {
            return false;
        }
        $title = mb_strtolower(trim($column['title']), 'UTF-8');
        $doneNames = array('done', 'closed', 'complete', 'completed', 'تمام', 'انجام شد', 'انجام‌شده', 'تکمیل', 'بسته', 'پایان');
        foreach ($doneNames as $name) {
            if ($title === $name || mb_strpos($title, $name, 0, 'UTF-8') !== false) {
                return true;
            }
        }
        return false;
    }

    protected function setProgress($taskId, $pct)
    {
        $pct = (string) max(0, min(100, (int) $pct));
        $this->writeMeta($taskId, self::META_PROGRESS, $pct);
    }

    protected function writeMeta($taskId, $name, $value)
    {
        // Preferred API
        try {
            $this->taskMetadataModel->save($taskId, array($name => $value));
        } catch (Exception $e) {
            // continue to fallback
        }

        $read = $this->taskMetadataModel->get($taskId, $name, null);
        if ((string) $read === (string) $value) {
            return;
        }

        // Fallback: direct metadata table write (Kanboard standard table)
        try {
            $this->db->table('task_has_metadata')
                ->eq('task_id', $taskId)
                ->eq('name', $name)
                ->remove();
            $this->db->table('task_has_metadata')->insert(array(
                'task_id' => $taskId,
                'name'    => $name,
                'value'   => $value,
            ));
        } catch (Exception $e) {
            // last resort: ignore
        }
    }

    protected function requestProgressPrompt($taskId)
    {
        $this->writeMeta($taskId, self::META_PROMPT, '1');
        $this->writeMeta($taskId, self::META_PROGRESS, '');
    }

    protected function clearPrompt($taskId)
    {
        $this->writeMeta($taskId, self::META_PROMPT, '0');
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__ . '/Locale');
    }

    public function getApplicationAccess()
    {
        return array(
            'ManualProgressController' => 'app-user',
        );
    }

    public function getPluginName()
    {
        return 'Manual Progress';
    }

    public function getPluginDescription()
    {
        return 'Manual task progress percentage (self-reported). Done column = 100%. Leaving Done asks for a new percentage. Green progress bar only.';
    }

    public function getPluginAuthor()
    {
        return 'Custom';
    }

    public function getPluginVersion()
    {
        return '1.0.0';
    }

    public function getPluginHomepage()
    {
        return '';
    }

    public function getCompatibleVersion()
    {
        return '>=1.2.0';
    }
}
