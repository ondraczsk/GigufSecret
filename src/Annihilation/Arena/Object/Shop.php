<?php

namespace Annihilation\Arena\Object;


use Annihilation\Annihilation;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\GoldIngot;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;

class Shop{

    public function onTransaction(Player $p, $slot, Item $item, ChestInventory $inv, $phase){
        if($item instanceof GoldIngot){
            return;
        }

        if(!$p->getInventory()->canAddItem($item)){
            $p->sendMessage(Annihilation::getPrefix().TextFormat::RED."Your inventory is full");
            return;
        }

        if($item->getId() === 377 && $phase < 4){
            $p->sendMessage(TextFormat::RED."You can not buy this until phase IV");
            return;
        }

        if(!$p->getInventory()->contains($cost = $inv->getItem($slot+1))){
            $p->sendMessage(TextFormat::RED."You haven't got enough gold");
            return;
        }

        $p->sendMessage(TextFormat::GRAY."Purchased ".TextFormat::YELLOW.$item->getName());
        $p->getInventory()->removeItem($cost);
        $p->getInventory()->addItem($item);
        $p->getInventory()->sendContents($p);
    }
}