<?php

namespace Kanboard\Plugin\AhvazTaskFields\Model;

use Kanboard\Core\Base;

class FormFieldModel extends Base
{
    const TABLE = 'plugin_form_fields';

    /**
     * Get all fields for a form. Project-specific overrides global (project_id NULL).
     * Returns map: field_key => row
     */
    public function getFieldsForForm($formKey, $projectId = null)
    {
        // Global defaults
        $globals = $this->db->table(self::TABLE)
            ->eq('form_key', $formKey)
            ->isNull('project_id')
            ->asc('position')
            ->findAll();

        $map = array();
        foreach ($globals as $row) {
            $map[$row['field_key']] = $row;
        }

        // Project overrides
        if ($projectId) {
            $overrides = $this->db->table(self::TABLE)
                ->eq('form_key', $formKey)
                ->eq('project_id', $projectId)
                ->findAll();

            foreach ($overrides as $row) {
                $map[$row['field_key']] = $row;
            }
        }

        return $map;
    }

    /**
     * Visibility map for JS: field_key => bool
     */
    public function getVisibilityMap($formKey, $projectId = null)
    {
        $fields = $this->getFieldsForForm($formKey, $projectId);
        $map = array();
        foreach ($fields as $key => $row) {
            $map[$key] = ((int)$row['is_visible'] === 1);
        }
        return $map;
    }

    public function getAllForForm($formKey, $projectId = null)
    {
        return array_values($this->getFieldsForForm($formKey, $projectId));
    }

    public function setVisibility($formKey, $fieldKey, $isVisible, $projectId = null)
    {
        $existing = $this->db->table(self::TABLE)
            ->eq('form_key', $formKey)
            ->eq('field_key', $fieldKey);

        if ($projectId === null) {
            $existing->isNull('project_id');
        } else {
            $existing->eq('project_id', $projectId);
        }

        $row = $existing->findOne();

        if ($row) {
            return $this->db->table(self::TABLE)
                ->eq('id', $row['id'])
                ->update(array(
                    'is_visible' => $isVisible ? 1 : 0,
                    'updated_at' => time(),
                ));
        }

        // Create project override or global
        return $this->db->table(self::TABLE)->persist(array(
            'project_id'  => $projectId,
            'form_key'    => $formKey,
            'field_key'   => $fieldKey,
            'label'       => $fieldKey,
            'is_visible'  => $isVisible ? 1 : 0,
            'is_required' => 0,
            'position'    => 0,
            'created_at'  => time(),
            'updated_at'  => time(),
        ));
    }

    public function isVisible($formKey, $fieldKey, $projectId = null)
    {
        $map = $this->getVisibilityMap($formKey, $projectId);
        // Default visible if not configured
        return isset($map[$fieldKey]) ? $map[$fieldKey] : true;
    }
}
