<?php

namespace Kanboard\Plugin\AhvazTaskFields\Subscriber;

use Kanboard\Event\GenericEvent;
use Kanboard\Model\TaskModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Kanboard\Core\Base;

class TaskSubscriber extends Base implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            TaskModel::EVENT_CREATE => 'afterCreate',
            TaskModel::EVENT_UPDATE => 'afterUpdate',
        );
    }

    public function afterCreate(GenericEvent $event)
    {
        $this->persistDoD($event);
        $this->enforceRequired($event, true);
    }

    public function afterUpdate(GenericEvent $event)
    {
        $this->persistDoD($event);
        $this->enforceRequired($event, false);
    }

    protected function persistDoD(GenericEvent $event)
    {
        $taskId = isset($event['task_id']) ? $event['task_id'] : (isset($event['task']['id']) ? $event['task']['id'] : null);
        if (!$taskId) {
            return;
        }
        $values = $this->request->getValues();
        if (array_key_exists('definition_of_done', $values)) {
            $this->db->table(TaskModel::TABLE)
                ->eq('id', $taskId)
                ->update(array('definition_of_done' => $values['definition_of_done']));
        }
    }

    /**
     * If required fields empty: remove newly created task (create) or flash error.
     */
    protected function enforceRequired(GenericEvent $event, $isCreate)
    {
        $values = $this->request->getValues();
        if (empty($values) || !is_array($values)) {
            return;
        }

        // Only for task forms that include our fields (create/update from UI)
        $hasDoDKey = array_key_exists('definition_of_done', $values);
        $hasCatKey = array_key_exists('category_id', $values);
        $hasRefKey = array_key_exists('reference', $values);
        if (!$hasDoDKey && !$hasCatKey && !$hasRefKey) {
            return; // API / internal create — skip
        }

        $errors = array();
        $dod = isset($values['definition_of_done']) ? trim($values['definition_of_done']) : '';
        $cat = isset($values['category_id']) ? $values['category_id'] : '';
        $ref = isset($values['reference']) ? trim($values['reference']) : '';

        if ($dod === '') {
            $errors[] = t('Definition of Done is required.');
        }
        if ($cat === '' || $cat === '0' || $cat === 0 || $cat === null) {
            $errors[] = t('Category is required.');
        }
        if ($ref === '') {
            $errors[] = t('Reference is required.');
        }

        if (empty($errors)) {
            return;
        }

        $taskId = isset($event['task_id']) ? $event['task_id'] : (isset($event['task']['id']) ? $event['task']['id'] : null);

        if ($isCreate && $taskId) {
            // Roll back the task that was just created without required fields
            $this->db->table(TaskModel::TABLE)->eq('id', $taskId)->remove();
        }

        $this->flash->failure(implode(' ', $errors));
    }
}
