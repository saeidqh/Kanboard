<?php

namespace Kanboard\Plugin\RequestPlugin\Model;

use Kanboard\Core\Base;

class RequestCommentModel extends Base
{
    const TABLE = 'request_comments';

    public function create($requestId, $userId, $comment)
    {
        $id = $this->db->table(self::TABLE)->persist(array(
            'request_id' => (int) $requestId,
            'user_id' => (int) $userId,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
        ));

        if ($id) {
            $this->db->table(RequestModel::TABLE)->eq('id', $requestId)->update(array(
                'updated_at' => date('Y-m-d H:i:s'),
            ));
            $this->requestHistoryModel->add($requestId, $userId, 'commented', null, null, mb_substr($comment, 0, 120));
        }

        return $id;
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
        }

        return $rows;
    }
}
