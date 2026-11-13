<?php

namespace Kanboard\Plugin\HierarchicalRoles\Model;

use Kanboard\Core\Base;

class RoleAccessModel extends Base
{
    const TABLE = 'plugin_role_access';

    public function hasAccess($roleId, $moduleId, $projectId)
    {
        if (!$roleId) {
            return false;
        }

        return $this->db->table(self::TABLE)
            ->eq('role_id', $roleId)
            ->eq('module_id', $moduleId)
            ->eq('project_id', $projectId)
            ->exists();
    }

    public function getRoleIdsByModule($moduleId, $projectId)
    {
        return $this->db->table(self::TABLE)
            ->eq('module_id', $moduleId)
            ->eq('project_id', $projectId)
            ->findAllByColumn('role_id');
    }

    public function getModulesByRole($roleId, $projectId)
    {
        return $this->db->table(self::TABLE)
            ->eq('role_id', $roleId)
            ->eq('project_id', $projectId)
            ->findAllByColumn('module_id');
    }

    public function grant($roleId, $moduleId, $projectId)
    {
        if ($this->hasAccess($roleId, $moduleId, $projectId)) {
            return true;
        }

        return $this->db->table(self::TABLE)->persist(array(
            'role_id'    => (int) $roleId,
            'module_id'  => (int) $moduleId,
            'project_id' => (int) $projectId,
            'created_at' => time(),
        ));
    }

    public function revoke($roleId, $moduleId, $projectId)
    {
        return $this->db->table(self::TABLE)
            ->eq('role_id', $roleId)
            ->eq('module_id', $moduleId)
            ->eq('project_id', $projectId)
            ->remove();
    }

    public function setAccessForRole($roleId, $projectId, array $moduleIds)
    {
        // Remove all current access for this role in project
        $this->db->table(self::TABLE)
            ->eq('role_id', $roleId)
            ->eq('project_id', $projectId)
            ->remove();

        foreach ($moduleIds as $moduleId) {
            $this->grant($roleId, $moduleId, $projectId);
        }

        return true;
    }

    public function getAllByProject($projectId)
    {
        return $this->db->table(self::TABLE)
            ->eq('project_id', $projectId)
            ->findAll();
    }
}
