<?php

namespace Annihilation\Arena\Tile;

use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\InventoryType;
use pocketmine\item\Item;

class EnderFurnaceInventory extends ContainerInventory{

    public $maxTime = 0;
    public $burnTime = 0;
    public $burnTicks = 0;
    public $cookTime = 0;

    public $owner;

    public function __construct(EnderFurnace $tile, string $owner){
        $this->owner = $owner;
        parent::__construct($tile, InventoryType::get(InventoryType::FURNACE));
    }

    /**
     * @return EnderFurnace
     */
    public function getHolder(){
        return $this->holder;
    }

    /**
     * @return Item
     */
    public function getResult(){
        return $this->getItem(2);
    }

    /**
     * @return Item
     */
    public function getFuel(){
        return $this->getItem(1);
    }

    /**
     * @return Item
     */
    public function getSmelting(){
        return $this->getItem(0);
    }

    /**
     * @param Item $item
     *
     * @return bool
     */
    public function setResult(Item $item){
        return $this->setItem(2, $item);
    }

    /**
     * @param Item $item
     *
     * @return bool
     */
    public function setFuel(Item $item){
        return $this->setItem(1, $item);
    }

    /**
     * @param Item $item
     *
     * @return bool
     */
    public function setSmelting(Item $item){
        return $this->setItem(0, $item);
    }

    public function onSlotChange($index, $before){
        parent::onSlotChange($index, $before);

        $this->getHolder()->inventoryUpdate($this);
    }
}