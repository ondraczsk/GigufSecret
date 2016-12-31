<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____  
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \ 
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/ 
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_| 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 * 
 *
*/

namespace Annihilation\Arena\Tile;

use pocketmine\block\Block;
use pocketmine\inventory\FurnaceRecipe;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\format\FullChunk;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\ContainerSetDataPacket;
use pocketmine\tile\Container;
use pocketmine\tile\Nameable;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;

//Bug fixed by MagicDroidX, Genisys and Nukkit Project

class EnderFurnace extends Spawnable implements InventoryHolder, Container, Nameable{
    /** @var EnderFurnaceInventory */
    protected $inventory;

    /** @var EnderFurnaceInventory[] */
    protected $inventories = [];

    public function __construct(FullChunk $chunk, CompoundTag $nbt)
    {
        parent::__construct($chunk, $nbt);
        $this->inventory = new EnderFurnaceInventory($this, "");

        $this->scheduleUpdate();
    }

    public function getName() : string{
        return "Ender Furnace";
    }

    public function hasName(){
        return isset($this->namedtag->CustomName);
    }

    public function setName($str){
        if($str === ""){
            unset($this->namedtag->CustomName);
            return;
        }

        $this->namedtag->CustomName = new StringTag("CustomName", $str);
    }

    public function close(){
        if($this->closed === false){
            foreach($this->getInventory()->getViewers() as $player){
                $player->removeWindow($this->getInventory());
            }
            parent::close();
        }
    }

    public function saveNBT(){
    }

    /**
     * @return int
     */
    public function getSize(){
        return 3;
    }

    /**
     * @param $index
     *
     * @return int
     */
    protected function getSlotIndex($index){
        foreach($this->namedtag->Items as $i => $slot){
            if($slot["Slot"] === $index){
                return $i;
            }
        }

        return -1;
    }

    /**
     * This method should not be used by plugins, use the Inventory
     *
     * @param int $index
     *
     * @return Item
     */
    public function getItem($index){
        $i = $this->getSlotIndex($index);
        if($i < 0){
            return Item::get(Item::AIR, 0, 0);
        }else{
            return NBT::getItemHelper($this->namedtag->Items[$i]);
        }
    }

    /**
     * This method should not be used by plugins, use the Inventory
     *
     * @param int  $index
     * @param Item $item
     *
     * @return bool
     */
    public function setItem($index, Item $item){
        $i = $this->getSlotIndex($index);

        $d = NBT::putItemHelper($item, $index);

        if($item->getId() === Item::AIR or $item->getCount() <= 0){
            if($i >= 0){
                unset($this->namedtag->Items[$i]);
            }
        }elseif($i < 0){
            for($i = 0; $i <= $this->getSize(); ++$i){
                if(!isset($this->namedtag->Items[$i])){
                    break;
                }
            }
            $this->namedtag->Items[$i] = $d;
        }else{
            $this->namedtag->Items[$i] = $d;
        }

        return true;
    }

    /**
     * @return EnderFurnaceInventory
     */
    public function getInventory(){
        return $this->inventory;
    }

    protected function checkFuel(Item $fuel, EnderFurnaceInventory $inv){
        $inv->maxTime = $fuel->getFuelTime() / 10;
        $inv->burnTime = $fuel->getFuelTime() / 10;
        $inv->burnTicks = 0;

        if($inv->burnTime > 0){
            $fuel->setCount($fuel->getCount() - 1);
            if($fuel->getCount() === 0){
                $fuel = Item::get(Item::AIR, 0, 0);
            }
            $inv->setFuel($fuel);
        }
    }

    private $lastUpdate2 = 0;

    public function onUpdate(){
        if($this->closed === true){
            return false;
        }

        if($this->lastUpdate2 < 10){
            $this->lastUpdate2++;
            return true;
        }

        $this->lastUpdate2 = 0;

        $this->timings->startTiming();

        //$ret = false;

        foreach($this->inventories as $inventory) {

            $fuel = $inventory->getFuel();
            $raw = $inventory->getSmelting();
            $product = $inventory->getResult();
            $smelt = $this->server->getCraftingManager()->matchFurnaceRecipe($raw);
            $canSmelt = ($smelt instanceof FurnaceRecipe and $raw->getCount() > 0 and (($smelt->getResult()->equals($product) and $product->getCount() < $product->getMaxStackSize()) or $product->getId() === Item::AIR));

            if ($inventory->burnTime <= 0 and $canSmelt and $fuel->getFuelTime() !== null and $fuel->getCount() > 0) {
                $this->checkFuel($fuel, $inventory);
            }

            if ($inventory->burnTime > 0) {
                $inventory->burnTime--;
                $inventory->burnTicks = ceil(($inventory->burnTime / $inventory->maxTime * 20));

                if ($smelt instanceof FurnaceRecipe and $canSmelt) {
                    $inventory->cookTime++;
                    if ($inventory->cookTime >= 20) { //10 seconds
                        $product = Item::get($smelt->getResult()->getId(), $smelt->getResult()->getDamage(), $product->getCount() + 1);
                            $inventory->setResult($product);
                            $raw->setCount($raw->getCount() - 1);
                            if ($raw->getCount() === 0) {
                                $raw = Item::get(Item::AIR, 0, 0);
                            }
                            $inventory->setSmelting($raw);

                        $inventory->cookTime = $inventory->cookTime - 20;
                    }
                } elseif ($inventory->burnTime <= 0) {
                    $inventory->burnTime = 0;
                    $inventory->cookTime = 0;
                    $inventory->burnTicks = 0;
                } else {
                    $inventory->cookTime = 0;
                }
                //$ret = true;

                foreach ($inventory->getViewers() as $player) {
                    $windowId = $player->getWindowId($inventory);
                    if ($windowId > 0) {
                        $pk = new ContainerSetDataPacket();
                        $pk->windowid = $windowId;
                        $pk->property = 0; //Smelting
                        $pk->value = floor($inventory->cookTime * 10);
                        $player->dataPacket($pk);

                        $pk = new ContainerSetDataPacket();
                        $pk->windowid = $windowId;
                        $pk->property = 1; //Fire icon
                        $pk->value = $inventory->burnTicks * 10;
                        $player->dataPacket($pk);
                    }

                }

            } else {
                $inventory->burnTime = 0;
                $inventory->cookTime = 0;
                $inventory->burnTicks = 0;
                unset($this->inventories[$inventory->owner]);
            }
        }

        $this->lastUpdate = microtime(true);

        $this->timings->stopTiming();

        return true;
    }

    public function getSpawnCompound(){
        $nbt = new CompoundTag("", [
            new StringTag("id", Tile::FURNACE),
            new IntTag("x", (int) $this->x),
            new IntTag("y", (int) $this->y),
            new IntTag("z", (int) $this->z),
            new ShortTag("BurnTime", 0),
            new ShortTag("CookTime", 0),
            new ShortTag("BurnDuration", 0)
        ]);

        if($this->hasName()){
            $nbt->CustomName = $this->namedtag->CustomName;
        }
        return $nbt;
    }

    public function inventoryUpdate(EnderFurnaceInventory $inv){
        $this->inventories[$inv->owner] = $inv;
    }
}
