<?php

namespace Kanboard\Plugin\AhvazTaskFields\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Core\Controller\AccessForbiddenException;

class DepartmentController extends BaseController
{
    public function index()
    {
        $project = $this->getProject();
        $this->checkAccess($project['id']);

        $departments = $this->departmentModel->getAllByProject($project['id'], false);

        $this->response->html($this->helper->layout->project('AhvazTaskFields:department/index', array(
            'project'     => $project,
            'departments' => $departments,
            'title'       => t('Departments') . ' — ' . $project['name'],
        )));
    }

    public function create(array $values = array(), array $errors = array())
    {
        $project = $this->getProject();
        $this->checkAccess($project['id']);

        if (empty($values)) {
            $values = array('project_id' => $project['id'], 'is_active' => 1);
        }

        $this->response->html($this->template->render('AhvazTaskFields:department/create', array(
            'project' => $project,
            'values'  => $values,
            'errors'  => $errors,
        )));
    }

    public function save()
    {
        $project = $this->getProject();
        $this->checkAccess($project['id']);

        $values = $this->request->getValues();
        $values['project_id'] = $project['id'];
        $values['is_active'] = isset($values['is_active']) ? 1 : 0;

        if (empty(trim(isset($values['name']) ? $values['name'] : ''))) {
            $this->create($values, array('name' => array(t('This field is required'))));
            return;
        }

        $id = $this->departmentModel->create($values);

        if ($id) {
            $this->flash->success(t('Department created successfully.'));
        } else {
            $this->flash->failure(t('Unable to create this department.'));
        }

        $this->response->redirect($this->helper->url->to('DepartmentController', 'index', array(
            'plugin'     => 'AhvazTaskFields',
            'project_id' => $project['id'],
        )));
    }

    public function edit(array $values = array(), array $errors = array())
    {
        $project = $this->getProject();
        $this->checkAccess($project['id']);

        $departmentId = $this->request->getIntegerParam('department_id');
        $department = $this->departmentModel->getById($departmentId);

        if (empty($department) || (int)$department['project_id'] !== (int)$project['id']) {
            $this->flash->failure(t('Department not found.'));
            $this->response->redirect($this->helper->url->to('DepartmentController', 'index', array(
                'plugin' => 'AhvazTaskFields', 'project_id' => $project['id'],
            )));
            return;
        }

        if (empty($values)) {
            $values = $department;
        }

        $this->response->html($this->template->render('AhvazTaskFields:department/edit', array(
            'project'     => $project,
            'department'  => $department,
            'values'      => $values,
            'errors'      => $errors,
        )));
    }

    public function update()
    {
        $project = $this->getProject();
        $this->checkAccess($project['id']);

        $values = $this->request->getValues();
        $values['id'] = $this->request->getIntegerParam('department_id');
        $values['is_active'] = isset($values['is_active']) ? 1 : 0;

        $department = $this->departmentModel->getById($values['id']);
        if (empty($department) || (int)$department['project_id'] !== (int)$project['id']) {
            $this->flash->failure(t('Department not found.'));
            $this->response->redirect($this->helper->url->to('DepartmentController', 'index', array(
                'plugin' => 'AhvazTaskFields', 'project_id' => $project['id'],
            )));
            return;
        }

        if (empty(trim(isset($values['name']) ? $values['name'] : ''))) {
            $this->edit($values, array('name' => array(t('This field is required'))));
            return;
        }

        if ($this->departmentModel->update($values)) {
            $this->flash->success(t('Department updated successfully.'));
        } else {
            $this->flash->failure(t('Unable to update this department.'));
        }

        $this->response->redirect($this->helper->url->to('DepartmentController', 'index', array(
            'plugin'     => 'AhvazTaskFields',
            'project_id' => $project['id'],
        )));
    }

    public function remove()
    {
        $project = $this->getProject();
        $this->checkAccess($project['id']);
        $this->checkCSRFParam();

        $departmentId = $this->request->getIntegerParam('department_id');
        $department = $this->departmentModel->getById($departmentId);

        if (!empty($department) && (int)$department['project_id'] === (int)$project['id']) {
            if ($this->departmentModel->remove($departmentId)) {
                $this->flash->success(t('Department removed successfully.'));
            } else {
                $this->flash->failure(t('Unable to remove this department.'));
            }
        }

        $this->response->redirect($this->helper->url->to('DepartmentController', 'index', array(
            'plugin'     => 'AhvazTaskFields',
            'project_id' => $project['id'],
        )));
    }

    public function json()
    {
        $project = $this->getProject();
        // members can list departments for the task form dropdown
        $list = $this->departmentModel->getAllByProject($project['id'], true);
        $result = array();
        foreach ($list as $row) {
            $result[] = array('id' => $row['name'], 'text' => $row['name']);
        }
        $this->response->json(array('results' => $result));
    }

    /**
     * Access rule (after Kanboard ACL):
     * 1) App admin always
     * 2) HierarchicalRoles module "departments" OR "org_structure"
     * 3) Fallback: project-manager role in core project_has_users
     */
    protected function checkAccess($projectId)
    {
        if ($this->userSession->isAdmin()) {
            return;
        }

        $userId = (int) $this->userSession->getId();

        // HierarchicalRoles module check
        try {
            if (isset($this->helper->hierarchicalRoleHelper) && is_object($this->helper->hierarchicalRoleHelper)) {
                $h = $this->helper->hierarchicalRoleHelper;
                if (method_exists($h, 'canAccessModule')) {
                    if ($h->canAccessModule($projectId, 'departments') || $h->canAccessModule($projectId, 'org_structure')) {
                        return;
                    }
                }
            }
        } catch (\Exception $e) {
            // continue to fallbacks
        }

        // Direct DB fallback (in case helper not loaded)
        try {
            $roleRow = $this->db->table('plugin_hierarchical_role_users')
                ->eq('project_id', $projectId)
                ->eq('user_id', $userId)
                ->findOne();
            if ($roleRow) {
                $roleId = (int) $roleRow['role_id'];
                $module = $this->db->table('plugin_access_modules')->eq('slug', 'departments')->findOne();
                if ($module) {
                    $ok = $this->db->table('plugin_role_access')
                        ->eq('role_id', $roleId)
                        ->eq('module_id', (int)$module['id'])
                        ->eq('project_id', $projectId)
                        ->exists();
                    if ($ok) {
                        return;
                    }
                }
                $module2 = $this->db->table('plugin_access_modules')->eq('slug', 'org_structure')->findOne();
                if ($module2) {
                    $ok2 = $this->db->table('plugin_role_access')
                        ->eq('role_id', $roleId)
                        ->eq('module_id', (int)$module2['id'])
                        ->eq('project_id', $projectId)
                        ->exists();
                    if ($ok2) {
                        return;
                    }
                }
            }
        } catch (\Exception $e) {
        }

        // Core project manager
        $role = $this->projectUserRoleModel->getUserRole($projectId, $userId);
        if ($role === 'project-manager') {
            return;
        }

        throw new AccessForbiddenException();
    }
}
