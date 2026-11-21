<?php

namespace Kanboard\Plugin\ManualProgress\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Plugin\ManualProgress\Plugin;

class ManualProgressController extends BaseController
{
    public function save()
    {
        $task = $this->getTask();
        $values = $this->request->getValues();
        $pct = isset($values['manual_progress']) ? (int) $values['manual_progress'] : $this->request->getIntegerParam('manual_progress', -1);

        if ($pct < 0 || $pct > 100) {
            if ($this->request->isAjax()) {
                $this->response->json(array('ok' => false, 'message' => t('Progress must be between 0 and 100')), 400);
                return;
            }
            $this->flash->failure(t('Progress must be between 0 and 100'));
            $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array(
                'task_id' => $task['id'],
                'project_id' => $task['project_id'],
            )));
            return;
        }

        $pct = max(0, min(100, $pct));
        $this->writeMeta($task['id'], Plugin::META_PROGRESS, (string) $pct);
        $this->writeMeta($task['id'], Plugin::META_PROMPT, '0');

        // Log to TaskTimeline if that plugin is installed
        try {
            if (isset($this->timelineModel)) {
                $this->timelineModel->log(
                    $task['id'],
                    $task['project_id'],
                    'progress_change',
                    'Progress → ' . $pct . '%',
                    '',
                    array('progress' => $pct)
                );
            }
        } catch (Exception $e) {
        }

        if ($this->request->isAjax()) {
            $this->response->json(array(
                'ok' => true,
                'task_id' => (int) $task['id'],
                'progress' => $pct,
            ));
            return;
        }

        $this->flash->success(t('Progress updated'));
        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array(
            'task_id' => $task['id'],
            'project_id' => $task['project_id'],
        )));
    }

    public function prompt()
    {
        $task = $this->getTask();
        $this->response->html($this->template->render('ManualProgress:task/prompt', array(
            'task' => $task,
            'title' => t('Enter progress percentage'),
        )));
    }

    protected function writeMeta($taskId, $name, $value)
    {
        try {
            $this->taskMetadataModel->save($taskId, array($name => $value));
        } catch (Exception $e) {
        }

        $read = $this->taskMetadataModel->get($taskId, $name, null);
        if ((string) $read === (string) $value) {
            return;
        }

        try {
            $this->db->table('task_has_metadata')
                ->eq('task_id', $taskId)
                ->eq('name', $name)
                ->remove();
            $this->db->table('task_has_metadata')->insert(array(
                'task_id' => $taskId,
                'name' => $name,
                'value' => $value,
            ));
        } catch (Exception $e) {
        }
    }
}
