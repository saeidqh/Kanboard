<?php

namespace Kanboard\Plugin\HierarchicalRoles\Model;

use Kanboard\Core\Base;

class HierarchicalRoleUserModel extends Base
{
    const TABLE = 'plugin_hierarchical_role_users';

    public function getByProjectAndUser($projectId, $userId)
    {
        return $this->db->table(self::TABLE)
            ->eq('project_id', $projectId)
            ->eq('user_id', $userId)
            ->findOne();
    }

    public function getRoleIdForUser($projectId, $userId)
    {
        $row = $this->getByProjectAndUser($projectId, $userId);
        return $row ? (int) $row['role_id'] : null;
    }

    public function getUsersByRole($roleId)
    {
        return $this->db->table(self::TABLE)
            ->eq('role_id', $roleId)
            ->findAllByColumn('user_id');
    }

    public function getUsersByRoleIds(array $roleIds)
    {
        if (empty($roleIds)) {
            return array();
        }

        return $this->db->table(self::TABLE)
            ->in('role_id', $roleIds)
            ->findAllByColumn('user_id');
    }

    public function getAllByProject($projectId)
    {
        return $this->db->table(self::TABLE)
            ->eq('project_id', $projectId)
            ->findAll();
    }

    /**
     * Assign user to role (one role per user per project)
     */
    public function assign($projectId, $userId, $roleId)
    {
        // Remove existing assignment in this project
        $this->db->table(self::TABLE)
            ->eq('project_id', $projectId)
            ->eq('user_id', $userId)
            ->remove();

        if (empty($roleId)) {
            return true; // just unassign
        }

        return $this->db->table(self::TABLE)->persist(array(
            'project_id' => (int) $projectId,
            'user_id'    => (int) $userId,
            'role_id'    => (int) $roleId,
            'created_at' => time(),
        ));
    }

    public function removeByRole($roleId)
    {
        return $this->db->table(self::TABLE)->eq('role_id', $roleId)->remove();
    }
}
