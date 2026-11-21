<?php

namespace Kanboard\Plugin\RequestPlugin\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Plugin\RequestPlugin\Model\RequestModel;

class RequestController extends BaseController
{
    public function index()
    {
        $this->response->redirect($this->helper->url->to('RequestController', 'inbox', array('plugin' => 'RequestPlugin')));
    }

    public function inbox()
    {
        $userId = $this->userSession->getId();
        $requests = $this->requestModel->enrichList($this->requestModel->getInbox($userId));

        $this->response->html($this->helper->layout->app('RequestPlugin:request/list', array(
            'title' => t('Requests') . ' — ' . t('Inbox'),
            'tab' => 'inbox',
            'requests' => $requests,
        )));
    }

    public function sent()
    {
        $userId = $this->userSession->getId();
        $requests = $this->requestModel->enrichList($this->requestModel->getSent($userId));

        $this->response->html($this->helper->layout->app('RequestPlugin:request/list', array(
            'title' => t('Requests') . ' — ' . t('Sent'),
            'tab' => 'sent',
            'requests' => $requests,
        )));
    }

    public function all()
    {
        $userId = $this->userSession->getId();
        $requests = $this->requestModel->enrichList($this->requestModel->getAllForUser($userId));

        $this->response->html($this->helper->layout->app('RequestPlugin:request/list', array(
            'title' => t('Requests') . ' — ' . t('All'),
            'tab' => 'all',
            'requests' => $requests,
        )));
    }

    public function create()
    {
        $projects = $this->getProjectsForCurrentUser();
        $params = array(
            'title' => t('New Request'),
            'projects' => $projects,
            'categories' => RequestModel::getCategories(),
            'priorities' => RequestModel::getPriorities(),
            'values' => array(),
            'errors' => array(),
        );

        // Modal (ajax) vs full page
        if ($this->request->isAjax()) {
            $this->response->html($this->template->render('RequestPlugin:request/create', $params));
        } else {
            $this->response->html($this->helper->layout->app('RequestPlugin:request/create', $params));
        }
    }

    public function save()
    {
        $values = $this->request->getValues();
        $userId = $this->userSession->getId();

        $errors = array();
        if (empty($values['title'])) {
            $errors['title'] = array(t('Title is required'));
        }
        if (empty($values['project_id'])) {
            $errors['project_id'] = array(t('Project is required'));
        }
        if (empty($values['assignee_id'])) {
            $errors['assignee_id'] = array(t('Recipient is required'));
        }

        // Verify assignee belongs to project (best-effort)
        if (!empty($values['project_id']) && !empty($values['assignee_id'])) {
            $projectId = (int) $values['project_id'];
            $assigneeId = (int) $values['assignee_id'];
            $okMember = false;
            try {
                if (method_exists($this->projectUserRoleModel, 'isMember')) {
                    $okMember = $this->projectUserRoleModel->isMember($projectId, $assigneeId);
                } elseif (method_exists($this->projectPermissionModel, 'isAssignable')) {
                    $okMember = $this->projectPermissionModel->isAssignable($projectId, $assigneeId);
                } else {
                    $okMember = true; // skip strict check on unknown API
                }
            } catch (Exception $e) {
                $okMember = true;
            }
            if (!$okMember) {
                $errors['assignee_id'] = array(t('Selected user is not a member of this project'));
            }
        }

        if (!empty($errors)) {
            $projects = $this->getProjectsForCurrentUser();
            return $this->response->html($this->helper->layout->app('RequestPlugin:request/create', array(
                'title' => t('New Request'),
                'projects' => $projects,
                'categories' => RequestModel::getCategories(),
                'priorities' => RequestModel::getPriorities(),
                'values' => $values,
                'errors' => $errors,
            )));
        }

        $id = $this->requestModel->create($values);
        if ($id) {
            $this->flash->success(t('Request created successfully'));
            return $this->response->redirect($this->helper->url->to('RequestController', 'show', array('plugin' => 'RequestPlugin', 'request_id' => $id)));
        }

        $this->flash->failure(t('Unable to create request'));
        return $this->response->redirect($this->helper->url->to('RequestController', 'create', array('plugin' => 'RequestPlugin')));
    }

    public function show()
    {
        $requestId = $this->request->getIntegerParam('request_id');
        $rq = $this->requestModel->getByIdWithUsers($requestId);

        if (!$rq) {
            return $this->messageModel->error(t('Request not found'));
        }

        $userId = $this->userSession->getId();
        $comments = $this->requestCommentModel->getAll($requestId);
        $history = $this->requestHistoryModel->getAll($requestId);

        $isHolder = ((int) $rq['current_holder_id'] === (int) $userId);
        $isRequester = ((int) $rq['requester_id'] === (int) $userId);
        $canAct = $isHolder && !in_array($rq['status'], array('completed', 'cancelled', 'rejected'), true);
        $canConvert = ($isHolder || $isRequester) && empty($rq['linked_task_id']) && !in_array($rq['status'], array('rejected', 'cancelled'), true);
        $canForward = $isHolder && empty($rq['linked_task_id']) && !in_array($rq['status'], array('completed', 'cancelled', 'rejected'), true);

        $projectMembers = array();
        try {
            if (method_exists($this->projectUserRoleModel, 'getQueryableActiveMembersList')) {
                $projectMembers = $this->projectUserRoleModel->getQueryableActiveMembersList((int) $rq['project_id']);
            } elseif (method_exists($this->projectUserRoleModel, 'getMembers')) {
                $raw = $this->projectUserRoleModel->getMembers((int) $rq['project_id']);
                foreach ($raw as $m) {
                    $id = isset($m['id']) ? $m['id'] : (isset($m['user_id']) ? $m['user_id'] : 0);
                    $name = !empty($m['name']) ? $m['name'] : (!empty($m['username']) ? $m['username'] : '#' . $id);
                    if ($id) {
                        $projectMembers[$id] = $name;
                    }
                }
            }
        } catch (Exception $e) {
            $projectMembers = array();
        }

        $this->response->html($this->helper->layout->app('RequestPlugin:request/show', array(
            'title' => $rq['reference'] . ' — ' . $rq['title'],
            'request' => $rq,
            'comments' => $comments,
            'history' => $history,
            'is_holder' => $isHolder,
            'is_requester' => $isRequester,
            'can_act' => $canAct,
            'can_convert' => $canConvert,
            'can_forward' => $canForward,
            'project_members' => $projectMembers,
            'statuses' => RequestModel::getStatuses(),
            'priorities' => RequestModel::getPriorities(),
            'categories' => RequestModel::getCategories(),
        )));
    }

    public function accept()
    {
        $requestId = $this->request->getIntegerParam('request_id');
        $values = $this->request->getValues();
        $note = isset($values['note']) ? $values['note'] : '';

        if ($this->requestModel->accept($requestId, $this->userSession->getId(), $note)) {
            $this->flash->success(t('Request accepted'));
        } else {
            $this->flash->failure(t('Unable to accept request'));
        }

        return $this->response->redirect($this->helper->url->to('RequestController', 'show', array('plugin' => 'RequestPlugin', 'request_id' => $requestId)));
    }

    public function reject()
    {
        $requestId = $this->request->getIntegerParam('request_id');
        $values = $this->request->getValues();
        $reason = isset($values['reason']) ? $values['reason'] : '';

        if ($this->requestModel->reject($requestId, $this->userSession->getId(), $reason)) {
            $this->flash->success(t('Request rejected'));
        } else {
            $this->flash->failure(t('Unable to reject request'));
        }

        return $this->response->redirect($this->helper->url->to('RequestController', 'show', array('plugin' => 'RequestPlugin', 'request_id' => $requestId)));
    }

    public function forward()
    {
        $requestId = $this->request->getIntegerParam('request_id');
        $values = $this->request->getValues();
        $toUserId = isset($values['to_user_id']) ? (int) $values['to_user_id'] : 0;
        $note = isset($values['note']) ? $values['note'] : '';

        if ($toUserId && $this->requestModel->forward($requestId, $this->userSession->getId(), $toUserId, $note)) {
            $this->flash->success(t('Request forwarded'));
        } else {
            $this->flash->failure(t('Unable to forward request'));
        }

        return $this->response->redirect($this->helper->url->to('RequestController', 'show', array('plugin' => 'RequestPlugin', 'request_id' => $requestId)));
    }

    public function comment()
    {
        $requestId = $this->request->getIntegerParam('request_id');
        $values = $this->request->getValues();
        $comment = isset($values['comment']) ? trim($values['comment']) : '';

        if ($comment !== '') {
            $this->requestCommentModel->create($requestId, $this->userSession->getId(), $comment);
            $this->flash->success(t('Comment added'));
        }

        return $this->response->redirect($this->helper->url->to('RequestController', 'show', array('plugin' => 'RequestPlugin', 'request_id' => $requestId)));
    }

    public function convert()
    {
        $requestId = $this->request->getIntegerParam('request_id');
        $taskId = $this->requestModel->convertToTask($requestId, $this->userSession->getId());

        if ($taskId) {
            $this->flash->success(t('Request converted to task'));
            $rq = $this->requestModel->getById($requestId);
            return $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $taskId, 'project_id' => $rq['project_id'])));
        }

        $this->flash->failure(t('Unable to convert request (maybe already converted)'));
        return $this->response->redirect($this->helper->url->to('RequestController', 'show', array('plugin' => 'RequestPlugin', 'request_id' => $requestId)));
    }

    public function complete()
    {
        $requestId = $this->request->getIntegerParam('request_id');
        $values = $this->request->getValues();
        $note = isset($values['note']) ? $values['note'] : '';

        if ($this->requestModel->complete($requestId, $this->userSession->getId(), $note)) {
            $this->flash->success(t('Request marked as completed'));
        } else {
            $this->flash->failure(t('Unable to complete request'));
        }

        return $this->response->redirect($this->helper->url->to('RequestController', 'show', array('plugin' => 'RequestPlugin', 'request_id' => $requestId)));
    }

    /**
     * AJAX: list active members of a project (for searchable dropdown)
     */
    public function users()
    {
        $projectId = $this->request->getIntegerParam('project_id');
        $list = array();

        if ($projectId > 0) {
            try {
                // Direct project members
                $rows = $this->db->table('project_has_users')
                    ->columns('users.id', 'users.username', 'users.name')
                    ->join('users', 'id', 'user_id')
                    ->eq('project_has_users.project_id', $projectId)
                    ->eq('users.is_active', 1)
                    ->asc('users.name')
                    ->asc('users.username')
                    ->findAll();

                $seen = array();
                foreach ($rows as $u) {
                    $id = (int) $u['id'];
                    if (isset($seen[$id])) {
                        continue;
                    }
                    $seen[$id] = true;
                    $label = !empty($u['name']) ? $u['name'] : $u['username'];
                    $list[] = array('id' => $id, 'text' => $label);
                }

                // Group members (raw SQL — correct joins)
                try {
                    $sql = 'SELECT DISTINCT u.id, u.username, u.name
                            FROM project_has_groups phg
                            INNER JOIN group_has_users ghu ON ghu.group_id = phg.group_id
                            INNER JOIN users u ON u.id = ghu.user_id
                            WHERE phg.project_id = ? AND u.is_active = 1
                            ORDER BY u.name, u.username';
                    $stmt = $this->db->execute($sql, array($projectId));
                    $groupRows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : array();
                    foreach ($groupRows as $u) {
                        $id = (int) $u['id'];
                        if (isset($seen[$id])) {
                            continue;
                        }
                        $seen[$id] = true;
                        $label = !empty($u['name']) ? $u['name'] : $u['username'];
                        $list[] = array('id' => $id, 'text' => $label);
                    }
                } catch (Exception $e2) {
                    // optional
                }
            } catch (Exception $e) {
                try {
                    $users = $this->userModel->getAll();
                    foreach ($users as $u) {
                        if (!empty($u['is_active'])) {
                            $list[] = array(
                                'id' => (int) $u['id'],
                                'text' => !empty($u['name']) ? $u['name'] : $u['username'],
                            );
                        }
                    }
                } catch (Exception $e3) {
                    $list = array();
                }
            }
        }

        $this->response->json(array('results' => $list));
    }

    /**
     * Projects accessible by current user (list of [id, name]).
     */
    protected function getProjectsForCurrentUser()
    {
        $userId = $this->userSession->getId();
        $projects = array();

        try {
            if (method_exists($this->projectUserRoleModel, 'getActiveProjectsByUser')) {
                $map = $this->projectUserRoleModel->getActiveProjectsByUser($userId);
                foreach ($map as $id => $name) {
                    $projects[] = array('id' => (int) $id, 'name' => $name);
                }
            } elseif (method_exists($this->projectUserRoleModel, 'getProjectsByUser')) {
                $map = $this->projectUserRoleModel->getProjectsByUser($userId);
                foreach ($map as $id => $name) {
                    $projects[] = array('id' => (int) $id, 'name' => $name);
                }
            } else {
                // Fallback: all projects (admin-like)
                foreach ($this->projectModel->getAll() as $p) {
                    $projects[] = array('id' => (int) $p['id'], 'name' => $p['name']);
                }
            }
        } catch (Exception $e) {
            foreach ($this->projectModel->getAll() as $p) {
                $projects[] = array('id' => (int) $p['id'], 'name' => $p['name']);
            }
        }

        return $projects;
    }
}
