<?php

namespace Kanboard\Plugin\AccessGuard;

use Kanboard\Core\Plugin\Base;
use Kanboard\Plugin\AccessGuard\Policy\AccessPolicy;

class Plugin extends Base
{
    public function initialize()
    {
        // ۱. تزریق تمپلیت کنترل منوها در بالای صفحات
        $this->template->hook->attach('template:layout:head', 'AccessGuard:guard/head');

        // ۲. هوک جلوگیری از ایجاد تسک برای اکسپرت سمت سرور (حتی اگر آدرس را دستی در مرورگر بزند)
        $this->hook->on('controller:before', function ($controllerName, $actionName) {
            if ($controllerName === 'TaskCreationController') {
                $userRole = $this->userSession->getRole();
                $userId = $this->userSession->getId();
                $projectId = $this->request->getIntegerParam('project_id');

                if ($projectId > 0) {
                    $projectRole = $this->projectUserRoleModel->getUserRole($projectId, $userId);
                    $policy = AccessPolicy::getPermissions($projectRole ?: $userRole);

                    // اگر حق ساخت تسک ندارد، پیام اخطار و ریدایرکت
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
