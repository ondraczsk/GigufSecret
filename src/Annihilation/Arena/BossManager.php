<?php

namespace Annihilation\Arena;

use pocketmine\entity\Entity;
use Annihilation\Entity\IronGolem;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\level\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Zombie;
use pocketmine\tile\Chest;
use pocketmine\Server;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\nbt\NBT;
use pocketmine\tile\Tile;

class BossManager{
    
    public $plugin;
    public $task1;
    public $task2;
    public $bosses = [1 => false, 2 => false];
    
    public function __construct(Arena $arena){
        $this->plugin = $arena;
    }
    
    //boss (1/2)
    public function spawnBoss($boss){
        if($this->plugin->phase >= 3){
            $this->plugin->messageAllPlayers(TextFormat::GRAY."================[ ".TextFormat::DARK_AQUA."Boss".TextFormat::GRAY." ]================\n"
                    . $this->getBossName($boss).TextFormat::GRAY." has respawned! Go slay the beast!\n"
                    . TextFormat::GRAY."=======================================");
        }

        $pos = $this->plugin->data['bosses'][$boss]['pos'];
        if(!$this->plugin->level->isChunkLoaded($pos->x >> 4, $pos->z >> 4)){
            $this->plugin->level->loadChunk($pos->x >> 4, $pos->z >> 4);
        }

        $chunk = $this->plugin->level->getChunk($pos->x >> 4, $pos->z >> 4);
        $golem = Entity::createEntity(IronGolem::NETWORK_ID, $chunk, $this->getNbt());

        $golem->setMaxHealth(200);
        $golem->setHealth(200);

        $name = $this->plugin->data['bosses'][$boss]['name']."   ".TextFormat::GREEN.$golem->getHealth()." HP";

        $golem->setPosition($pos);
        $golem->setNameTag($name);
	    $golem->spawnToAll();
        $this->bosses[$boss] = true;
    }
    
    public function getNbt()
    {
        $nbt = new CompoundTag();
        $nbt->Pos = new ListTag("Pos", [
            new DoubleTag("", 0),
            new DoubleTag("", 0),
            new DoubleTag("", 0),
        ]);
        $nbt->Motion = new ListTag("Motion", [
            new DoubleTag("", 0),
            new DoubleTag("", 0),
            new DoubleTag("", 0),
        ]);
        $nbt->Rotation = new ListTag("Rotation", [
            new FloatTag("", 0),
            new FloatTag("", 0)
        ]);
        $nbt->Health = new ShortTag("Health", 200);
        return $nbt;
    }
    
    public function spawnChest($pos){
        $level = $this->plugin->level;
        $tile = $level->getTile($pos);
        if(!$tile instanceof Chest){
            $level->setBlock($pos, Block::get(54, 0), true);
            $nbt = new CompoundTag("", [
                new ListTag("Items", []),
                new StringTag("id", Tile::CHEST),
                new IntTag("x", $pos->x),
                new IntTag("y", $pos->y),
                new IntTag("z", $pos->z)
            ]);
            $nbt->Items->setTagType(NBT::TAG_Compound);
            $tile = Tile::createTile("Chest", $this->plugin->level->getChunk($pos->x >> 4, $pos->z >> 4), $nbt);
        }
        $tile->getInventory()->setItem(rand(0, 26), $this->getDrop());
    }
    
    public function getDrop(){

        switch(mt_rand(1, 6)){
            case 1:
                $item = Item::get(310, 0, 1);
                $ench = Enchantment::getEnchantment(0);
                $ench->setLevel(2);
                $item->addEnchantment($ench);
                $ench = Enchantment::getEnchantment(6);
                $ench->setLevel(4);
                $item->addEnchantment($ench);
                $item->setCustomName(TextFormat::AQUA."Oxyger I");
                return $item;
            case 2:
                $item = Item::get(311, 0, 1);
                $ench = Enchantment::getEnchantment(0);
                $ench->setLevel(4);
                $item->addEnchantment($ench);
                $ench = Enchantment::getEnchantment(6);
                $ench->setLevel(4);
                $item->addEnchantment($ench);
                $item->setCustomName(TextFormat::AQUA."Oxyger II");
                return $item;
            case 3:
                $item = Item::get(311, 0, 1);
                $ench = Enchantment::getEnchantment(0);
                $ench->setLevel(4);
                $item->addEnchantment($ench);
                $ench = Enchantment::getEnchantment(6);
                $ench->setLevel(4);
                $item->addEnchantment($ench);
                $item->setCustomName(TextFormat::AQUA."TrollPlate");
                return $item;
            case 4:
                $item = Item::get(312, 0, 1);
                $ench = Enchantment::getEnchantment(0);
                $ench->setLevel(4);
                $item->addEnchantment($ench);
                $ench = Enchantment::getEnchantment(5);
                $ench->setLevel(2);
                $item->addEnchantment($ench);
                $item->setCustomName(TextFormat::AQUA."Antisharp leggs");
                return $item;
            case 5:
                $item = Item::get(313, 0, 1);
                $ench = Enchantment::getEnchantment(0);
                $ench->setLevel(4);
                $item->addEnchantment($ench);
                $ench = Enchantment::getEnchantment(2);
                $ench->setLevel(4);
                $item->addEnchantment($ench);
                $item->setCustomName(TextFormat::AQUA."Fly Boots");
                return $item;
            case 6:
                $item = Item::get(276, 0, 1);
                $ench = Enchantment::getEnchantment(9);
                $ench->setLevel(3);
                $item->addEnchantment($ench);
                $ench = Enchantment::getEnchantment(17);
                $ench->setLevel(3);
                $item->addEnchantment($ench);
                $ench = Enchantment::getEnchantment(12);
                $ench->setLevel(2);
                $item->addEnchantment($ench);
                $ench = Enchantment::getEnchantment(13);
                $ench->setLevel(2);
                $item->addEnchantment($ench);
                $item->setCustomName(TextFormat::AQUA."Blood Finger");
                return $item;
            case 7:
                $item = Item::get(261, 0, 1);
                $ench = Enchantment::getEnchantment(19);
                $ench->setLevel(4);
                $item->addEnchantment($ench);
                $ench = Enchantment::getEnchantment(22);
                $ench->setLevel(1);
                $item->addEnchantment($ench);
                $ench = Enchantment::getEnchantment(21);
                $ench->setLevel(1);
                $item->addEnchantment($ench);
                $ench = Enchantment::getEnchantment(20);
                $ench->setLevel(2);
                $item->addEnchantment($ench);
                $item->setCustomName(TextFormat::AQUA."Infinite Shooter");
                return $item;
        }
    }
    
    public function onBossDeath($boss, $pname){
        if(!isset($this->bosses[$boss]) || $this->bosses[$boss] !== true){
            return;
        }
        $this->spawnChest($this->plugin->data['bosses'][$boss]['chest']);
        Server::getInstance()->getScheduler()->scheduleDelayedTask($this->task1 = new BossTask($this, $boss), 12000);

        $this->plugin->messageAllPlayers(TextFormat::GRAY."===========[ ".TextFormat::DARK_AQUA."Boss Killed".TextFormat::GRAY." ]===========\n"
                        . $this->getBossName($boss).TextFormat::GRAY." was killed by ".$pname."\n"
                        . TextFormat::GRAY."==================================");

        $this->bosses[$boss] = false;
    }
    
    public function getBossName($boss){
        return $this->plugin->data['bosses'][$boss]['name'];
    }
}