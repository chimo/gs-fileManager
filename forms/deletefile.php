<?php
if (!defined('GNUSOCIAL')) {
    exit(1);
}

class DeletefileForm extends AdminForm
{
    function __construct(HTMLOutputter $out=null, array $formOpts=array())
    {
        if (!array_key_exists('file', $formOpts) || !$formOpts['file'] instanceof File) {
            throw new ServerException('No file provided to DeletefileForm');
        }

        parent::__construct($out);

        $this->file = $formOpts['file'];
    }

    function id() {
        return 'form_deletefile_admin_panel';
    }

    function formClass() {
        return 'form_settings';
    }

    function action() {
        $deleteurl = common_local_url('deletefileadminpanel', array('file' => $this->file->getID()));

        return $deleteurl;
    }

    function formData() {
        // TODO
    }

    function formActions() {
        $this->out->submit('form_action-no',
                      // TRANS: Button label on the delete notice form.
                      _m('BUTTON','No'),
                      'submit form_action-primary',
                      'no',
                      // TRANS: Submit button title for 'No' when deleting a notice.
                      _('Do not delete this file.'));

        $this->out->submit('form_action-yes',
                      // TRANS: Button label on the delete notice form.
                      _m('BUTTON','Yes'),
                      'submit form_action-secondary',
                      'yes',
                      // TRANS: Submit button title for 'Yes' when deleting a notice.
                      _('Delete this file.'));
    }
}

