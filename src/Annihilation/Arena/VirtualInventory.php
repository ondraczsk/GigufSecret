<?php
namespace Annihilation\Arena;

use pocketmine\inventory\CustomInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\inventory\InventoryType;

class VirtualInventory{
    
    public $hotbar = [];
    /** @var Item[]  */
    public $armor = [];
    /** @var Item[] */
    public $contents = [];

    public $xp = 0;
    public $xplevel = 0;
    public $hunger = 0;
    public $health = 0;
    
    public function __construct(Player $p){
        $inv = $p->getInventory();

        foreach($inv->getContents() as $slot => $item){
            $this->contents[$slot] = clone $item;
        }

        $this->armor = $inv->getArmorContents();

        for($i = 0; $i < 10; $i++){
            $this->hotbar[$i] = $inv->getHotbarSlotIndex($i);
        }

        $this->hunger = $p->getFood();
        //$this->xp = $p->getExp();
        //$this->xplevel = $p->getExpLevel();
        $this->health = $p->getHealth();
    }

    public function getContents(){
        return $this->contents;
    }
}