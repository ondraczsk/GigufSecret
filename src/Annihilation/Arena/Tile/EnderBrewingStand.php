<?php
/*
 *
 *  _                       _           _ __  __ _
 * (_)                     (_)         | |  \/  (_)
 *  _ _ __ ___   __ _  __ _ _  ___ __ _| | \  / |_ _ __   ___
 * | | '_ ` _ \ / _` |/ _` | |/ __/ _` | | |\/| | | '_ \ / _ \
 * | | | | | | | (_| | (_| | | (_| (_| | | |  | | | | | |  __/
 * |_|_| |_| |_|\__,_|\__, |_|\___\__,_|_|_|  |_|_|_| |_|\___|
 *                     __/ |
 *                    |___/
 *
 * This program is a third party build by ImagicalMine.
 *
 * PocketMine is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author ImagicalMine Team
 * @link http://forums.imagicalcorp.ml/
 *
 *
*/
namespace Annihilation\Arena\Tile;

use pocketmine\block\Air;
use pocketmine\inventory\BrewingInventory;
use pocketmine\inventory\BrewingRecipe;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\item\SplashPotion;
use pocketmine\level\format\FullChunk;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\ContainerSetDataPacket;
use pocketmine\Server;
use pocketmine\tile\Container;
use pocketmine\tile\Nameable;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;

class EnderBrewingStand extends Spawnable implements InventoryHolder, Container, Nameable{
    /** @var BrewingInventory */
    protected $inventory;

    /** @var EnderBrewingInventory[] */
    protected $inventories = [];

    /** @var EnderBrewingInventory[] */
    private $updateInventories = [];

    const MAX_BREW_TIME = 40;

    public static $ingredients = [Item::NETHER_WART, Item::GOLD_NUGGET, Item::GHAST_TEAR, Item::GLOWSTONE_DUST, Item::REDSTONE_DUST, Item::GUNPOWDER, Item::MAGMA_CREAM, Item::BLAZE_POWDER, Item::GOLDEN_CARROT, Item::SPIDER_EYE, Item::FERMENTED_SPIDER_EYE, Item::GLISTERING_MELON, Item::SUGAR, Item::RAW_FISH];

    public function __construct(FullChunk $chunk, CompoundTag $nbt){
        parent::__construct($chunk, $nbt);
        $this->inventory = new EnderBrewingInventory($this, "");

        /*if(!isset($this->namedtag->Items) or !($this->namedtag->Items instanceof ListTag)){
            $this->namedtag->Items = new ListTag("Items", []);
            $this->namedtag->Items->setTagType(NBT::TAG_Compound);
        }

        if(!isset($this->namedtag->Inventories) || !$this->namedtag->Inventories instanceof ListTag){
            $this->namedtag->Inventories = new ListTag("Inventories", []);
            $this->namedtag->Inventories->setTagType(NBT::TAG_Compound);
        } else {

            foreach ($this->namedtag->Inventories as $name => $items) {
                $inv = new EnderBrewingInventory($this);

                foreach($items as $slot => $item){
                    $inv->setItem($slot, $item);
                }

                $this->inventories[strtolower($name)] = $inv;
            }
        }*/

        /*for($i = 0; $i < $this->getSize(); ++$i){
            $this->inventory->setItem($i, $this->getItem($i));
        }*/

        $this->scheduleUpdate();
    }

    public function getName() : string{
        return "Ender Brewing";
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
            foreach($this->inventories as $inventory) {
                foreach ($inventory->getViewers() as $player) {
                    $player->removeWindow($inventory);
                }
            }
            parent::close();
        }
    }

    public function saveNBT(){
        /*$this->namedtag->Inventories = new ListTag("Inventories", []);
        $this->namedtag->Inventories->setTagType(NBT::TAG_Compound);

        foreach ($this->inventories as $name => $inventory) {
            $items = new ListTag("Items", []);
            $items->setTagType(NBT::TAG_Compound);

            foreach($inventory->getContents() as $slot => $item){
                $d = NBT::putItemHelper($item, $slot);
                $items[$slot] = $d;
            }

            $this->namedtag->Inventories[$name] = $items;
        }*/


        /*$this->namedtag->Items = new ListTag("Items", []);
        $this->namedtag->Items->setTagType(NBT::TAG_Compound);
        for($index = 0; $index < $this->getSize(); ++$index){
            $this->setItem($index, $this->inventory->getItem($index));
        }*/
    }

    /**
     * @return int
     */
    public function getSize(){
        return 4;
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
     * @return BrewingInventory
     */
    public function getInventory(){
        return $this->inventory;
    }

    protected function checkIngredient(Item $ingredient){
        return in_array($ingredient->getId(), self::$ingredients);
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

        foreach($this->inventories as $owner => $inventory) {
            $ingredient = $inventory->getIngredient();
            $potions = $inventory->getPotions();
            $canBrew = false;

            foreach ($potions as $pot) {
                if ($pot->getId() === Item::POTION || $pot->getId() === Item::SPLASH_POTION) {
                    $canBrew = true;
                }
            }

            if ($inventory->brewTime <= self::MAX_BREW_TIME and $canBrew and $ingredient->getCount() > 0) {
                if (!$this->checkIngredient($ingredient)) {
                    $canBrew = false;
                }
            } else {
                $canBrew = false;
            }

            if ($canBrew) {
                $inventory->brewTime--;

                //echo "\nbrewing...";

                if ($inventory->brewTime <= 0) { //20 seconds
                    //echo "\ndone";
                    foreach ($inventory->getPotions() as $slot => $potion) {
                        $recipe = Server::getInstance()->getCraftingManager()->matchBrewingRecipe($ingredient, $potion);

                        if ($recipe instanceof BrewingRecipe) {
                            //echo "\nrecipe";
                            $inventory->setPotion($slot, $recipe->getResult());
                        } elseif ($ingredient->getId() === Item::GUNPOWDER && $potion->getId() === Item::POTION) {
                            $inventory->setPotion($slot, new SplashPotion($potion->getDamage()));
                        }
                    }

                    $ingredient->setCount($ingredient->getCount() - 1);
                    if($ingredient->getCount() <= 0){
                        $inventory->setIngredient(Item::get(Item::AIR, 0, 0));
                    } else{
                        $inventory->setIngredient($ingredient);
                    }

                    $inventory->brewTime += self::MAX_BREW_TIME;
                }
            } else {
                $inventory->brewTime = self::MAX_BREW_TIME;
                unset($this->inventories[$owner]);
            }

            echo "\nrun";

            foreach ($inventory->getViewers() as $player) {
                $windowId = $player->getWindowId($inventory);
                if ($windowId > 0) {
                    $pk = new ContainerSetDataPacket();
                    $pk->windowid = $windowId;
                    $pk->property = 0; //Brewing
                    $pk->value = floor($inventory->brewTime * 10);
                    $player->dataPacket($pk);
                }

            }

        }

        $this->lastUpdate = microtime(true);

        $this->timings->stopTiming();

        return true;
    }

    public function getSpawnCompound(){
        $nbt = new CompoundTag("", [
            new StringTag("id", Tile::BREWING_STAND),
            new IntTag("x", (int) $this->x),
            new IntTag("y", (int) $this->y),
            new IntTag("z", (int) $this->z),
            new ShortTag("BrewTime", self::MAX_BREW_TIME)
        ]);

        if($this->hasName()){
            $nbt->CustomName = $this->namedtag->CustomName;
        }
        return $nbt;
    }

    public function inventoryUpdate(EnderBrewingInventory $inv){
        $this->inventories[$inv->owner] = $inv;
    }
}