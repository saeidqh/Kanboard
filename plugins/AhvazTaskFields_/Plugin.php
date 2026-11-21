<?php

namespace Kanboard\Plugin\AhvazTaskFields;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;
use Kanboard\Core\Security\Role;
use Kanboard\Model\TaskModel;
use Kanboard\Plugin\AhvazTaskFields\Subscriber\TaskSubscriber;

class Plugin extends Base
{
    public function initialize()
    {
        // Same validation path as Title → form re-shown with errors
        $this->container['taskValidator'] = $this->container->factory(function ($c) {
            return new \Kanboard\Plugin\AhvazTaskFields\Validator\TaskValidator($c);
        });

        // IMPORTANT: AccessMap keeps ONE role per controller/method (last write wins).
        // Register the LOWEST role that should access. Higher roles (manager) are allowed via hierarchy.
        $this->applicationAccessMap->add('DepartmentController', '*', Role::APP_USER);
        $this->applicationAccessMap->add('FormFieldController', '*', Role::APP_USER);

        $this->projectAccessMap->add('DepartmentController', '*', Role::PROJECT_MEMBER);
        $this->projectAccessMap->add('FormFieldController', '*', Role::PROJECT_MEMBER);

        // Task form
        $this->template->hook->attach('template:layout:head', 'AhvazTaskFields:layout/head');
        $this->template->hook->attach('template:task:form:first-column', 'AhvazTaskFields:task/form_extra');

        // Assets
        $this->hook->on('template:layout:css', array('template' => 'plugins/AhvazTaskFields/Asset/task_form.css'));
        $this->hook->on('template:layout:js',  array('template' => 'plugins/AhvazTaskFields/Asset/task_form.js'));

        // Org Structure links
        $this->template->setTemplateOverride('HierarchicalRoles:project/roles', 'AhvazTaskFields:org/roles');

        // Routes
        $this->route->addRoute('/project/:project_id/departments',                      'DepartmentController', 'index',  'AhvazTaskFields');
        $this->route->addRoute('/project/:project_id/department/create',                'DepartmentController', 'create', 'AhvazTaskFields');
        $this->route->addRoute('/project/:project_id/department/edit/:department_id',   'DepartmentController', 'edit',   'AhvazTaskFields');
        $this->route->addRoute('/project/:project_id/department/remove/:department_id', 'DepartmentController', 'remove', 'AhvazTaskFields');
        $this->route->addRoute('/project/:project_id/departments/json',                 'DepartmentController', 'json',   'AhvazTaskFields');
        $this->route->addRoute('/project/:project_id/form-fields',                      'FormFieldController', 'index',          'AhvazTaskFields');
        $this->route->addRoute('/project/:project_id/form-fields/toggle',               'FormFieldController', 'toggle',         'AhvazTaskFields');
        $this->route->addRoute('/project/:project_id/form-fields/visibility.json',      'FormFieldController', 'visibilityJson', 'AhvazTaskFields');

        $this->helper->register('taskFieldHelper', '\Kanboard\Plugin\AhvazTaskFields\Helper\TaskFieldHelper');

        $this->dispatcher->addSubscriber(new TaskSubscriber($this->container));
    }

    public function onTaskSave($event)
    {
        $taskId = isset($event['task_id']) ? $event['task_id'] : (isset($event['task']['id']) ? $event['task']['id'] : null);
        if (!$taskId) {
            return;
        }
        $values = $this->request->getValues();
        if (array_key_exists('definition_of_done', $values)) {
            $this->db->table(TaskModel::TABLE)
                ->eq('id', $taskId)
                ->update(array('definition_of_done' => $values['definition_of_done']));
        }
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__ . '/Locale');
    }

    public function getClasses()
    {
        return array(
            'Plugin\AhvazTaskFields\Model' => array(
                'DepartmentModel',
                'FormFieldModel',
            ),
            'Plugin\AhvazTaskFields\Helper' => array(
                'TaskFieldHelper',
            ),
        );
    }

    public function getPluginName() { return 'Ahvaz Task Fields'; }
    public function getPluginDescription() { return 'DoD + Reference + Departments + Form field visibility'; }
    public function getPluginAuthor() { return 'Ahvaz005 Team'; }
    public function getPluginVersion() { return '1.2.3'; }
    public function getCompatibleVersion() { return '>=1.2.20'; }
}
