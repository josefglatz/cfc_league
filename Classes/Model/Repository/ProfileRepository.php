<?php

namespace System25\T3sports\Model\Repository;

use Sys25\RnBase\Domain\Repository\PersistenceRepository;
use System25\T3sports\Model\MatchNote;
use System25\T3sports\Model\Profile;
use System25\T3sports\Search\ProfileSearch;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017-2021 Rene Nitzsche (rene@system25.de)
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
 * @author Rene Nitzsche
 */
class ProfileRepository extends PersistenceRepository
{
    private static $unknownPlayer = null;
    private static $notfoundProfile = null;

    public function getSearchClass()
    {
        return ProfileSearch::class;
    }

    /**
     * @param string $uids
     *
     * @return Profile[]
     */
    public function findByUids($uids)
    {
        $fields = $options = [];
        $fields['PROFILE.UID'][OP_IN_INT] = $uids;

        return $this->search($fields, $options);
    }

    /**
     * Liefert den primären Spieler eine MatchNote.
     *
     * @param MatchNote $note
     *
     * @return Profile|null
     */
    public function findByMatchNote(MatchNote $note): ?Profile
    {
        $property = $note->isHome() ? 'player_home' : 'player_guest';
        $profileUid = (int) $note->getProperty($property);
        if (0 == $profileUid) {
            return null;
        } elseif (-1 == $profileUid) {
            return $this->getUnknownPlayer();
        }

        $profile = $this->findByUid($profileUid);

        return $profile ?: $this->getNotFoundPlayer();
    }

    private function getUnknownPlayer()
    {
        if (null == self::$unknownPlayer) {
            self::$unknownPlayer = tx_rnbase::makeInstance(Profile::class, '-1');
        }

        return self::$unknownPlayer;
    }

    private function getNotFoundPlayer()
    {
        if (null == self::$notfoundProfile) {
            self::$notfoundProfile = tx_rnbase::makeInstance(Profile::class, '-1');
        }

        return self::$notfoundProfile;
    }
}
