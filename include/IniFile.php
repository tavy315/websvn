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
// accessfile.php
//
// Read a .ini style file

class IniFile
{
    private $sections;

    public function __construct()
    {
        $this->sections = array();
    }

    public function readIniFile($name)
    {
        // does not use parse_ini_file function since php 5.3 does not support comment lines starting with #
        $contents = file($name);
        $currentSection = '';
        $currentKey = '';

        foreach ($contents as $line) {
            $line = rtrim($line);
            $str = ltrim($line);
            if (empty($str)) {
                continue;
            }

            // @todo remove ' in the next major release to be in line with the svn book
            if ($str{0} == '#' || $str{0} == "'") {
                continue;
            }

            if ($str != $line && !empty($currentSection) && !empty($currentKey)) {
                // line starts with whitespace
                $this->sections[$currentSection][$currentKey] .= strtolower($str);
            } else {
                if ($str{0} == '[' && $str{strlen($str) - 1} == ']') {
                    $currentSection = strtolower(substr($str, 1, strlen($str) - 2));
                } else {
                    if (!empty($currentSection)) {
                        if (!isset($this->sections[$currentSection])) {
                            $this->sections[$currentSection] = array();
                        }

                        list($key, $val) = explode('=', $str, 2);

                        $key = strtolower(trim($key));
                        $currentKey = $key;
                        if ($currentSection == 'groups' && isset($this->sections[$currentSection][$key])) {
                            $this->sections[$currentSection][$key] .= ',' . strtolower(trim($val));
                        } else {
                            $this->sections[$currentSection][$key] = strtolower(trim($val));
                        }
                    }
                }
            }
        }
    }

    public function &getSections()
    {
        return $this->sections;
    }

    public function getValues($section)
    {
        return @$this->sections[strtolower($section)];
    }

    public function getValue($section, $key)
    {
        return @$this->sections[strtolower($section)][strtolower($key)];
    }
}
