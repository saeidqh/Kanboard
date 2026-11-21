<?php

namespace Kanboard\Plugin\HierarchicalRoles\Model;

use Kanboard\Model\ProjectUserRoleModel;
use Kanboard\Plugin\HierarchicalRoles\Helper\HierarchicalRoleHelper;

/**
 * Filters only getAssignableUsers (task assignee dropdown / board assign).
 * getAllUsers stays unfiltered so Org Structure "Assign Users to Roles"
 * can list every project member for managers with module access.
 */
class FilteredProjectUserRoleModel extends ProjectUserRoleModel
{
    /**
     * Users that can be assigned to a task — hierarchy filtered.
     *
     * @param  integer $project_id
     * @return array   [user_id => name]
     */
    public function getAssignableUsers($project_id)
    {
        $users = parent::getAssignableUsers($project_id);

        $helper = new HierarchicalRoleHelper($this->container);
        $filtered = $helper->getAssignableUsersForCurrentUser($project_id);

        // null = no filtering (admin)
        if ($filtered === null) {
            return $users;
        }

        $result = array();
        foreach ($filtered as $userId => $name) {
            if (isset($users[$userId])) {
                $result[$userId] = $users[$userId];
            } else {
                $result[$userId] = $name;
            }
        }

        return $result;
    }
}
