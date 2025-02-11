<?php

namespace System25\T3sports\Model;

use Sys25\RnBase\Domain\Model\DataModel;
use Sys25\RnBase\Utility\Strings;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2021 Rene Nitzsche (rene@system25.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Model for a match set.
 */
class Set extends DataModel
{
    protected $p1;

    protected $p2;

    protected $set;

    public function __construct($set, $p1 = 0, $p2 = 0)
    {
        $this->setResult($set, $p1, $p2);
    }

    public function setResult($set, $p1, $p2)
    {
        $this->set = $set;
        $this->p1 = $p1;
        $this->p2 = $p2;
        $this->setProperty([
            'set' => $this->set,
            'pointshome' => $this->p1,
            'pointsguest' => $this->p2,
        ]);
    }

    public function getSet()
    {
        return $this->set;
    }

    public function getPointsHome()
    {
        return $this->p1;
    }

    public function getPointsGuest()
    {
        return $this->p2;
    }

    public static function buildFromString($sets)
    {
        if (!$sets) {
            return false;
        }
        $sets = preg_split("/[\s]*[;,|][\s]*/", $sets);
        $ret = [];
        foreach ($sets as $idx => $setStr) {
            list($p1, $p2) = Strings::intExplode(':', $setStr);
            $ret[] = tx_rnbase::makeInstance(Set::class, $idx + 1, $p1, $p2);
        }

        return $ret;
    }

    public function getColumnNames()
    {
        return array_keys($this->getProperty());
    }
}
