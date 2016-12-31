<?php

namespace Annihilation\Arena;

use Annihilation\Arena\Tile\EnderBrewingInventory;
use Annihilation\Arena\Tile\EnderFurnace;
use Annihilation\Arena\Tile\EnderFurnaceInventory;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\FurnaceInventory;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Chest;
use pocketmine\tile\Furnace;
use pocketmine\tile\Tile;

class EnderManager{
    
    public $plugin;
    
    public function __construct(Arena $plugin) {
        $this->plugin = $plugin;
    }
    
    public function createChest(Player $p){
        $id = $this->plugin->getPlayerTeam($p)->getId();

        $nbt = new CompoundTag("", [
            new ListTag("Items", []),
            new StringTag("id", Tile::CHEST),
            new IntTag("x", $this->plugin->data[$id."Chest"]->x),
            new IntTag("y", $this->plugin->data[$id."Chest"]->y),
            new IntTag("z", $this->plugin->data[$id."Chest"]->z),
            new StringTag("CustomName", "Ender Chest")
        ]);
        $chest = Tile::createTile("Chest", $this->plugin->level->getChunk($this->plugin->data[$id."Chest"]->x >> 4, $this->plugin->data[$id."Chest"]->z >> 4), $nbt);

        $inv = new ChestInventory($chest);
        $this->plugin->getPlayerData($p)->setChest($inv);
    }

    public function createBrewing(Player $p){
        $data = $this->plugin->getPlayerData($p);

        $inv = new EnderBrewingInventory($data->getTeam()->getEnderBrewing(), strtolower($p->getName()));

        $data->setBrewing($inv);
    }
    
    public function createFurnace(Player $p){
        $data = $this->plugin->getPlayerData($p);

        $inv = new EnderFurnaceInventory($data->getTeam()->getEnderFurnace(), strtolower($p->getName()));

        $data->setFurnace($inv);
    }
}