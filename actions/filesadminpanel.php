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

        $this->pluginDir = common_root_url() .
            str_replace(array(INSTALLDIR . '/', '/actions'), '', dirname(__FILE__));

        $this->page = $this->int('page', 1, null, 1);
        $this->sortBy = $this->trimmed('sort-by', 'id');
        $this->sortDir = $this->trimmed('sort-dir', 'asc');

        $sortable_columns = ['id', 'size'];

        // URL param sanitation: make sure 'sort-by' is one of the
        // columns we're expecting. If not, default sorting by 'id'
        if (!in_array($this->sortBy, $sortable_columns)) {
            $this->sortBy = 'id';
        }

        // URL param sanitation: make sure 'sort-dir' is either 'asc'
        // or 'desc'. Default to 'asc' if neither.
        if ($this->sortDir !== 'desc') {
            $this->sortDir = 'asc';
        }

        $offset = ($this->page - 1) * 10;
        $limit = 10;

        $this->files = $this->getFiles($offset, $limit);
        $this->overview = $this->getOverview();

        return true;
    }

    function getFiles($offset, $limit) {
        $file = new File();

        $file->whereAdd('filename IS NOT NULL'); // Only local files
        $file->orderBy($this->sortBy . ' ' . $this->sortDir);
        $file->limit($offset, $limit);

        return $file->fetchAll();
    }

    function getOverview() {
        $overview = new File();
        $overview->selectAdd();
        $overview->selectAdd('count(*) as total_files');
        $overview->selectAdd('sum(size) as total_size');
        $overview->whereAdd('filename IS NOT NULL');

        // TODO: handle cases where this fails for wtv reason
        if ($overview->find()) {
            $overview->fetch();
        }

        return array(
            'total_files' => $overview->total_files,
            'total_size' => $overview->total_size
        );
    }

    function showOverview() {
        $bytes = $this->formatBytes($this->overview['total_size']);
        $nb_files = number_format($this->overview['total_files']);

        $this->element('p', null, "There are $nb_files files saved on your instance, using $bytes of disk space.");
    }

    function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    function showSort($by, $direction) {
        $klass = 'sort-icon';
        $href = '?sort-by=' . $by . '&sort-dir=' . $direction . '&page=' . $this->page;
        $img_src = $this->pluginDir . '/images/sort-' . $direction . '.png';

        if ($this->sortBy === $by && $this->sortDir === $direction) {
            $klass .= ' active';
        }

        $this->elementStart('a', array('class' => $klass, 'href' => $href));
        $this->element('img', array('src' => $img_src));
        $this->elementEnd('a');
    }

    function showContent() {
        if ($this->page === 1) {
            $this->showOverview();
        }

        if (count($this->files) === 0) {
            $this->element('p', null, 'No files found.'); // TODO: Better msg

            return true;
        }

        $this->elementStart('table', array('class' => 'chr-files'));
        $this->elementStart('thead');
        $this->elementStart('tr');

        $this->elementStart('th');
        $this->text('File');
        $this->showSort('id', 'asc');
        $this->showSort('id', 'desc');
        $this->elementEnd('th');

        $this->elementStart('th');
        $this->text('Size');
        $this->showSort('size', 'asc');
        $this->showSort('size', 'desc');
        $this->elementEnd('th');

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
            $size = $file->getSize();

            if ($size === 0) {
                $size = 'Information not available';
            } else {
                $size = $this->formatBytes($size);
            }

            $this->element('td', array('class' => 'chr-files__size'), $size);

            // Referred by
            $noticeIds = array();

            try {
                $noticeIds = File_to_post::getNoticeIDsByFile($file);
            } catch (NoResultException $e) {
                // Ignore
            }

            $this->elementStart('td');

            if (count($noticeIds) > 0) {
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
            } else {
                $this->text('No one');
            }

            $this->elementEnd('td');

            // Delete
            $this->elementStart('td');
            $this->elementStart('div', array('class' => 'notice-options'));
            $this->element('a',
                array('class' => 'notice_delete', 'href' => $deleteurl), 'Delete');
            $this->elementEnd('div');
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
        if (count($this->files) === 10) {
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
            $pargs = array(
                'page' => $page - 1,
                'sort-by' => $this->sortBy,
                'sort-dir' => $this->sortDir
            );
            $this->elementStart('li', array('class' => 'nav_prev'));
            $this->element('a', array('href' => common_local_url($action, $args, $pargs),
                                      'rel' => 'prev'),
                           // TRANS: Pagination message to go to a page displaying information more in the
                           // TRANS: present than the currently displayed information.
                           _('Previous'));
            $this->elementEnd('li');
        }
        if ($have_after) {
            $pargs   = array(
                'page' => $page + 1,
                'sort-by' => $this->sortBy,
                'sort-dir' => $this->sortDir
            );
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

