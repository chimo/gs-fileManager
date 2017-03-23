<?php
if (!defined('GNUSOCIAL')) {
    exit(1);
}

class FilesadminpanelAction extends AdminPanelAction
{
    function title() {
        return 'File Manager';
    }

    function prepare(array $args=array()) {
        parent::prepare($args);

        $page = isset($args['page']) ? intval($args['page']) : 1;

        if ($page <= 0) {
            $page = 1;
        }

        $this->page = $page;
        $this->args = $args;

        $offset = ($this->page - 1) * 10;
        $limit = 10;

        $file = new File();

        $file->whereAdd('filename IS NOT NULL'); // Only local files
        $file->limit($offset, $limit);

        $this->files = $file->fetchAll();

        $overview = new File();
        $overview->selectAdd();
        $overview->selectAdd('count(*) as total_files');
        $overview->selectAdd('sum(size) as total_size');

        // TODO: handle cases where this fails for wtv reason
        if ($overview->find()) {
            $overview->fetch();

            $this->total_files = $overview->total_files;
            $this->total_size = $overview->total_size;
        }

        return true;
    }

    function showOverview() {
        $bytes = $this->formatBytes($this->total_size);
        $nb_files = number_format($this->total_files);

        $this->element('p', null, "There are $nb_files files saved on your instance, using $bytes of disk space.");
    }

    function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    function showContent() {
        if ($this->page === 1) {
            $this->showOverview();
        }

        if (sizeof($this->files) === 0) {
            $this->element('p', null, 'No files found.'); // TODO: Better msg

            return true;
        }

        $this->elementStart('table', array('class' => 'chr-files'));
        $this->elementStart('thead');
        $this->elementStart('tr');
        $this->element('th', null, 'File');
        $this->element('th', null, 'Size');
        $this->element('th', null, 'Referred by');
        $this->element('th', null, 'Delete');
        $this->elementEnd('tr');
        $this->elementEnd('thead');

        $this->elementStart('tbody');

        foreach($this->files as $file) {
            $deleteurl = common_local_url('deletefileadminpanel', array('file' => $file->getID()));

            $this->elementStart('tr');

            // Link to original file
            $this->elementStart('td');
            $this->elementStart('a', array('href' => $file->getUrl()));
            if ($file->hasThumbnail()) {
                $thumbnail = $file->getThumbnail();
                $this->element('img', $thumbnail->getHtmlAttrs(array('class' => 'file-thumbnail')));
            } else {
                $this->text($file->getFilename());
            }
            $this->elementEnd('a');
            $this->elementEnd('td');

            // Size
            $this->element('td', array('class' => 'chr-files__size'), $this->formatBytes($file->getSize()));

            // Referred by
            $noticeIds = File_to_post::getNoticeIDsByFile($file);
            $this->elementStart('td');

            if (sizeof($noticeIds >= 0)) {
                $this->elementStart('figure', array('class' => 'chr-files__referrers'));
                $this->element('figcaption', null, 'Notice:');
                $this->elementStart('ul', array('class' => 'chr-files__referrers-list'));

                foreach($noticeIds as $noticeId) {
                    $notice_url = common_local_url('shownotice',
                        array('notice' => $noticeId), null, null, false);

                    $this->elementStart('li');
                    $this->element('a', array('href' => $notice_url), $noticeId);
                    $this->elementEnd('li');
                }

                $this->elementEnd('ul');
                $this->elementEnd('figure');
            }

            $this->elementEnd('td');

            // Delete
            $this->elementStart('td');
            $this->element('a', array('class' => 'file-delete', 'href' => $deleteurl), 'Delete');
            $this->elementEnd('td');

            $this->elementEnd('tr');
        }

        $this->elementEnd('tbody');
        $this->elementEnd('table');

        $this->showPagination($this->page);
    }

    function showPagination($current_page) {
        $have_before = false;
        $have_after = false;

        if ($current_page > 1) {
            $have_before = true;
        }

        // FIXME: This might give us an empty last page if the total
        //        amount of files is a multiple of 10
        if (sizeof($this->files) === 10) {
            $have_after = true;
        }

        $this->pagination($have_before, $have_after, $current_page, 'filesadminpanel');
    }

    /**
     * This is a copy of Action::pagination because the 'Before'/'After' labels
     * don't make sense and can't be overwritten...
     */
    function pagination($have_before, $have_after, $page, $action, $args=null)
    {
        // Does a little before-after block for next/prev page
        if ($have_before || $have_after) {
            $this->elementStart('ul', array('class' => 'nav',
                                            'id' => 'pagination'));
        }
        if ($have_before) {
            $pargs   = array('page' => $page-1);
            $this->elementStart('li', array('class' => 'nav_prev'));
            $this->element('a', array('href' => common_local_url($action, $args, $pargs),
                                      'rel' => 'prev'),
                           // TRANS: Pagination message to go to a page displaying information more in the
                           // TRANS: present than the currently displayed information.
                           _('Previous'));
            $this->elementEnd('li');
        }
        if ($have_after) {
            $pargs   = array('page' => $page + 1);
            $this->elementStart('li', array('class' => 'nav_next'));
            $this->element('a', array('href' => common_local_url($action, $args, $pargs),
                                      'rel' => 'next'),
                           // TRANS: Pagination message to go to a page displaying information more in the
                           // TRANS: past than the currently displayed information.
                           _('Next'));
            $this->elementEnd('li');
        }
        if ($have_before || $have_after) {
            $this->elementEnd('ul');
        }
    }
}

