<?php

namespace Kanboard\Plugin\HierarchicalRoles\Model;

use Kanboard\Core\Base;

class HierarchicalRoleModel extends Base
{
    const TABLE = 'plugin_hierarchical_roles';

    public function getById($id)
    {
        return $this->db->table(self::TABLE)->eq('id', $id)->findOne();
    }

    public function getAllByProject($projectId)
    {
        return $this->db->table(self::TABLE)
            ->eq('project_id', $projectId)
            ->asc('level')
            ->asc('position')
            ->asc('title')
            ->findAll();
    }

    public function getTree($projectId)
    {
        $roles = $this->getAllByProject($projectId);
        return $this->buildTree($roles);
    }

    private function buildTree(array $roles, $parentId = null)
    {
        $branch = array();

        foreach ($roles as $role) {
            $roleParent = $role['parent_id'] === null || $role['parent_id'] === '' ? null : (int) $role['parent_id'];
            $compareParent = $parentId === null ? null : (int) $parentId;

            if ($roleParent === $compareParent) {
                $children = $this->buildTree($roles, $role['id']);
                $role['children'] = $children;
                $branch[] = $role;
            }
        }

        return $branch;
    }

    public function create(array $values)
    {
        $now = time();
        $parentId = !empty($values['parent_id']) ? (int) $values['parent_id'] : null;
        $level = 1;

        if ($parentId) {
            $parent = $this->getById($parentId);
            if ($parent) {
                $level = (int) $parent['level'] + 1;
            }
        }

        return $this->db->table(self::TABLE)->persist(array(
            'project_id'  => (int) $values['project_id'],
            'title'       => trim($values['title']),
            'parent_id'   => $parentId,
            'level'       => $level,
            'description' => isset($values['description']) ? $values['description'] : null,
            'position'    => isset($values['position']) ? (int) $values['position'] : 0,
            'created_at'  => $now,
            'updated_at'  => $now,
        ));
    }

    public function update(array $values)
    {
        $id = (int) $values['id'];
        $parentId = !empty($values['parent_id']) ? (int) $values['parent_id'] : null;

        // Prevent circular reference
        if ($parentId && $this->isDescendant($id, $parentId)) {
            return false;
        }

        $level = 1;
        if ($parentId) {
            $parent = $this->getById($parentId);
            if ($parent) {
                $level = (int) $parent['level'] + 1;
            }
        }

        $result = $this->db->table(self::TABLE)->eq('id', $id)->update(array(
            'title'       => trim($values['title']),
            'parent_id'   => $parentId,
            'level'       => $level,
            'description' => isset($values['description']) ? $values['description'] : null,
            'position'    => isset($values['position']) ? (int) $values['position'] : 0,
            'updated_at'  => time(),
        ));

        // Recalculate children levels
        $this->recalculateLevels($id, $level);

        return $result;
    }

    private function recalculateLevels($parentId, $parentLevel)
    {
        $children = $this->db->table(self::TABLE)->eq('parent_id', $parentId)->findAll();
        foreach ($children as $child) {
            $newLevel = $parentLevel + 1;
            $this->db->table(self::TABLE)->eq('id', $child['id'])->update(array(
                'level' => $newLevel,
                'updated_at' => time(),
            ));
            $this->recalculateLevels($child['id'], $newLevel);
        }
    }

    public function remove($id)
    {
        // Move children to parent of deleted node (or null)
        $role = $this->getById($id);
        if (!$role) {
            return false;
        }

        $this->db->table(self::TABLE)
            ->eq('parent_id', $id)
            ->update(array('parent_id' => $role['parent_id']));

        // Remove user assignments for this role
        $this->db->table('plugin_hierarchical_role_users')->eq('role_id', $id)->remove();

        // Remove access grants for this role
        $this->db->table('plugin_role_access')->eq('role_id', $id)->remove();

        return $this->db->table(self::TABLE)->eq('id', $id)->remove();
    }

    /**
     * Get all descendant role IDs (children, grandchildren, ...)
     */
    public function getDescendantIds($roleId)
    {
        $ids = array();
        $this->collectDescendants($roleId, $ids);
        return $ids;
    }

    private function collectDescendants($roleId, array &$ids)
    {
        $children = $this->db->table(self::TABLE)->eq('parent_id', $roleId)->findAllByColumn('id');
        foreach ($children as $childId) {
            $ids[] = (int) $childId;
            $this->collectDescendants($childId, $ids);
        }
    }

    /**
     * Check if $possibleDescendant is under $roleId
     */
    public function isDescendant($roleId, $possibleDescendant)
    {
        $descendants = $this->getDescendantIds($roleId);
        return in_array((int) $possibleDescendant, $descendants, true);
    }

    public function getListForDropdown($projectId, $excludeId = null)
    {
        $roles = $this->getAllByProject($projectId);
        $list = array('' => t('No parent (Level 1)'));

        foreach ($roles as $role) {
            if ($excludeId && (int) $role['id'] === (int) $excludeId) {
                continue;
            }
            $prefix = str_repeat('— ', max(0, (int) $role['level'] - 1));
            $list[$role['id']] = $prefix . $role['title'] . ' (L' . $role['level'] . ')';
        }

        return $list;
    }
}
