<?php
// WebSVN - Subversion repository viewing via the web using PHP
// Copyright (C) 2004-2006 Tim Armes
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA
//
// --
//
// svn-look.php
//
// Svn bindings
//
// These binding currently use the svn command line to achieve their goal.	Once a proper
// SWIG binding has been produced for PHP, there'll be an option to use that instead.

require_once 'include/utils.php';
require_once 'include/SVNListEntry.php';
require_once 'include/SVNLog.php';
require_once 'include/SVNLogEntry.php';
require_once 'include/SVNMod.php';
require_once 'include/SVNRepository.php';

$debugXML = false;

function SVNLogEntry_compare($a, $b)
{
    return strnatcasecmp($a->path, $b->path);
}

$curTag = '';

$curInfo = 0;

function infoStartElement($parser, $name, $attrs)
{
    global $curInfo, $curTag, $debugXML;

    switch ($name) {
        case 'INFO':
            if ($debugXML) {
                print 'Starting info' . "\n";
            }
            break;

        case 'ENTRY':
            if ($debugXML) {
                print 'Creating info entry' . "\n";
            }

            if (count($attrs)) {
                while (list($k, $v) = each($attrs)) {
                    switch ($k) {
                        case 'KIND':
                            if ($debugXML) {
                                print 'Kind ' . $v . "\n";
                            }
                            $curInfo->isdir = ($v == 'dir');
                            break;
                        case 'REVISION':
                            if ($debugXML) {
                                print 'Revision ' . $v . "\n";
                            }
                            $curInfo->rev = $v;
                            break;
                    }
                }
            }
            break;

        default:
            $curTag = $name;
            break;
    }
}

// {{{ infoEndElement
function infoEndElement($parser, $name)
{
    global $curInfo, $debugXML, $curTag;

    switch ($name) {
        case 'ENTRY':
            if ($debugXML) {
                print 'Ending info entry' . "\n";
            }
            if ($curInfo->isdir) {
                $curInfo->path .= '/';
            }
            break;
    }

    $curTag = '';
}

// {{{ infoCharacterData
function infoCharacterData($parser, $data)
{
    global $curInfo, $curTag, $debugXML;

    switch ($curTag) {
        case 'URL':
            if ($debugXML) {
                print 'Url: ' . $data . "\n";
            }
            $curInfo->path = $data;
            break;

        case 'ROOT':
            if ($debugXML) {
                print 'Root: ' . $data . "\n";
            }
            $curInfo->path = urldecode(substr($curInfo->path, strlen($data)));
            break;
    }
}

$curList = 0;

// {{{ listStartElement
function listStartElement($parser, $name, $attrs)
{
    global $curList, $curTag, $debugXML;

    switch ($name) {
        case 'LIST':
            if ($debugXML) {
                print 'Starting list' . "\n";
            }

            if (count($attrs)) {
                while (list($k, $v) = each($attrs)) {
                    switch ($k) {
                        case 'PATH':
                            if ($debugXML) {
                                print 'Path ' . $v . "\n";
                            }
                            $curList->path = $v;
                            break;
                    }
                }
            }
            break;

        case 'ENTRY':
            if ($debugXML) {
                print 'Creating new entry' . "\n";
            }
            $curList->curEntry = new SVNListEntry;

            if (count($attrs)) {
                while (list($k, $v) = each($attrs)) {
                    switch ($k) {
                        case 'KIND':
                            if ($debugXML) {
                                print 'Kind ' . $v . "\n";
                            }
                            $curList->curEntry->isdir = ($v == 'dir');
                            break;
                    }
                }
            }
            break;

        case 'COMMIT':
            if ($debugXML) {
                print 'Commit' . "\n";
            }

            if (count($attrs)) {
                while (list($k, $v) = each($attrs)) {
                    switch ($k) {
                        case 'REVISION':
                            if ($debugXML) {
                                print 'Revision ' . $v . "\n";
                            }
                            $curList->curEntry->rev = $v;
                            break;
                    }
                }
            }
            break;

        default:
            $curTag = $name;
            break;
    }
}

// {{{ listEndElement
function listEndElement($parser, $name)
{
    global $curList, $debugXML, $curTag;

    switch ($name) {
        case 'ENTRY':
            if ($debugXML) {
                print 'Ending new list entry' . "\n";
            }
            if ($curList->curEntry->isdir) {
                $curList->curEntry->file .= '/';
            }
            $curList->entries[] = $curList->curEntry;
            $curList->curEntry = null;
            break;
    }

    $curTag = '';
}

// {{{ listCharacterData
function listCharacterData($parser, $data)
{
    global $curList, $curTag, $debugXML;

    switch ($curTag) {
        case 'NAME':
            if ($debugXML) {
                print 'Name: ' . $data . "\n";
            }
            if ($data === false || $data === '') {
                return;
            }
            $curList->curEntry->file .= $data;
            break;

        case 'AUTHOR':
            if ($debugXML) {
                print 'Author: ' . $data . "\n";
            }
            if ($data === false || $data === '') {
                return;
            }
            if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
                $data = mb_convert_encoding($data, 'UTF-8', mb_detect_encoding($data));
            }
            $curList->curEntry->author .= $data;
            break;

        case 'DATE':
            if ($debugXML) {
                print 'Date: ' . $data . "\n";
            }
            $data = trim($data);
            if ($data === false || $data === '') {
                return;
            }

            $committime = parseSvnTimestamp($data);
            $curList->curEntry->committime = $committime;
            $curList->curEntry->date = strftime('%Y-%m-%d %H:%M:%S', $committime);
            $curList->curEntry->age = datetimeFormatDuration(max(time() - $committime, 0), true, true);
            break;
    }
}

$curLog = 0;

function logStartElement($parser, $name, $attrs)
{
    global $curLog, $curTag, $debugXML;

    switch ($name) {
        case 'LOGENTRY':
            if ($debugXML) {
                print 'Creating new log entry' . "\n";
            }
            $curLog->curEntry = new SVNLogEntry;
            $curLog->curEntry->mods = array();

            $curLog->curEntry->path = $curLog->path;

            if (count($attrs)) {
                while (list($k, $v) = each($attrs)) {
                    switch ($k) {
                        case 'REVISION':
                            if ($debugXML) {
                                print 'Revision ' . $v . "\n";
                            }
                            $curLog->curEntry->rev = $v;
                            break;
                    }
                }
            }
            break;

        case 'PATH':
            if ($debugXML) {
                print 'Creating new path' . "\n";
            }
            $curLog->curEntry->curMod = new SVNMod;

            if (count($attrs)) {
                while (list($k, $v) = each($attrs)) {
                    switch ($k) {
                        case 'ACTION':
                            if ($debugXML) {
                                print 'Action ' . $v . "\n";
                            }
                            $curLog->curEntry->curMod->action = $v;
                            break;

                        case 'COPYFROM-PATH':
                            if ($debugXML) {
                                print 'Copy from: ' . $v . "\n";
                            }
                            $curLog->curEntry->curMod->copyfrom = $v;
                            break;

                        case 'COPYFROM-REV':
                            $curLog->curEntry->curMod->copyrev = $v;
                            break;

                        case 'KIND':
                            if ($debugXML) {
                                print 'Kind ' . $v . "\n";
                            }
                            $curLog->curEntry->curMod->isdir = ($v == 'dir');
                            break;
                    }
                }
            }

            $curTag = $name;
            break;

        default:
            $curTag = $name;
            break;
    }
}

function logEndElement($parser, $name)
{
    global $curLog, $debugXML, $curTag;

    switch ($name) {
        case 'LOGENTRY':
            if ($debugXML) {
                print 'Ending new log entry' . "\n";
            }
            $curLog->entries[] = $curLog->curEntry;
            break;

        case 'PATH':
            if ($debugXML) {
                print 'Ending path' . "\n";
            }
            $curLog->curEntry->mods[] = $curLog->curEntry->curMod;
            break;

        case 'MSG':
            $curLog->curEntry->msg = trim($curLog->curEntry->msg);
            if ($debugXML) {
                print 'Completed msg = "' . $curLog->curEntry->msg . '"' . "\n";
            }
            break;
    }

    $curTag = '';
}

function logCharacterData($parser, $data)
{
    global $curLog, $curTag, $debugXML;

    switch ($curTag) {
        case 'AUTHOR':
            if ($debugXML) {
                print 'Author: ' . $data . "\n";
            }
            if ($data === false || $data === '') {
                return;
            }
            if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
                $data = mb_convert_encoding($data, 'UTF-8', mb_detect_encoding($data));
            }
            $curLog->curEntry->author .= $data;
            break;

        case 'DATE':
            if ($debugXML) {
                print 'Date: ' . $data . "\n";
            }
            $data = trim($data);
            if ($data === false || $data === '') {
                return;
            }

            $committime = parseSvnTimestamp($data);
            $curLog->curEntry->committime = $committime;
            $curLog->curEntry->date = strftime('%Y-%m-%d %H:%M:%S', $committime);
            $curLog->curEntry->age = datetimeFormatDuration(max(time() - $committime, 0), true, true);
            break;

        case 'MSG':
            if ($debugXML) {
                print 'Msg: ' . $data . "\n";
            }
            if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
                $data = mb_convert_encoding($data, 'UTF-8', mb_detect_encoding($data));
            }
            $curLog->curEntry->msg .= $data;
            break;

        case 'PATH':
            if ($debugXML) {
                print 'Path name: ' . $data . "\n";
            }
            $data = trim($data);
            if ($data === false || $data === '') {
                return;
            }

            $curLog->curEntry->curMod->path .= $data;

            // The XML returned when a file is renamed/branched in inconsistent.
            // In the case of a branch, the path doesn't include the leafname.
            // In the case of a rename, it does.	Ludicrous.

            if (!empty($curLog->path)) {
                $pos = strrpos($curLog->path, '/');
                $curpath = substr($curLog->path, 0, $pos);
                $leafname = substr($curLog->path, $pos + 1);
            } else {
                $curpath = '';
                $leafname = '';
            }

            $curMod = $curLog->curEntry->curMod;
            if ($curMod->action == 'A') {
                if ($debugXML) {
                    print 'Examining added path "' . $curMod->copyfrom . '" - Current path = "' . $curpath . '", leafname = "' . $leafname . '"' . "\n";
                }
                if ($data == $curLog->path) {
                    // For directories and renames
                    $curLog->path = $curMod->copyfrom;
                } else {
                    if ($data == $curpath || $data == $curpath . '/') {
                        // Logs of files that have moved due to branching
                        $curLog->path = $curMod->copyfrom . '/' . $leafname;
                    } else {
                        $curLog->path = str_replace($curMod->path, $curMod->copyfrom, $curLog->path);
                    }
                }
                if ($debugXML) {
                    print 'New path for comparison: "' . $curLog->path . '"' . "\n";
                }
            }
            break;
    }
}

// Function returns true if the give entry in a directory tree is at the top level
function _topLevel($entry)
{
    // To be at top level, there must be one space before the entry
    return (strlen($entry) > 1 && $entry{0} == ' ' && $entry{1} != ' ');
}

// Function to sort two given directory entries.
// Directories go at the top if config option alphabetic is not set
function _listSort($e1, $e2)
{
    global $config;

    $file1 = $e1->file;
    $file2 = $e2->file;
    $isDir1 = ($file1{strlen($file1) - 1} == '/');
    $isDir2 = ($file2{strlen($file2) - 1} == '/');

    if (!$config->isAlphabeticOrder()) {
        if ($isDir1 && !$isDir2) {
            return -1;
        }
        if ($isDir2 && !$isDir1) {
            return 1;
        }
    }

    if ($isDir1) {
        $file1 = substr($file1, 0, -1);
    }
    if ($isDir2) {
        $file2 = substr($file2, 0, -1);
    }

    return strnatcasecmp($file1, $file2);
}

// {{{ encodePath
// Function to encode a URL without encoding the /'s
function encodePath($uri)
{
    global $config;

    $uri = str_replace(DIRECTORY_SEPARATOR, '/', $uri);
    if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
        $uri = mb_convert_encoding($uri, 'UTF-8', mb_detect_encoding($uri));
    }

    $parts = explode('/', $uri);
    $partsCount = count($parts);
    for ($i = 0; $i < $partsCount; $i++) {
        // do not urlencode the 'svn+ssh://' part!
        if ($i != 0 || $parts[$i] != 'svn+ssh:') {
            $parts[$i] = rawurlencode($parts[$i]);
        }
    }

    $uri = implode('/', $parts);

    // Quick hack. Subversion seems to have a bug surrounding the use of %3A instead of :
    $uri = str_replace('%3A', ':', $uri);

    // Correct for Window share names
    if ($config->serverIsWindows) {
        if (substr($uri, 0, 2) == '//') {
            $uri = '\\' . substr($uri, 2, strlen($uri));
        }

        if (substr($uri, 0, 10) == 'file://///') {
            $uri = 'file:///\\' . substr($uri, 10, strlen($uri));
        }
    }

    return $uri;
}

function _equalPart($str1, $str2)
{
    $len1 = strlen($str1);
    $len2 = strlen($str2);
    $i = 0;
    while ($i < $len1 && $i < $len2) {
        if (strcmp($str1{$i}, $str2{$i}) != 0) {
            break;
        }
        $i++;
    }
    if ($i == 0) {
        return '';
    }

    return substr($str1, 0, $i);
}

// Initialize SVN version information by parsing from command-line output.
$cmd = $config->getSvnCommand();
$cmd = str_replace(array( '--non-interactive', '--trust-server-cert' ), array( '', '' ), $cmd);
$cmd .= ' --version';

$ret = runCommand($cmd, false);

if (preg_match('~([0-9]+)\.([0-9]+)\.([0-9]+)~', $ret[0], $matches)) {
    $config->setSubversionVersion($matches[0]);
    $config->setSubversionMajorVersion($matches[1]);
    $config->setSubversionMinorVersion($matches[2]);
}
