<?php

namespace Kanboard\Plugin\AhvazTaskFields\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Core\Controller\AccessForbiddenException;

class FormFieldController extends BaseController
{
    public function index()
    {
        $project = $this->getProject();
        $this->checkAccess($project['id']);

        $formKey = $this->request->getStringParam('form_key', 'task_create');
        $fields = $this->formFieldModel->getAllForForm($formKey, $project['id']);

        $this->response->html($this->helper->layout->project('AhvazTaskFields:form_field/index', array(
            'project'  => $project,
            'fields'   => $fields,
            'form_key' => $formKey,
            'title'    => t('Form Fields') . ' — ' . $project['name'],
        )));
    }

    public function toggle()
    {
        $project = $this->getProject();
        $this->checkAccess($project['id']);
        $this->checkCSRFParam();

        $formKey  = $this->request->getStringParam('form_key', 'task_create');
        $fieldKey = $this->request->getStringParam('field_key');
        $visible  = $this->request->getIntegerParam('visible', 1);

        if ($fieldKey !== '') {
            $this->formFieldModel->setVisibility($formKey, $fieldKey, $visible, $project['id']);
            $this->flash->success(t('Field visibility updated.'));
        }

        $this->response->redirect($this->helper->url->to('FormFieldController', 'index', array(
            'plugin'     => 'AhvazTaskFields',
            'project_id' => $project['id'],
            'form_key'   => $formKey,
        )));
    }

    public function visibilityJson()
    {
        $project = $this->getProject();
        $formKey = $this->request->getStringParam('form_key', 'task_create');
        $map = $this->formFieldModel->getVisibilityMap($formKey, $project['id']);
        $this->response->json(array('visibility' => $map));
    }

    protected function checkAccess($projectId)
    {
        if ($this->userSession->isAdmin()) {
            return;
        }
        try {
            if (isset($this->helper->hierarchicalRoleHelper) && is_object($this->helper->hierarchicalRoleHelper)
                && method_exists($this->helper->hierarchicalRoleHelper, 'canAccessModule')) {
                if ($this->helper->hierarchicalRoleHelper->canAccessModule($projectId, 'org_structure')
                    || $this->helper->hierarchicalRoleHelper->canAccessModule($projectId, 'departments')) {
                    return;
                }
            }
        } catch (\Exception $e) {
        }
        $role = $this->projectUserRoleModel->getUserRole($projectId, $this->userSession->getId());
        if ($role === 'project-manager') {
            return;
        }
        throw new AccessForbiddenException();
    }
}
