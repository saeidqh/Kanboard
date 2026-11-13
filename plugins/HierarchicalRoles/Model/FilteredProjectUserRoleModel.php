<?php

namespace Kanboard\Plugin\HierarchicalRoles\Model;

use Kanboard\Model\ProjectUserRoleModel;
use Kanboard\Plugin\HierarchicalRoles\Helper\HierarchicalRoleHelper;

/**
 * Extends core ProjectUserRoleModel to filter assignable users
 * by organizational hierarchy.
 */
class FilteredProjectUserRoleModel extends ProjectUserRoleModel
{
    /**
     * Get list of users that can be assigned to a task,
     * filtered by the current user's position in the hierarchy.
     *
     * @param  integer $project_id
     * @return array   [user_id => name]
     */
    public function getAssignableUsers($project_id)
    {
        // Original full list from core
        $users = parent::getAssignableUsers($project_id);

        $helper = new HierarchicalRoleHelper($this->container);
        $filtered = $helper->getAssignableUsersForCurrentUser($project_id);

        // null = no filtering (e.g. app admin)
        if ($filtered === null) {
            return $users;
        }

        // Keep only allowed users (intersection), preserve names from original when possible
        $result = array();
        foreach ($filtered as $userId => $name) {
            if (isset($users[$userId])) {
                $result[$userId] = $users[$userId];
            } else {
                // Still allow (e.g. self) even if somehow missing from core list
                $result[$userId] = $name;
            }
        }

        return $result;
    }
}
