<?php
if (!defined('GNUSOCIAL')) {
    exit(1);
}

class DeletefileadminpanelAction extends AdminPanelAction
{
    protected $file = null;

    function title() {
        return 'Confirm Deletion';
    }

    function prepare(array $args=array()) {
        parent::prepare($args);

        // TODO: handle cases where we don't have a fileId

        $fileId = $args['file'];

        $this->file = File::getKV('id', $fileId);

        return true;
    }

    function showContent() {
        $src = $this->file->getUrl();

        // TODO: check mimetype
        $this->element('img', array('src' => $src));

        parent::showContent();
    }

    function showForm() {
        $form = new DeletefileForm($this, array('file' => $this->file));

        $form->show();
    }

    function saveSettings() {
        if ($this->arg('yes')) {
            // TODO: handle errors
            $this->file->delete();
        }

        common_redirect(common_local_url('filesadminpanel'), 303);
    }
}

