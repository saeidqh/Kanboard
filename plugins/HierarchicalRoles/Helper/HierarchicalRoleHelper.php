<?php

namespace Kanboard\Plugin\HierarchicalRoles\Helper;

use Kanboard\Core\Base;
use Kanboard\Plugin\HierarchicalRoles\Model\AccessModuleModel;
use Kanboard\Plugin\HierarchicalRoles\Model\HierarchicalRoleModel;
use Kanboard\Plugin\HierarchicalRoles\Model\HierarchicalRoleUserModel;
use Kanboard\Plugin\HierarchicalRoles\Model\RoleAccessModel;

class HierarchicalRoleHelper extends Base
{
    /**
     * Check if current user can manage Organizational Structure module
     */
    public function canManageOrgStructure($projectId)
    {
        return $this->canAccessModule($projectId, AccessModuleModel::SLUG_ORG_STRUCTURE);
    }

    /**
     * Generic module access check by slug
     *
     * Usage in templates:
     *   <?php if ($this->hierarchicalRoleHelper->canAccessModule($project['id'], 'analytics')): ?>
     *
     * Usage in controllers:
     *   if (!$this->helper->hierarchicalRoleHelper->canAccessModule($project['id'], 'project_settings')) { ... }
     *
     * @param int    $projectId
     * @param string $slug   e.g. org_structure, analytics, project_settings
     * @return bool
     */
    public function canAccessModule($projectId, $slug)
    {
        if ($this->userSession->isAdmin()) {
            return true;
        }

        $userId = (int) $this->userSession->getId();
        $projectId = (int) $projectId;

        if ($userId <= 0 || $projectId <= 0 || $slug === '') {
            return false;
        }

        $moduleModel = new AccessModuleModel($this->container);
        $module = $moduleModel->getBySlug($slug);

        if (!$module) {
            return false;
        }

        $roleUserModel = new HierarchicalRoleUserModel($this->container);
        $roleId = $roleUserModel->getRoleIdForUser($projectId, $userId);

        if (!$roleId) {
            return false;
        }

        $roleAccessModel = new RoleAccessModel($this->container);
        return $roleAccessModel->hasAccess($roleId, (int) $module['id'], $projectId);
    }

    /**
     * Get list of users that current user is allowed to assign tasks to.
     * Returns null → no filtering (show all) — only for app admins.
     * Returns array → filtered list [userId => name]
     */
    public function getAssignableUsersForCurrentUser($projectId)
    {
        $userId = (int) $this->userSession->getId();

        if ($this->userSession->isAdmin()) {
            return null;
        }

        $roleUserModel = new HierarchicalRoleUserModel($this->container);
        $roleModel = new HierarchicalRoleModel($this->container);

        $myRoleId = $roleUserModel->getRoleIdForUser($projectId, $userId);

        if (!$myRoleId) {
            $me = $this->userModel->getById($userId);
            return $me ? array($userId => ($me['name'] ?: $me['username'])) : array();
        }

        $myRole = $roleModel->getById($myRoleId);
        if (!$myRole) {
            return array();
        }

        if ((int) $myRole['level'] === 1) {
            $allRoleUsers = $roleUserModel->getAllByProject($projectId);
            $userIds = array($userId);
            foreach ($allRoleUsers as $ru) {
                $userIds[] = (int) $ru['user_id'];
            }
            $userIds = array_values(array_unique($userIds));
            return $this->formatUserList($userIds);
        }

        $descendantRoleIds = $roleModel->getDescendantIds($myRoleId);
        $allowedRoleIds = array_merge(array($myRoleId), $descendantRoleIds);

        $userIds = $roleUserModel->getUsersByRoleIds($allowedRoleIds);
        $userIds[] = $userId;
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        return $this->formatUserList($userIds);
    }

    private function formatUserList(array $userIds)
    {
        if (empty($userIds)) {
            return array();
        }

        $users = $this->db->table('users')
            ->in('id', $userIds)
            ->eq('is_active', 1)
            ->asc('name')
            ->asc('username')
            ->findAll();

        $list = array();
        foreach ($users as $user) {
            $list[(int) $user['id']] = $user['name'] ?: $user['username'];
        }

        return $list;
    }
}
