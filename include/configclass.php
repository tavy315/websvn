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
// configclass.php
//
// General class for handling configuration options

require_once 'include/ParentPath.php';
require_once 'include/Repository.php';
require_once 'include/WebSvnConfig.php';
require_once 'include/command.php';
require_once 'include/Authentication.php';
require_once 'include/version.php';

// Auxiliary functions used to sort repositories by name/group

// {{{ cmpReps($a, $b)
function cmpReps($a, $b)
{
    // First, sort by group
    $g = strcasecmp($a->group, $b->group);
    if ($g) {
        return $g;
    }

    // Same group? Sort by name
    return strcasecmp($a->name, $b->name);
}

// {{{ cmpGroups($a, $b)
function cmpGroups($a, $b)
{
    $g = strcasecmp($a->group, $b->group);
    if ($g) {
        return $g;
    }

    return 0;
}

// {{{ mergesort(&$array, [$cmp_function])
function mergesort(&$array, $cmp_function = 'strcmp')
{
    // Arrays of size < 2 require no action
    if (count($array) < 2) {
        return;
    }

    // Split the array in half
    $halfway = count($array) / 2;
    $array1 = array_slice($array, 0, $halfway);
    $array2 = array_slice($array, $halfway);

    // Recurse to sort the two halves
    mergesort($array1, $cmp_function);
    mergesort($array2, $cmp_function);

    // If all of $array1 is <= all of $array2, just append them.
    if (call_user_func($cmp_function, end($array1), $array2[0]) < 1) {
        $array = array_merge($array1, $array2);

        return;
    }

    // Merge the two sorted arrays into a single sorted array
    $array = array();
    $array1count = count($array1);
    $array2count = count($array2);
    $ptr1 = 0;
    $ptr2 = 0;
    while ($ptr1 < $array1count && $ptr2 < $array2count) {
        if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1) {
            $array[] = $array1[$ptr1++];
        } else {
            $array[] = $array2[$ptr2++];
        }
    }

    // Merge the remainder
    while ($ptr1 < $array1count) {
        $array[] = $array1[$ptr1++];
    }
    while ($ptr2 < $array2count) {
        $array[] = $array2[$ptr2++];
    }

    return;
}
