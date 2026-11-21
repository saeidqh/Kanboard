<?php

namespace Kanboard\Plugin\HierarchicalRoles\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Plugin\HierarchicalRoles\Model\HierarchicalRoleModel;
use Kanboard\Plugin\HierarchicalRoles\Model\HierarchicalRoleUserModel;
use Kanboard\Plugin\HierarchicalRoles\Model\AccessModuleModel;
use Kanboard\Plugin\HierarchicalRoles\Model\RoleAccessModel;

class HierarchicalRoleController extends BaseController
{
    private function checkAccess()
    {
        $project = $this->getProject();
        $helper = $this->helper->hierarchicalRoleHelper;

        if (!$helper->canManageOrgStructure($project['id'])) {
            $this->flash->failure(t('You do not have access to Organizational Structure module.'));
            $this->response->redirect($this->helper->url->to('BoardViewController', 'show', array('project_id' => $project['id'])));
            return false;
        }

        return $project;
    }

    public function index()
    {
        $project = $this->checkAccess();
        if (!$project) {
            return;
        }

        $roleModel = new HierarchicalRoleModel($this->container);
        $roles = $roleModel->getAllByProject($project['id']);
        $tree = $roleModel->getTree($project['id']);

        $this->response->html($this->helper->layout->project('HierarchicalRoles:project/roles', array(
            'project' => $project,
            'roles'   => $roles,
            'tree'    => $tree,
            'title'   => t('Organizational Structure'),
        )));
    }

    public function create(array $values = array(), array $errors = array())
    {
        $project = $this->checkAccess();
        if (!$project) {
            return;
        }

        $roleModel = new HierarchicalRoleModel($this->container);

        $this->response->html($this->template->render('HierarchicalRoles:project/role_form', array(
            'project' => $project,
            'values'  => $values + array('project_id' => $project['id']),
            'errors'  => $errors,
            'parents' => $roleModel->getListForDropdown($project['id']),
            'title'   => t('New Role'),
        )));
    }

    public function save()
    {
        $project = $this->checkAccess();
        if (!$project) {
            return;
        }

        $values = $this->request->getValues();
        $values['project_id'] = $project['id'];

        $roleModel = new HierarchicalRoleModel($this->container);

        if (empty($values['title'])) {
            $this->create($values, array('title' => array(t('Title is required'))));
            return;
        }

        $id = $roleModel->create($values);

        if ($id) {
            $this->flash->success(t('Role created successfully.'));
        } else {
            $this->flash->failure(t('Unable to create role.'));
        }

        $this->response->redirect($this->helper->url->to('HierarchicalRoleController', 'index', array(
            'plugin' => 'HierarchicalRoles',
            'project_id' => $project['id'],
        )));
    }

    public function edit(array $values = array(), array $errors = array())
    {
        $project = $this->checkAccess();
        if (!$project) {
            return;
        }

        $roleId = $this->request->getIntegerParam('role_id');
        $roleModel = new HierarchicalRoleModel($this->container);
        $role = $roleModel->getById($roleId);

        if (!$role || (int) $role['project_id'] !== (int) $project['id']) {
            $this->flash->failure(t('Role not found.'));
            $this->response->redirect($this->helper->url->to('HierarchicalRoleController', 'index', array(
                'plugin' => 'HierarchicalRoles',
                'project_id' => $project['id'],
            )));
            return;
        }

        $this->response->html($this->template->render('HierarchicalRoles:project/role_form', array(
            'project' => $project,
            'values'  => empty($values) ? $role : $values,
            'errors'  => $errors,
            'parents' => $roleModel->getListForDropdown($project['id'], $roleId),
            'title'   => t('Edit Role'),
        )));
    }

    public function update()
    {
        $project = $this->checkAccess();
        if (!$project) {
            return;
        }

        $values = $this->request->getValues();
        $roleModel = new HierarchicalRoleModel($this->container);

        if (empty($values['title']) || empty($values['id'])) {
            $this->edit($values, array('title' => array(t('Title is required'))));
            return;
        }

        if ($roleModel->update($values)) {
            $this->flash->success(t('Role updated successfully.'));
        } else {
            $this->flash->failure(t('Unable to update role. Check for circular reference.'));
        }

        $this->response->redirect($this->helper->url->to('HierarchicalRoleController', 'index', array(
            'plugin' => 'HierarchicalRoles',
            'project_id' => $project['id'],
        )));
    }

    public function confirm()
    {
        $project = $this->checkAccess();
        if (!$project) {
            return;
        }

        $roleId = $this->request->getIntegerParam('role_id');
        $roleModel = new HierarchicalRoleModel($this->container);
        $role = $roleModel->getById($roleId);

        $this->response->html($this->template->render('HierarchicalRoles:project/role_remove', array(
            'project' => $project,
            'role'    => $role,
        )));
    }

    public function remove()
    {
        $project = $this->checkAccess();
        if (!$project) {
            return;
        }

        $this->checkCSRFParam();
        $roleId = $this->request->getIntegerParam('role_id');
        $roleModel = new HierarchicalRoleModel($this->container);

        if ($roleModel->remove($roleId)) {
            $this->flash->success(t('Role removed successfully.'));
        } else {
            $this->flash->failure(t('Unable to remove role.'));
        }

        $this->response->redirect($this->helper->url->to('HierarchicalRoleController', 'index', array(
            'plugin' => 'HierarchicalRoles',
            'project_id' => $project['id'],
        )));
    }

    /**
     * Assign users to roles
     */
    public function users()
    {
        $project = $this->checkAccess();
        if (!$project) {
            return;
        }

        $roleModel = new HierarchicalRoleModel($this->container);
        $roleUserModel = new HierarchicalRoleUserModel($this->container);

        $roles = $roleModel->getAllByProject($project['id']);
        $assignments = $roleUserModel->getAllByProject($project['id']);
        $assignmentMap = array();
        foreach ($assignments as $a) {
            $assignmentMap[(int) $a['user_id']] = (int) $a['role_id'];
        }

        // Full project members for role assignment (NOT hierarchy-filtered).
        // Use core ProjectUserRoleModel so Filtered override cannot shrink this list.
        $coreUserRoleModel = new \Kanboard\Model\ProjectUserRoleModel($this->container);
        $rawUsers = $coreUserRoleModel->getAllUsers($project['id']);
        $projectUsers = array();

        if (!empty($rawUsers)) {
            // Detect structure: associative [id => string] vs list of arrays
            $first = reset($rawUsers);
            if (is_array($first) && isset($first['id'])) {
                // Already list of user rows
                $projectUsers = $rawUsers;
            } else {
                // [user_id => role_name] or [user_id => name]
                $userIds = array_map('intval', array_keys($rawUsers));
                if (!empty($userIds)) {
                    $rows = $this->db->table('users')
                        ->in('id', $userIds)
                        ->eq('is_active', 1)
                        ->asc('name')
                        ->asc('username')
                        ->findAll();
                    $projectUsers = $rows;
                }
            }
        }

        $this->response->html($this->helper->layout->project('HierarchicalRoles:project/assign_users', array(
            'project'        => $project,
            'roles'          => $roles,
            'project_users'  => $projectUsers,
            'assignment_map' => $assignmentMap,
            'title'          => t('Assign Users to Roles'),
        )));
    }

    public function saveUsers()
    {
        $project = $this->checkAccess();
        if (!$project) {
            return;
        }

        $values = $this->request->getValues();
        $roleUserModel = new HierarchicalRoleUserModel($this->container);

        if (!empty($values['assignments']) && is_array($values['assignments'])) {
            foreach ($values['assignments'] as $userId => $roleId) {
                $roleUserModel->assign($project['id'], (int) $userId, $roleId !== '' ? (int) $roleId : null);
            }
        }

        $this->flash->success(t('User role assignments saved.'));
        $this->response->redirect($this->helper->url->to('HierarchicalRoleController', 'users', array(
            'plugin' => 'HierarchicalRoles',
            'project_id' => $project['id'],
        )));
    }

    /**
     * Manage which roles can access the Org Structure module
     */
    public function access()
    {
        $project = $this->checkAccess();
        if (!$project) {
            return;
        }

        // Only app admins or already authorized roles should reach here (checkAccess already done)
        $roleModel = new HierarchicalRoleModel($this->container);
        $roleAccessModel = new RoleAccessModel($this->container);
        $moduleModel = new AccessModuleModel($this->container);

        $roles = $roleModel->getAllByProject($project['id']);
        $modules = $moduleModel->getAll();
        $currentAccess = $roleAccessModel->getAllByProject($project['id']);

        // Build map: role_id => [module_id, ...]
        $accessMap = array();
        foreach ($currentAccess as $row) {
            $accessMap[$row['role_id']][] = $row['module_id'];
        }

        $this->response->html($this->helper->layout->project('HierarchicalRoles:project/access', array(
            'project'    => $project,
            'roles'      => $roles,
            'modules'    => $modules,
            'access_map' => $accessMap,
            'title'      => t('Module Access Control'),
        )));
    }

    public function saveAccess()
    {
        $project = $this->checkAccess();
        if (!$project) {
            return;
        }

        $values = $this->request->getValues();
        $roleAccessModel = new RoleAccessModel($this->container);

        // values['access'][role_id][] = module_id
        $accessData = isset($values['access']) && is_array($values['access']) ? $values['access'] : array();

        $roleModel = new HierarchicalRoleModel($this->container);
        $roles = $roleModel->getAllByProject($project['id']);

        foreach ($roles as $role) {
            $moduleIds = isset($accessData[$role['id']]) ? $accessData[$role['id']] : array();
            $roleAccessModel->setAccessForRole($role['id'], $project['id'], $moduleIds);
        }

        $this->flash->success(t('Access permissions saved.'));
        $this->response->redirect($this->helper->url->to('HierarchicalRoleController', 'access', array(
            'plugin' => 'HierarchicalRoles',
            'project_id' => $project['id'],
        )));
    }
}
