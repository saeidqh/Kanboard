<?php

namespace Kanboard\Plugin\HierarchicalRoles\Model;

use Kanboard\Core\Base;

class AccessModuleModel extends Base
{
    const TABLE = 'plugin_access_modules';

    const MODULE_ORG_STRUCTURE = 1;
    const SLUG_ORG_STRUCTURE   = 'org_structure';

    const SLUG_PROJECT_SETTINGS = 'project_settings';
    const SLUG_ANALYTICS        = 'analytics';
    const SLUG_BOARD_MANAGEMENT = 'board_management';
    const SLUG_MEMBERS          = 'members';
    const SLUG_CATEGORIES       = 'categories';

    public function getAll()
    {
        return $this->db->table(self::TABLE)->asc('id')->findAll();
    }

    public function getById($id)
    {
        return $this->db->table(self::TABLE)->eq('id', $id)->findOne();
    }

    public function getBySlug($slug)
    {
        return $this->db->table(self::TABLE)->eq('slug', $slug)->findOne();
    }
}
