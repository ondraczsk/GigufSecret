<?php

namespace Annihilation\Arena\Kits;


use pocketmine\item\Item;
use pocketmine\Player;

interface Kit{

    /**
     * @param Player $p
     * @return Item[]
     */
    public static function give(Player $p);
}