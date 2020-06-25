# Note

I'm done with this. If someone wants to maintain it send me a note and I'll transfer the repo. Or just fork it, or whatever.

# File Manager Plugin for GNU social

## Installation

1. Navigate to your `/local/plugins` directory (create it if it doesn't exist)
1. `git clone https://github.com/chimo/gs-fileManager.git FileManager`

## Configuration

Tell `/config.php` to use it:

```
    addPlugin('FileManager');
```

## Usage

A new "File Manager" link should appear in the left-navigation on the "Admin" page.

On the "File Manager" page, you should see a list of files saved on your instance, and their total disk space usage.

![screenshot](https://static.chromic.org/repos/gs-fileManager/screenshot.png)

