<?php

namespace Kanboard\Plugin\AhvazTaskFields\Model;

use Kanboard\Core\Base;

class DepartmentModel extends Base
{
    const TABLE = 'plugin_departments';

    public function getAllByProject($projectId, $onlyActive = true)
    {
        $query = $this->db->table(self::TABLE)
            ->eq('project_id', $projectId)
            ->asc('position')
            ->asc('name');

        if ($onlyActive) {
            $query->eq('is_active', 1);
        }

        return $query->findAll();
    }

    public function getById($id)
    {
        return $this->db->table(self::TABLE)->eq('id', $id)->findOne();
    }

    public function create(array $values)
    {
        $values['created_at'] = time();
        $values['updated_at'] = time();

        if (!isset($values['position'])) {
            $max = $this->db->table(self::TABLE)
                ->eq('project_id', $values['project_id'])
                ->columns('MAX(position) AS max_pos')
                ->findOne();
            $values['position'] = (int)($max['max_pos'] ?? 0) + 1;
        }

        return $this->db->table(self::TABLE)->persist($values);
    }

    public function update(array $values)
    {
        $values['updated_at'] = time();
        $id = $values['id'];
        unset($values['id']);

        return $this->db->table(self::TABLE)->eq('id', $id)->update($values);
    }

    public function remove($id)
    {
        return $this->db->table(self::TABLE)->eq('id', $id)->remove();
    }

    public function getListForSelect($projectId)
    {
        $rows = $this->getAllByProject($projectId, true);
        $list = ['' => t('Select a department')];

        foreach ($rows as $row) {
            $list[$row['name']] = $row['name'];
        }

        return $list;
    }
}
