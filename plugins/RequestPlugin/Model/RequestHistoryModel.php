<?php

namespace Kanboard\Plugin\RequestPlugin\Model;

use Kanboard\Core\Base;

class RequestHistoryModel extends Base
{
    const TABLE = 'request_history';

    public function add($requestId, $userId, $action, $fromUserId = null, $toUserId = null, $note = null)
    {
        return $this->db->table(self::TABLE)->persist(array(
            'request_id' => (int) $requestId,
            'user_id' => (int) $userId,
            'action' => $action,
            'from_user_id' => $fromUserId ? (int) $fromUserId : null,
            'to_user_id' => $toUserId ? (int) $toUserId : null,
            'note' => $note,
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function getAll($requestId)
    {
        $rows = $this->db->table(self::TABLE)
            ->eq('request_id', $requestId)
            ->asc('created_at')
            ->findAll();

        foreach ($rows as &$r) {
            $u = $this->userModel->getById($r['user_id']);
            $r['user_name'] = $u ? ($u['name'] ?: $u['username']) : '?';

            if (!empty($r['from_user_id'])) {
                $fu = $this->userModel->getById($r['from_user_id']);
                $r['from_user_name'] = $fu ? ($fu['name'] ?: $fu['username']) : '?';
            }
            if (!empty($r['to_user_id'])) {
                $tu = $this->userModel->getById($r['to_user_id']);
                $r['to_user_name'] = $tu ? ($tu['name'] ?: $tu['username']) : '?';
            }
        }

        return $rows;
    }
}
