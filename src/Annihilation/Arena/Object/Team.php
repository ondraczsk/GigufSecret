<?php

namespace Annihilation\Arena\Object;

use Annihilation\Annihilation;
use Annihilation\Arena\Arena;
use Annihilation\Arena\BlockSetTask;
use Annihilation\Arena\Tile\EnderFurnace;
use Annihilation\MySQL\JoinTeamQuery;
use MTCore\MTCore;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;

class Team{

    private $name;
    private $color;
    private $dec;
    private $id;

    /** @var Player[] */
    private $players = [];

    private $nexus;

    /** @var Vector3[] */
    private $data = [];

    private $plugin;

    /** @var  Position $spawn */
    private $spawn;

    /** @var  Chest */
    private $brewingShop;
    /** @var  Chest */
    private $weaponsShop;

    /** @var  Chest */
    private $kitWindow;

    private $enderBrewing;

    private $enderFurnace;

    public function __construct($id, $name, $color, $dec, Arena $plugin){
        $this->id = $id;
        $this->name = $name;
        $this->color = $color;
        $this->dec = $dec;
        $this->plugin = $plugin;
    }

    /**
     * @param Vector3[] $data
     */
    public function setData(array $data, Arena $plugin){
        $this->data = $data;
        $this->nexus = new Nexus($this, new Position($this->data["nexus"]->x, $this->data["nexus"]->y, $this->data["nexus"]->z, $plugin->level));
        $this->spawn = new Position($this->data["spawn"]->x, $this->data["spawn"]->y, $this->data["spawn"]->z, $plugin->level);

        /*$nbt = new CompoundTag("", [
            new ListTag("Items", []),
            new StringTag("id", Tile::CHEST),
            new IntTag("x", $this->data["chest"]->x),
            new IntTag("y", $this->data["chest"]->y),
            new IntTag("z", $this->data["chest"]->z),
            //new String("CustomName", "Brewing Shop")
        ]);*/

        //$items = [[Item::get(Item::BREWING_STAND), Item::get(Item::GOLD_INGOT, 0, 10)], [Item::get(374, 0, 3), Item::get(Item::GOLD_INGOT, 0, 1)], [Item::get(372), Item::get(Item::GOLD_INGOT, 0, 5)], [Item::get(331), Item::get(Item::GOLD_INGOT, 0, 3)], [Item::get(376), Item::get(Item::GOLD_INGOT, 0, 3)], [Item::get(378), Item::get(Item::GOLD_INGOT, 0, 2)], [Item::get(353), Item::get(Item::GOLD_INGOT, 0, 2)], [Item::get(382), Item::get(Item::GOLD_INGOT, 0, 2)], [Item::get(370), Item::get(Item::GOLD_INGOT, 0, 15)], [Item::get(396), Item::get(Item::GOLD_INGOT, 0, 2)], [Item::get(375), Item::get(Item::GOLD_INGOT, 0, 2)], [Item::get(377), Item::get(Item::GOLD_INGOT, 0, 15)], [Item::get(Item::GUNPOWDER), Item::get(Item::GOLD_INGOT, 0, 30)]];
        //$items = [[Item::get(Item::POTION, Potion::STRENGTH_TWO, 3), Item::get(Item::GOLD_INGOT, 0, 24)], [Item::get(Item::POTION, Potion::REGENERATION_T, 3), Item::get(Item::GOLD_INGOT, 0, 24)], [Item::get(Item::POTION, Potion::INVISIBILITY_T, 3), Item::get(Item::GOLD_INGOT, 0, 18)], [Item::get(Item::SPLASH_POTION, Potion::STRENGTH_TWO, 3), Item::get(Item::GOLD_INGOT, 0, 34)], [Item::get(Item::SPLASH_POTION, Potion::REGENERATION_T, 3), Item::get(Item::GOLD_INGOT, 0, 34)], [Item::get(Item::SPLASH_POTION, Potion::INVISIBILITY_T, 3), Item::get(Item::GOLD_INGOT, 0, 28)]];

        $task = new BlockSetTask($this->plugin, [Block::get(Item::CHEST, 0, new Position($data["brewing"]->x, $data["brewing"]->y, $data["brewing"]->z, $this->plugin->level)), Block::get(Item::CHEST, 0, new Position($data["weapons"]->x, $data["weapons"]->y, $data["weapons"]->z, $this->plugin->level))]);
        $this->plugin->plugin->getServer()->getScheduler()->scheduleDelayedTask($task, 100);
        //$plugin->level->setBlock($data["brewing"], Block::get(Item::CHEST, 0, $data["brewing"]));
        //$plugin->level->setBlock($data["weapons"], Block::get(Item::CHEST, 0, $data["weapons"]));

        /** @var Chest $chest */
        /*$chest = Tile::createTile("Chest", $this->plugin->level->getChunk($this->data["brewing"]->x >> 4, $this->data["brewing"]->z >> 4), $nbt);
        $this->brewingShop = $chest;
        $this->brewingShop->setName("Brewing Shop");

        $slot = 0;
        foreach($items as $array){
            $this->brewingShop->getInventory()->setItem($slot, $array[0]);
            $slot++;
            $this->brewingShop->getInventory()->setItem($slot, $array[1]);
            $slot++;
        }

        $chest2 = Tile::createTile("Chest", $this->plugin->level->getChunk($this->data["weapons"]->x >> 4, $this->data["weapons"]->z >> 4), $nbt);
        $this->weaponsShop = $chest2;
        $this->weaponsShop->setName("Weapon Shop");

        $items = [[Item::get(Item::IRON_HELMET), Item::get(Item::GOLD_INGOT, 0, 10)], [Item::get(Item::IRON_CHESTPLATE), Item::get(Item::GOLD_INGOT, 0, 18)], [Item::get(Item::IRON_LEGGINGS), Item::get(Item::GOLD_INGOT, 0, 14)], [Item::get(Item::IRON_BOOTS), Item::get(Item::GOLD_INGOT, 0, 8)], [Item::get(Item::IRON_SWORD), Item::get(Item::GOLD_INGOT, 0, 5)], [Item::get(Item::BOW), Item::get(Item::GOLD_INGOT, 0, 5)], [Item::get(Item::ARROW, 0, 16), Item::get(Item::GOLD_INGOT, 0, 5)], [Item::get(Item::CAKE), Item::get(Item::GOLD_INGOT, 0, 5)], [Item::get(Item::MELON, 0, 16), Item::get(Item::GOLD_INGOT, 0, 1)]];

        $slot = 0;
        foreach($items as $array){
            $this->weaponsShop->getInventory()->setItem($slot, $array[0]);
            $slot++;
            $this->weaponsShop->getInventory()->setItem($slot, $array[1]);
            $slot++;
        }*/

        $this->plugin->level->loadChunk($this->data["furnace"]->x >> 4, $this->data["furnace"]->z >> 4);

        $nbt = new CompoundTag("", [
            new ListTag("Items", []),
            new StringTag("id", Tile::FURNACE),
            new IntTag("x", $this->data["furnace"]->x),
            new IntTag("y", $this->data["furnace"]->y),
            new IntTag("z", $this->data["furnace"]->z),
            new StringTag("CustomName", "Ender Furnace")
        ]);
        $nbt->Items->setTagType(NBT::TAG_Compound);

        /** @var EnderFurnace $enderFurnace */
        $this->enderFurnace = Tile::createTile("EnderFurnace", $this->plugin->level->getChunk($this->data["furnace"]->x >> 4, $this->data["furnace"]->z >> 4), clone $nbt);

        $nbt = new CompoundTag("", [
            new ListTag("Items", []),
            new StringTag("id", Tile::BREWING_STAND),
            new IntTag("x", $this->data["enderbrewing"]->x),
            new IntTag("y", $this->data["enderbrewing"]->y),
            new IntTag("z", $this->data["enderbrewing"]->z),
            new StringTag("CustomName", "Ender Brewing")
        ]);
        $nbt->Items->setTagType(NBT::TAG_Compound);

        $this->enderBrewing = Tile::createTile("EnderBrewingStand", $this->plugin->level->getChunk($this->data["enderbrewing"]->x >> 4, $this->data["enderbrewing"]->z >> 4), clone $nbt);
    }

    public function getId(){
        return $this->id;
    }

    public function addPlayer(Player $p){
        $this->players[strtolower($p->getName())] = $p;

        $anni = Annihilation::getInstance(); //WTF ???

        new JoinTeamQuery($anni, $p->getName(), $this->getColor());

        //$p->setNameTag($this->getColor().$p->getName().TextFormat::WHITE);
        //$p->setDisplayName(MTCore::getDisplayRank($p)." ".$this->getColor().$p->getName().TextFormat::WHITE);
    }

    public function removePlayer(Player $p){
        unset($this->players[strtolower($p->getName())]);
    }

    public function getName(){
        return $this->name;
    }

    public function getColor(){
        return $this->color;
    }

    public function getDec(){
        return $this->dec;
    }

    /**
     * @return Nexus
     */
    public function getNexus(){
        return $this->nexus;
    }

    public function isAlive(){
        return count($this->players) > 0 ? true : false;
    }

    public function getPlayers(){
        return $this->players;
    }

    public function message($message, Player $player = null){
        Server::getInstance()->getLogger()->info($message);
        if($player === null){
            foreach($this->getPlayers() as $p) {
                $p->sendMessage($message);
            }
            return;
        }

        $color = $this->getColor();
        $msg = TextFormat::GRAY . "[{$color}Team" . TextFormat::GRAY . "]   " . $player->getDisplayName() . TextFormat::DARK_AQUA . " > " . (isset($this->plugin->mtcore->chatColors[strtolower($player->getName())]) ? $this->plugin->mtcore->chatColors[strtolower($player->getName())] : "") . $message;

        foreach($this->getPlayers() as $p) {
            $p->sendMessage($msg);
        }
    }

    public function getSpawnLocation(){
        return $this->spawn;
    }

    public function getBrewingShop(){
        return $this->brewingShop;
    }

    public function getWeaponsShop(){
        return $this->weaponsShop;
    }

    public function getKitWindow(){
        return $this->kitWindow->getInventory();
    }

    public function getEnderBrewing(){
        return $this->enderBrewing;
    }

    public function getEnderFurnace(){
        return $this->enderFurnace;
    }
}