# websvn
PHP based web interface of Subversion repositories

WebSVN offers a view onto your subversion repositories that's been designed to reflect the Subversion methodology. You can view the log of any file or directory and see a list of all the files changed, added or deleted in any given revision. You can also view compare two versions of a file so as to see exactly what was changed in a particular revision.

Since it's written using PHP, WebSVN is very portable and easy to install.

For more information about WebSVN visit www.websvn.info.

## Primary Features
* Easy-to-use interface, simple to install / configure
* Supports multiple repositories, local or remote
* Optional path-based restriction of privileges
* Colourisation of file listings; MIME type support
* Blame (annotation) view of file authorship
* Comparing revisions of files / directories
* Revision and log message browsing / searching
* RSS feed support for watching any resource
* Download of files and folders
* Customisable templating system
* Multiple languages and on-demand switching

## Requirements

WebSVN uses a command-line SVN client for accessing repositories. Depending on the WebSVN version used, different versions of SVN are required:

| WebSVN version | SVN version                               |
| -------------: | :---------------------------------------- |
|          2.3.x | 1.4 or higher (usage of "@PEG"-revision)  |
|          2.2.x | 1.4 or higher (usage of "@PEG"-revision)  |
|          2.1.0 | 1.2 or higher (usage of "svn list --xml") |
|         <= 2.0 | any (?)                                   |

WebSVN currently runs under both PHP 4 and PHP 5.
