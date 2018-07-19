<?php

if (!defined('GNUSOCIAL')) {
    exit(1);
}

class FileManagerPlugin extends Plugin
{
    const VERSION = '0.0.1';

    function onRouterInitialized($m)
    {
        $m->connect(
            'panel/files', array(
                'action' => 'filesadminpanel'
            )
        );

        $m->connect(
            'panel/file/:file/delete',
            array('action' => 'deletefileadminpanel'),
            array('file' => '[0-9]+')
        );

        return true;
    }

    function onEndShowStyles($action) {
        $action->cssLink($this->path('css/file-manager.css'));

        return true;
    }

    /**
     * If the plugin's installed, this should be accessible to admins
     */
    function onAdminPanelCheck($name, &$isOK)
    {
        if ($name === 'files' || $name === 'deletefile') {
            $isOK = true;
            return false;
        }

        return true;
    }

    function onEndAdminPanelNav($nav) {
        if (AdminPanelAction::canAdmin('user')) {
            $menu_title = _('File Manager');
            $action_name = $nav->action->trimmed('action');

            $nav->out->menuItem(common_local_url('filesadminpanel'), _m('MENU','File Manager'),
                                 $menu_title, $action_name == 'filesadminpanel', 'files_admin_panel');
        }
    }

    function onPluginVersion(array &$versions)
    {
        $versions[] = array('name' => 'File Manager',
                            'version' => self::VERSION,
                            'author' => 'chimo',
                            'homepage' => 'https://github.com/chimo/gs-fileManager',
                            'description' =>
                            // TRANS: Plugin description.
                            _m('Manage files (attachments)'));
        return true;
    }
}
