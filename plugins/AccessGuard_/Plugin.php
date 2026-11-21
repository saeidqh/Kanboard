<?php

namespace Kanboard\Plugin\AccessGuard;

use Kanboard\Core\Plugin\Base;
use Kanboard\Plugin\AccessGuard\Policy\AccessPolicy;

class Plugin extends Base
{
    public function initialize()
    {
        // ۱. تزریق CSS فقط و فقط برای کاربران با نقش Expert درون پروژه
        $this->template->hook->attach('template:layout:head', 'AccessGuard:guard/head');

        // ۲. هوک امنیتی سرور برای جلوگیری از ایجاد تسک
        $this->hook->on('controller:before', function ($controllerName, $actionName) {
            if ($controllerName === 'TaskCreationController') {
                $userRole = $this->userSession->getRole();
                $userId = $this->userSession->getId();
                $projectId = $this->request->getIntegerParam('project_id');

                if ($projectId > 0) {
                    $projectRole = $this->projectUserRoleModel->getUserRole($projectId, $userId);
                    $policy = AccessPolicy::getPermissions($projectRole ?: $userRole);

                    // اگر ادمین نبود و اجازه ساخت تسک نداشت
                    if ($policy['can_create_task'] === false && $userRole !== 'app-admin') {
                        $this->flash->failure(t('Access denied: Your role does not allow creating tasks.'));
                        $this->response->redirect($this->helper->url->to('BoardViewController', 'show', ['project_id' => $projectId]));
                    }
                }
            }
        });
    }

    public function getPluginName()
    {
        return 'AccessGuard';
    }

    public function getPluginAuthor()
    {
        return 'Saeid';
    }

    public function getPluginVersion()
    {
        return '1.0.0';
    }

    public function getPluginDescription()
    {
        return 'Role-based policy guard with fine-grained views and team reporting.';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/saeidqh/Kanboard';
    }
}
