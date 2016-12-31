<?php

namespace Annihilation\Arena;


use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\scheduler\Task;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;

class BlockSetTask extends Task{

    private $plugin;
    private $blocks;

    public function __construct(Arena $plugin, array $blocks){
        $this->plugin = $plugin;
        $this->blocks = $blocks;
    }

    public function onRun($currentTick){
        if($this->plugin->level == null){
            return;
        }

        $level = $this->plugin->level;

        $itemsBrew = [[Item::get(Item::BREWING_STAND), Item::get(Item::GOLD_INGOT, 0, 10)], [Item::get(374, 0, 3), Item::get(Item::GOLD_INGOT, 0, 1)], [Item::get(372), Item::get(Item::GOLD_INGOT, 0, 5)], [Item::get(331), Item::get(Item::GOLD_INGOT, 0, 3)], [Item::get(376), Item::get(Item::GOLD_INGOT, 0, 3)], [Item::get(378), Item::get(Item::GOLD_INGOT, 0, 2)], [Item::get(353), Item::get(Item::GOLD_INGOT, 0, 2)], [Item::get(382), Item::get(Item::GOLD_INGOT, 0, 2)], [Item::get(370), Item::get(Item::GOLD_INGOT, 0, 15)], [Item::get(396), Item::get(Item::GOLD_INGOT, 0, 2)], [Item::get(375), Item::get(Item::GOLD_INGOT, 0, 2)], [Item::get(377), Item::get(Item::GOLD_INGOT, 0, 15)], [Item::get(Item::GUNPOWDER), Item::get(Item::GOLD_INGOT, 0, 30)]];
        $itemsWeapon = [[Item::get(Item::IRON_HELMET), Item::get(Item::GOLD_INGOT, 0, 10)], [Item::get(Item::IRON_CHESTPLATE), Item::get(Item::GOLD_INGOT, 0, 18)], [Item::get(Item::IRON_LEGGINGS), Item::get(Item::GOLD_INGOT, 0, 14)], [Item::get(Item::IRON_BOOTS), Item::get(Item::GOLD_INGOT, 0, 8)], [Item::get(Item::IRON_SWORD), Item::get(Item::GOLD_INGOT, 0, 5)], [Item::get(Item::BOW), Item::get(Item::GOLD_INGOT, 0, 5)], [Item::get(Item::ARROW, 0, 16), Item::get(Item::GOLD_INGOT, 0, 5)], [Item::get(Item::CAKE), Item::get(Item::GOLD_INGOT, 0, 5)], [Item::get(Item::MELON, 0, 16), Item::get(Item::GOLD_INGOT, 0, 1)]];

        $name = 0;

        foreach($this->blocks as $block){
            $level->setBlock($block, $block, true, true);

            $nbt = new CompoundTag("", [
                new ListTag("Items", []),
                new StringTag("id", Tile::CHEST),
                new IntTag("x", $block->x),
                new IntTag("y", $block->y),
                new IntTag("z", $block->z),
                //new String("CustomName", "Brewing Shop")
            ]);

            if($name === 0){
                $nbt->CustomName = new StringTag("CustomName", "Brewing Shop");
            } else{
                $nbt->CustomName = new StringTag("CustomName", "Weapon Shop");
            }

            /** @var Chest $tile */
            $tile = Tile::createTile(Tile::CHEST, $level->getChunk($block->x >> 4, $block->z >> 4), $nbt);

            if($name === 0){
                $tile->setName("Brewing Shop");
                $slot = 0;

                foreach($itemsBrew as $array){
                    $tile->getInventory()->setItem($slot, $array[0]);
                    $slot++;
                    $tile->getInventory()->setItem($slot, $array[1]);
                    $slot++;
                }

                $name++;
            } else{
                $tile->setName("Weapon Shop");
                $slot = 0;

                foreach($itemsWeapon as $array){
                    $tile->getInventory()->setItem($slot, $array[0]);
                    $slot++;
                    $tile->getInventory()->setItem($slot, $array[1]);
                    $slot++;
                }
            }
        }
    }
}