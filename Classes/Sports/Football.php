<?php

namespace System25\T3sports\Sports;

use Sys25\RnBase\Typo3Wrapper\Service\AbstractService;
use Sys25\RnBase\Utility\Misc;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2021 Rene Nitzsche (rene@system25.de)
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

class Football extends AbstractService implements ISports
{
    /**
     * @return array
     */
    public function getTCAPointSystems()
    {
        return [
            [Misc::translateLLL('LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_competition.point_system_2'), 1],
            [Misc::translateLLL('LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_competition.point_system_3'), 0],
        ];
    }

    public function getTCALabel()
    {
        return 'Football';
    }

    public function isSetBased()
    {
        return false;
    }

    private $matchInfo = null;

    /* (non-PHPdoc)
     * @see ISports::getMatchInfo()
     */
    public function getMatchInfo()
    {
        if (null == $this->matchInfo) {
            $this->matchInfo = tx_rnbase::makeInstance(MatchInfo::class, [
                    MatchInfo::MATCH_TIME => 90,
                    MatchInfo::MATCH_PARTS => 2,
                    MatchInfo::MATCH_EXTRA_TIME => 30,
            ]);
        }

        return $this->matchInfo;
    }
}
