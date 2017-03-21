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

        $this->page = isset($args['page']) ? $args['page'] + 0 : 1;
        $this->args = $args;

        $offset = ($this->page - 1) * 10;
        $limit = 10;

        $file = new File();

        $file->whereAdd('filename IS NOT NULL'); // Only local files
        $file->limit($offset, $limit);

        $this->files = $file->fetchAll();

        return true;
    }

    function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    function showContent() {
        if (sizeof($this->files) === 0) {
            $this->element('p', null, 'No files found.'); // TODO: Better msg

            return true;
        }

        $this->elementStart('table', array('class' => 'chr-files'));
        $this->elementStart('thead');
        $this->elementStart('tr');
        $this->element('th', null, 'Thumbnail');
        $this->element('th', null, 'Size');
        $this->element('th', null, 'Original');
        $this->element('th', null, 'Delete');
        $this->elementEnd('tr');
        $this->elementEnd('thead');

        $this->elementStart('tbody');

        foreach($this->files as $file) {
            $deleteurl = common_local_url('deletefileadminpanel', array('file' => $file->getID()));

            $this->elementStart('tr');

            // Thumbnail
            $this->elementStart('td');
            $thumbnail = $file->getThumbnail(); // FIXME: Hanlde file types w/ no thumbnails
            $this->element('img', $thumbnail->getHtmlAttrs(array('class' => 'file-thumbnail')));
            $this->elementEnd('td');

            // Size
            $this->element('td', null, $this->formatBytes($file->getSize()));

            // Link to original file
            $this->elementStart('td');
            $this->element('a', array('href' => $file->getUrl()), 'Link');
            $this->elementEnd('td');

            // Delete
            $this->elementStart('td');
            $this->element('a', array('class' => 'file-delete', 'href' => $deleteurl), 'Delete');
            $this->elementEnd('td');

            $this->elementEnd('tr');
        }

        $this->elementEnd('tbody');
        $this->elementEnd('table');
    }
}

