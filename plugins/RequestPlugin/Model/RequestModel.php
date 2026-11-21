<?php

namespace Kanboard\Plugin\RequestPlugin\Model;

use Kanboard\Core\Base;

class RequestModel extends Base
{
    const TABLE = 'requests';

    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FORWARDED = 'forwarded';

    public static function getStatuses()
    {
        return array(
            self::STATUS_DRAFT => t('Draft'),
            self::STATUS_SUBMITTED => t('Submitted'),
            self::STATUS_UNDER_REVIEW => t('Under Review'),
            self::STATUS_ACCEPTED => t('Accepted'),
            self::STATUS_REJECTED => t('Rejected'),
            self::STATUS_IN_PROGRESS => t('In Progress'),
            self::STATUS_COMPLETED => t('Completed'),
            self::STATUS_CANCELLED => t('Cancelled'),
            self::STATUS_FORWARDED => t('Forwarded'),
        );
    }

    public static function getPriorities()
    {
        return array(
            1 => t('Low'),
            2 => t('Normal'),
            3 => t('High'),
            4 => t('Critical'),
        );
    }

    public static function getCategories()
    {
        return array(
            'general' => t('General'),
            'approval' => t('Approval'),
            'resource' => t('Resource'),
            'information' => t('Information'),
            'change' => t('Change'),
            'support' => t('Support'),
        );
    }

    public function generateReference()
    {
        $year = date('Y');
        $last = $this->db->table(self::TABLE)
            ->like('reference', 'REQ-' . $year . '-%')
            ->desc('id')
            ->findOne();

        $seq = 1;
        if ($last && preg_match('/REQ-\d{4}-(\d+)/', $last['reference'], $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return sprintf('REQ-%s-%05d', $year, $seq);
    }

    public function create(array $values)
    {
        $now = date('Y-m-d H:i:s');
        $userId = $this->userSession->getId();

        $data = array(
            'reference' => $this->generateReference(),
            'title' => $values['title'],
            'description' => isset($values['description']) ? $values['description'] : '',
            'requester_id' => $userId,
            'assignee_id' => (int) $values['assignee_id'],
            'project_id' => (int) $values['project_id'],
            'category' => isset($values['category']) ? $values['category'] : 'general',
            'priority' => isset($values['priority']) ? (int) $values['priority'] : 2,
            'status' => self::STATUS_SUBMITTED,
            'due_date' => !empty($values['due_date']) ? $values['due_date'] : null,
            'needed_by' => !empty($values['needed_by']) ? $values['needed_by'] : null,
            'current_holder_id' => (int) $values['assignee_id'],
            'created_at' => $now,
            'updated_at' => $now,
            'submitted_at' => $now,
        );

        $requestId = $this->db->table(self::TABLE)->persist($data);

        if ($requestId) {
            $this->requestHistoryModel->add($requestId, $userId, 'created', null, (int) $values['assignee_id'], t('Request created and submitted'));
        }

        return $requestId;
    }

    public function getById($requestId)
    {
        return $this->db->table(self::TABLE)->eq('id', $requestId)->findOne();
    }

    public function getByIdWithUsers($requestId)
    {
        $rq = $this->getById($requestId);
        if (!$rq) {
            return null;
        }

        $rq['requester'] = $this->userModel->getById($rq['requester_id']);
        $rq['assignee'] = $this->userModel->getById($rq['assignee_id']);
        $rq['holder'] = $this->userModel->getById($rq['current_holder_id']);
        if (!empty($rq['converted_by'])) {
            $rq['converter'] = $this->userModel->getById($rq['converted_by']);
        }
        if (!empty($rq['project_id'])) {
            $rq['project'] = $this->projectModel->getById($rq['project_id']);
        }
        if (!empty($rq['linked_task_id'])) {
            $rq['task'] = $this->taskFinderModel->getById($rq['linked_task_id']);
        }

        return $rq;
    }

    public function getInbox($userId)
    {
        return $this->db->table(self::TABLE)
            ->eq('current_holder_id', $userId)
            ->neq('status', self::STATUS_COMPLETED)
            ->neq('status', self::STATUS_CANCELLED)
            ->neq('status', self::STATUS_REJECTED)
            ->desc('updated_at')
            ->findAll();
    }

    public function getSent($userId)
    {
        return $this->db->table(self::TABLE)
            ->eq('requester_id', $userId)
            ->desc('updated_at')
            ->findAll();
    }

    public function getAllForUser($userId)
    {
        return $this->db->table(self::TABLE)
            ->beginOr()
            ->eq('requester_id', $userId)
            ->eq('assignee_id', $userId)
            ->eq('current_holder_id', $userId)
            ->closeOr()
            ->desc('updated_at')
            ->findAll();
    }

    public function updateStatus($requestId, $status, array $extra = array())
    {
        $data = array_merge(array(
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ), $extra);

        if (in_array($status, array(self::STATUS_COMPLETED, self::STATUS_REJECTED, self::STATUS_CANCELLED), true)) {
            $data['closed_at'] = date('Y-m-d H:i:s');
        }

        return $this->db->table(self::TABLE)->eq('id', $requestId)->update($data);
    }

    public function accept($requestId, $userId, $note = '')
    {
        $rq = $this->getById($requestId);
        if (!$rq || (int) $rq['current_holder_id'] !== (int) $userId) {
            return false;
        }

        $ok = $this->updateStatus($requestId, self::STATUS_ACCEPTED, array(
            'acceptance_note' => $note,
        ));

        if ($ok) {
            $this->requestHistoryModel->add($requestId, $userId, 'accepted', null, null, $note);
        }

        return $ok;
    }

    public function reject($requestId, $userId, $reason = '')
    {
        $rq = $this->getById($requestId);
        if (!$rq || (int) $rq['current_holder_id'] !== (int) $userId) {
            return false;
        }

        $ok = $this->updateStatus($requestId, self::STATUS_REJECTED, array(
            'rejection_reason' => $reason,
        ));

        if ($ok) {
            $this->requestHistoryModel->add($requestId, $userId, 'rejected', null, null, $reason);
        }

        return $ok;
    }

    public function forward($requestId, $fromUserId, $toUserId, $note = '')
    {
        $rq = $this->getById($requestId);
        if (!$rq || (int) $rq['current_holder_id'] !== (int) $fromUserId) {
            return false;
        }

        if (!empty($rq['linked_task_id'])) {
            return false;
        }

        $ok = $this->db->table(self::TABLE)->eq('id', $requestId)->update(array(
            'assignee_id' => (int) $toUserId,
            'current_holder_id' => (int) $toUserId,
            'status' => self::STATUS_FORWARDED,
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        if ($ok) {
            $this->requestHistoryModel->add($requestId, $fromUserId, 'forwarded', $fromUserId, $toUserId, $note);
        }

        return $ok;
    }

    public function complete($requestId, $userId, $note = '')
    {
        $rq = $this->getById($requestId);
        if (!$rq) {
            return false;
        }

        // Holder or requester can mark complete after accept
        $allowed = in_array((int) $userId, array((int) $rq['current_holder_id'], (int) $rq['requester_id']), true);
        if (!$allowed) {
            return false;
        }

        $ok = $this->updateStatus($requestId, self::STATUS_COMPLETED);
        if ($ok) {
            $this->requestHistoryModel->add($requestId, $userId, 'completed', null, null, $note);
        }

        return $ok;
    }

    public function convertToTask($requestId, $userId, $columnId = null)
    {
        $rq = $this->getById($requestId);
        if (!$rq) {
            return false;
        }

        // Already converted?
        if (!empty($rq['linked_task_id'])) {
            return false;
        }

        // Only requester or current holder
        $allowed = in_array((int) $userId, array((int) $rq['requester_id'], (int) $rq['current_holder_id']), true);
        if (!$allowed) {
            return false;
        }

        // Prefer accepted or in_progress, but allow convert after accept path
        $projectId = (int) $rq['project_id'];
        if (!$columnId) {
            $column = $this->db->table('columns')->eq('project_id', $projectId)->asc('position')->findOne();
            $columnId = $column ? (int) $column['id'] : 0;
        }

        if (!$columnId) {
            return false;
        }

        $taskId = $this->taskCreationModel->create(array(
            'title' => $rq['title'],
            'description' => $rq['description'] . "\n\n---\n" . t('Converted from request') . ' ' . $rq['reference'],
            'project_id' => $projectId,
            'column_id' => $columnId,
            'owner_id' => (int) $rq['current_holder_id'],
            'creator_id' => $userId,
            'date_due' => $rq['due_date'],
            'priority' => max(0, min(3, ((int) $rq['priority']) - 1)),
        ));

        if (!$taskId) {
            return false;
        }

        $ok = $this->db->table(self::TABLE)->eq('id', $requestId)->update(array(
            'linked_task_id' => $taskId,
            'converted_by' => $userId,
            'converted_at' => date('Y-m-d H:i:s'),
            'status' => self::STATUS_IN_PROGRESS,
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        if ($ok) {
            $this->requestHistoryModel->add($requestId, $userId, 'converted', null, null, t('Converted to task #%d', $taskId));
        }

        return $taskId;
    }

    public function enrichList(array $rows)
    {
        if (empty($rows)) {
            return array();
        }

        $userIds = array();
        $projectIds = array();
        foreach ($rows as $r) {
            $userIds[] = $r['requester_id'];
            $userIds[] = $r['assignee_id'];
            $userIds[] = $r['current_holder_id'];
            $projectIds[] = $r['project_id'];
        }
        $userIds = array_unique($userIds);
        $projectIds = array_unique($projectIds);

        $users = array();
        foreach ($userIds as $uid) {
            $u = $this->userModel->getById($uid);
            if ($u) {
                $users[$uid] = $u;
            }
        }

        $projects = array();
        foreach ($projectIds as $pid) {
            $p = $this->projectModel->getById($pid);
            if ($p) {
                $projects[$pid] = $p;
            }
        }

        foreach ($rows as &$r) {
            $r['requester_name'] = isset($users[$r['requester_id']]) ? $users[$r['requester_id']]['name'] ?: $users[$r['requester_id']]['username'] : '?';
            $r['assignee_name'] = isset($users[$r['assignee_id']]) ? $users[$r['assignee_id']]['name'] ?: $users[$r['assignee_id']]['username'] : '?';
            $r['holder_name'] = isset($users[$r['current_holder_id']]) ? $users[$r['current_holder_id']]['name'] ?: $users[$r['current_holder_id']]['username'] : '?';
            $r['project_name'] = isset($projects[$r['project_id']]) ? $projects[$r['project_id']]['name'] : '?';
            $r['status_label'] = self::getStatuses()[$r['status']] ?? $r['status'];
            $r['priority_label'] = self::getPriorities()[$r['priority']] ?? $r['priority'];
            $r['is_converted'] = !empty($r['linked_task_id']);
        }

        return $rows;
    }
}
