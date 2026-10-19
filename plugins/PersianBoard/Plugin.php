<?php
namespace Kanboard\Plugin\PersianBoard;
use Kanboard\Core\Plugin\Base;

class Plugin extends Base
{
    public function initialize()
    {
        // Load Persian calendar assets regardless of UI language.
        $this->hook->on(
            "template:layout:css",
            array("template" => "plugins/PersianBoard/Assets/css/persian-datepicker.min.css")
        );

        $this->hook->on(
            "template:layout:js",
            array("template" => "plugins/PersianBoard/Assets/js/persian-date.min.js")
        );

        $this->hook->on(
            "template:layout:js",
            array("template" => "plugins/PersianBoard/Assets/js/persian-datepicker.min.js")
        );

        $this->hook->on(
            "template:layout:js",
            array("template" => "plugins/PersianBoard/Assets/js/PersianBoard.js")
        );

        // Keep RTL styling only when the UI language is Persian.
        if ($this->languageModel->getCurrentLanguage() == "fa_IR") {
            $this->hook->on(
                "template:layout:css",
                array("template" => "plugins/PersianBoard/Assets/css/default_rtl.css")
            );

            if (array_key_exists("WeKanboard", $this->pluginLoader->getPlugins())) {
                $this->hook->on(
                    "template:layout:css",
                    array("template" => "plugins/PersianBoard/Assets/css/WeKanboard_rtl.css")
                );
            }
        }
    }


public function onstartup()
{
    if ($this->dateParser->getUserDateFormat() != 'Y/m/d') {
        $this->flash->failure(
            'Persian Board plugin Error - Date Format : '
            . $this->dateParser->getUserDateFormat()
            . ' please change it to: Y/m/d'
        );
    }
}


    public function getPluginName()
    {
        return 'Persian Board';
    }

    public function getPluginDescription()
    {
        return 'Persian calendar support for Kanboard';
    }

    public function getPluginAuthor()
    {
        return 'Hamid Kord';
    }

    public function getPluginVersion()
    {
        return '1.0.1';
    }

    public function getCompatibleVersion()
    {
        return '>1.2.11';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/mer30hamid/PersianBoard';
    }
}
