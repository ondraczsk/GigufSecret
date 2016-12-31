<?php

namespace Annihilation\Arena;

use Annihilation\Arena\Object\PlayerData;
use Annihilation\Arena\Object\Shop;
use Annihilation\Arena\Object\Team;
use Annihilation\Arena\Tile\EnderBrewingInventory;
use Annihilation\Arena\Tile\EnderBrewingStand;
use Annihilation\Arena\Tile\EnderFurnaceInventory;
use Annihilation\MySQL\NormalQuery;
use Annihilation\MySQLManager;
use Annihilation\Task\WorldCopyTask;
use Annihilation\WorldManager;
use pocketmine\command\Command;
use pocketmine\entity\Arrow;
use Annihilation\Entity\IronGolem;
use pocketmine\entity\FishingHook;
use pocketmine\event\entity\EntityEnterPortalEvent;
use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\event\level\ChunkUnloadEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerFishEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerHungerChangeEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerUseFishingRodEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\FurnaceInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\level\Level;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\level\particle\LargeExplodeParticle;
use pocketmine\level\particle\LavaDripParticle;
use pocketmine\level\sound\NoteblockSound;
use pocketmine\tile\Furnace;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\math\AxisAlignedBB;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\block\Block;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerAchievementAwardedEvent;
use pocketmine\level\sound\AnvilFallSound;
use Annihilation\Annihilation;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\network\protocol\SetSpawnPositionPacket;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\level\sound\BlazeShootSound;

class Arena extends ArenaManager implements Listener
{

    /** @var Annihilation $plugin */
    public $plugin;

    public $id;

    /** @var ArenaSchedule $task */
    public $task;

    /** @var PopupTask $popupTask */
    public $popupTask;

    /** @var KitManager $kitManager */
    public $kitManager;

    /** @var VotingManager $votingManager */
    public $votingManager;

    /** @var WorldManager $worldManager */
    public $worldManager;

    /** @var BossManager $bossManager */
    public $bossManager;

    /** @var EnderManager $enderManager */
    public $enderManager;

    /** @var DeathManager $deathManager */
    public $deathManager;

    public $phase = 0;
    public $starting = false;
    public $ending = false;

    private $gamesCount = 0;

    /** @var  Level $level */
    public $level;

    /** @var PlayerData[] $playersData */
    public $playersData = [];

    /** @var Player[] $players */
    public $players = [];

    /** @var  Vector3[] $data */
    public $data = [];

    public $maindata;

    /** @var  MySQLManager $mysql */
    public $mysql;

    /** @var \MTCore\MTCore $mtcore */
    public $mtcore;

    /** @var  Team $winnerteam */
    public $winnerteam;

    public $map;

    /** @var Shop */
    public $shopManager;

    public $isMapLoaded = false;

    public function __construct($id, Annihilation $plugin)
    {
        parent::__construct($this);
        $this->plugin = $plugin;
        $this->id = $id;
        $this->maindata = $this->plugin->arenas[$id];
        $this->mysql = $this->plugin->mysql;
        $this->mtcore = $this->plugin->mtcore;
        $this->data;
        $this->kitManager = new KitManager($this);
        $this->votingManager = new VotingManager($this);
        $this->worldManager = new WorldManager();
        $this->bossManager = new BossManager($this);
        $this->enderManager = new EnderManager($this);
        $this->deathManager = new DeathManager($this);
        $this->shopManager = new Shop();
        //$this->data = $this->getMapData($id);
        $this->registerTeams();
        $this->votingManager->createVoteTable();
        $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask($this->task = new ArenaSchedule($this), 20);
        $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask($this->popupTask = new PopupTask($this), 10);
    }

    public function onPlayerQuit(PlayerQuitEvent $e)
    {
        $this->handlePlayerQuit($e->getPlayer());
    }

    public function handlePlayerQuit(Player $p)
    {
        if ($this->inArena($p)) {

            $data = $this->getPlayerData($p);

            if ($data->getTeam() instanceof Team) {
                $this->getPlayerTeam($p)->removePlayer($p);
                //$p->setSpawn($data->getTeam()->getSpawnLocation());
            }

            $p->setMaxHealth(20);

            if ($this->phase >= 1 && $data->getTeam() instanceof Team) {
                if ($p->isAlive() && $p->getInventory() instanceof PlayerInventory) {
                    $data->saveInventory($p);
                }
            }

            $data->setLobby(false);
            unset($this->players[strtolower($p->getName())]);
            $this->plugin->mtcore->setLobby($p);
            //$this->checkAlive();
        }
    }

    public function joinToArena(Player $p)
    {
        if ($this->inArena($p)) {
            return;
        }

        $wasInGame = false;

        if (!$this->getPlayerData($p) instanceof PlayerData) {
            $data = $this->createPlayerData($p);
        } else {
            $data = $this->getPlayerData($p);
            if ($data->getTeam() instanceof Team) {
                $wasInGame = true;
            }
        }

        if ($this->phase >= 5 && !$wasInGame && !$p->isOp()) {
            $p->sendMessage($this->plugin->getPrefix() . TextFormat::RED . "You can not join in this phase");
            return;
        }

        if ($wasInGame === true && $this->phase >= 1 && !$data->getTeam()->getNexus()->isAlive()) {
            $p->sendMessage($this->plugin->getPrefix() . TextFormat::RED . "Your team nexus has been destroyed");
            return;
        }

        $this->players[strtolower($p->getName())] = $p;
        $this->plugin->mtcore->unsetLobby($p);

        if ($wasInGame) {
            $this->addToTeam($p, $data->getTeam());

            if ($this->phase >= 1) {
                $this->teleportToArena($p);
                return;
            }

            $data->setLobby(true);
        } else {
            $p->setNameTag($p->getName());
            $data->setLobby(true);
            $this->kitManager->addKitWindow($p);
        }

        $p->teleport($this->maindata['lobby']);
        $p->setSpawn($this->maindata['lobby']);
        $p->sendMessage($this->plugin->getPrefix() . TextFormat::GREEN . "Joining to $this->id...");
        $p->sendMessage(Annihilation::getPrefix() . TextFormat::GOLD . "Open your inventory to select kit");
        $this->checkLobby();
        return;
    }

    public function onHurt(EntityDamageEvent $e)
    {
        $entity = $e->getEntity();

        if ($entity instanceof IronGolem) {
            $e->setCancelled(false);
            if ($e->getFinalDamage() >= $entity->getHealth()) {
                $e->setCancelled();
                $entity->kill();

                $pname = "";

                if ($e instanceof EntityDamageByEntityEvent && ($damager = $e->getDamager()) instanceof Player) {
                    $pname = $this->getPlayerTeam($damager)->getColor() . $damager->getName();
                }

                if (strpos($entity->getNameTag(), "Celariel")) {
                    $this->bossManager->onBossDeath(2, $pname);
                } elseif (strpos($entity->getNameTag(), "Ferwin")) {
                    $this->bossManager->onBossDeath(1, $pname);
                }
            }
            return;
        }

        if ($e->isCancelled() || !$entity instanceof Player || !$this->inArena($entity)) {
            return;
        }

        if ($e->getCause() === 4 && $this->getPlayerData($entity)->getKit() == "acrobat") {
            $e->setCancelled();
            return;
        }

        if ($e instanceof EntityDamageByEntityEvent) {
            $victim = $e->getEntity();
            $killer = $e->getDamager();

            if ($killer instanceof Player && $victim instanceof Player) {
                $data = $this->getPlayerData($victim);

                $dataKiller = $this->getPlayerData($killer);

                if ($dataKiller->getTeam() == null || $data->getTeam() == null) {
                    $e->setCancelled();
                    return;
                }

                if (!$this->inArena($killer) || $dataKiller->getTeam()->getId() === $data->getTeam()->getId() || $this->phase === 0 || $victim->getLevel() === $this->plugin->level) {
                    $e->setCancelled();
                    return;
                }

                if ($dataKiller->getKit() == 'warrior') {
                    $e->setDamage($e->getDamage() + 1);
                }

                /*if ($e->getFinalDamage() < $victim->getHealth()) {
                    $killer->spawnTo($victim);
                }*/

                $data->setKiller($dataKiller);
            }
            return;
        } elseif ($e instanceof EntityDamageByChildEntityEvent) {
            $killer = $e->getDamager();
            $victim = $e->getEntity();
            if ($killer instanceof Player && $victim instanceof Player) {
                $data = $this->getPlayerData($victim);

                $dataKiller = $this->getPlayerData($killer);

                if ($dataKiller->getTeam()->getId() === $data->getTeam()->getId() || $this->phase === 0 || $victim->getLevel()->getId() === $this->plugin->level->getId()) {
                    $e->setCancelled();
                    return;
                }

                if ($dataKiller->getKit() == 'archer') {
                    $e->setDamage($e->getDamage() + 1);
                }

                $data->setKiller($dataKiller);
            }
            return;
        }
    }

    public function onBlockBreak(BlockBreakEvent $e)
    {
        $b = $e->getBlock();
        $p = $e->getPlayer();
        $item = $e->getItem();
        //$e->setInstaBreak(true);

        if($e->isBlocked()){
            $e->setCancelled();
            return;
        }

        if (!$this->inArena($p) && !$p->isOp()) {
            $e->setCancelled();
            return;
        }

        if ($this->phase < 1) {
            return;
        }

        if (!$this->contains($this->data['corner1']->x, $this->data['corner1']->y, $this->data['corner1']->z, $this->data['corner2']->x, $this->data['corner2']->y, $this->data['corner2']->z, new Vector3($b->x, $b->y, $b->z))) {
            $e->setCancelled();
            return;
        }

        /*if($b->getId() === Item::FURNACE && ($tile = $this->level->getTile($b)) instanceof Furnace) {
            if (!isset($tile->namedtag->Owner)) {
                return;
            }

            if (strval($tile->namedtag["Owner"]) != strtolower($player->getName())) {
                $e->setCancelled();
                $player->sendMessage(Annihilation::getPrefix() . TextFormat::RED . "This is not your furnace");
                return;
            }
        }*/

        if ($this->phase >= 1) {
            $blueNex = $this->data["1Nexus"];
            $redNex = $this->data["2Nexus"];
            $yellowNex = $this->data["3Nexus"];
            $greenNex = $this->data["4Nexus"];

            if ($b->getId() === Item::END_STONE) {
                if ($b->x == $blueNex->x && $b->y === $blueNex->y && $b->z === $blueNex->z) {
                    $e->setCancelled();
                    $this->breakNexus($p, $this->getTeam(1));
                } elseif ($b->x === $redNex->x && $b->y === $redNex->y && $b->z === $redNex->z) {
                    $e->setCancelled();
                    $this->breakNexus($p, $this->getTeam(2));
                } elseif ($b->x === $yellowNex->x && $b->y === $yellowNex->y && $b->z === $yellowNex->z) {
                    $e->setCancelled();
                    $this->breakNexus($p, $this->getTeam(3));
                } elseif ($b->x == $greenNex->x && $b->y == $greenNex->y && $b->z == $greenNex->z) {
                    $e->setCancelled();
                    $this->breakNexus($p, $this->getTeam(4));
                }

                if($item->isTool()){
                    $item->setDamage(0);
                    $p->getInventory()->setItemInHand($item);
                }

                return;
            }

            if ($this->contains($blueNex->x - 15, 0, $blueNex->z - 15, $blueNex->x + 15, 128, $blueNex->z + 15, new Vector3($b->x, $b->y, $b->z)) || $this->contains($redNex->x - 15, 0, $redNex->z - 15, $redNex->x + 15, 128, $redNex->z + 15, new Vector3($b->x, $b->y, $b->z)) || $this->contains($yellowNex->x - 15, 0, $yellowNex->z - 15, $yellowNex->x + 15, 128, $yellowNex->z + 15, new Vector3($b->x, $b->y, $b->z)) || $this->contains($greenNex->x - 15, 0, $greenNex->z - 15, $greenNex->x + 15, 128, $greenNex->z + 15, new Vector3($b->x, $b->y, $b->z))) {
                $e->setCancelled();
                $p->sendMessage(TextFormat::RED . "You can't destroy blocks close to the nexus");
                return;
            }

            if (!$this->contains($this->data["corner1"]->x, $this->data["corner1"]->y, $this->data["corner1"]->z, $this->data["corner2"]->x, $this->data["corner2"]->y, $this->data["corner2"]->z, $b)) {
                $p->sendMessage(TextFormat::RED . "You haven't permissions for that");
                $e->setCancelled();
                return;
            }

            if ($b->getId() === 14 || $b->getId() === 15 || $b->getId() === 16 || $b->getId() === 21 || $b->getId() === 56 || $b->getId() === 73 || $b->getId() === 74 || $b->getId() === 103 || $b->getId() === 129 || $b->getId() === 17 || $b->getId() === 162) {
                $this->task->push($b);
                $p->getInventory()->addItem($this->getDrops($p, $b));
                $p->addExperience($this->getExpFromBlock($b->getId()) * 3);

                //$item = $p->getInventory()->getItemInHand();
                if($item->isTool()){
                    $item->setDamage(0);
                    $p->getInventory()->setItemInHand($item);
                }

                $e->setDrops([]);

                $p->getInventory()->sendContents($p);
                return;
            } elseif ($b->getId() === 13) {
                $this->task->push($b);

                $e->setDrops([]);

                $drops = $this->getDrops($p, $b);

                if (is_array($drops)) {
                    foreach ($drops as $drop) {
                        $p->getInventory()->addItem($drop);
                    }
                } else {
                    $p->getInventory()->addItem($drops);
                }

                $p->addExperience($this->getExpFromBlock($b->getId()) * 3);

                if($item->isTool()){
                    $item->setDamage(0);
                    $p->getInventory()->setItemInHand($item);
                }

                $p->getInventory()->sendContents($p);
                return;
            } elseif($b->getId() === Item::CHEST && $this->isEnderChest($b)){
                $e->setCancelled();
            }elseif($b->getId() === Item::FURNACE && $this->isEnderFurnace($b)){
                $e->setCancelled();
            }elseif($b->getId() === Item::BREWING_STAND_BLOCK && $this->isEnderBrewing($b)){
                $e->setCancelled();
            }

            /*foreach ($b->getDrops($e->getItem()) as $drops) {
                if (empty($drops)) {
                    continue;
                }
                $p->getInventory()->addItem(Item::get($drops[0], $drops[1], $drops[2]));
            }*/


            /*foreach ($b->getDrops($player->getInventory()->getItemInHand()) as $drop) {
                if ($drop[2] > 0) {
                    $player->getInventory()->addItem(Item::get($drop[0], $drop[1], $drop[2]));
                }
            }*/

            //$player->getInventory()->sendContents($player);
        }
        /*if($b->getId() === 4){
            foreach($this->task->pool as $content){
                list($tick, $x, $y, $z, $id, $damage, $lvName) = explode(":", $content);
                if("$x:$y:$z:$lvName" == "$b->x:$b->y:$b->z:{$b->level->getName()}"){
                    $e->setCancelled();
                }
            }
            return;
        }*/
    }

    public function getExpFromBlock($id)
    {
        $dropExp = 0;

        switch ($id) {
            case Block::COAL_ORE:
                $dropExp = mt_rand(1, 2);
                break;
            case Block::LAPIS_ORE:
                $dropExp = mt_rand(2, 5);
                break;
            case Block::IRON_ORE:
                $dropExp = mt_rand(1, 2);
                break;
            case Block::GOLD_ORE:
                $dropExp = mt_rand(1, 2);
                break;
            case Block::REDSTONE_ORE:
                $dropExp = mt_rand(1, 5);
                break;
            case Block::DIAMOND_ORE:
                $dropExp = mt_rand(3, 7);
                break;
            case Block::EMERALD_ORE:
                $dropExp = mt_rand(1, 2);
                break;
            case 153:
                $dropExp = mt_rand(3, 7);
                break;
            case Block::WOOD:
                $dropExp = mt_rand(1, 2);
                break;
            case Block::WOOD2:
                $dropExp = mt_rand(1, 2);
                break;
            case Block::GRAVEL:
                $dropExp = 1;
                break;
        }

        return $dropExp;
    }

    public function getDrops(Player $p, Block $b)
    {
        switch ($b->getId()) {
            case Item::GRAVEL:
                $items = [Item::get(287, 0, mt_rand(0, 2)), Item::get(352, 0, mt_rand(0, 2)), Item::get(318, 0, mt_rand(0, 3)), Item::get(288, 0, mt_rand(0, 3)), Item::get(262, 0, mt_rand(0, 4))];

                $rnd = array_rand($items, mt_rand(1, 3));

                $final = [];
                if (is_array($rnd)) {
                    foreach ($rnd as $key) {
                        $final[] = $items[$key];
                    }
                } else {
                    $final = $items[$rnd];
                }

                return $final;
            case Item::GOLD_ORE:
                $item = Item::get(Item::GOLD_ORE, 0, 1);
                //$item = Item::get(Item::GOLD_INGOT, 0, 1);

                if ($this->getPlayerData($p)->getKit() === 'miner') {
                    $item->setCount(2);
                }
                return $item;
            case Item::IRON_ORE:
                $item = Item::get(Item::IRON_ORE, 0, 1);
                //$item = Item::get(Item::IRON_INGOT, 0, 1);

                if ($this->getPlayerData($p)->getKit() === 'miner') {
                    $item->setCount(2);
                }
                return $item;
            case Item::COAL_ORE:
                $item = Item::get(263, 0, 1);

                if ($this->getPlayerData($p)->getKit() === 'miner') {
                    $item->setCount(2);
                }
                return $item;
            case 17:
                $item = Item::get(17, 0, 1);

                if ($this->getPlayerData($p)->getKit() === 'lumberjack') {
                    $item->setCount(2);
                }

                return $item;
            case 21:
                $item = Item::get(21, 0, 16);

                if ($this->getPlayerData($p)->getKit() === 'miner') {
                    $item->setCount(32);
                }
                return $item;
            case 56:
                $item = Item::get(Item::DIAMOND, 0, 1);

                if ($this->getPlayerData($p)->getKit() === 'miner') {
                    $item->setCount(2);
                }
                return $item;
            case 73:
                $item = Item::get(73, 0, 1);

                if ($this->getPlayerData($p)->getKit() === 'miner') {
                    $item->setCount(2);
                }
                return $item;
            case 74:
                $item = Item::get(74, 0, 1);

                if ($this->getPlayerData($p)->getKit() === 'miner') {
                    $item->setCount(2);
                }
                return $item;
            case 103:
                return Item::get(360, 0, mt_rand(3, 7));
            case 129:
                $item = Item::get(388, 0, 1);

                if ($this->getPlayerData($p)->getKit() === 'miner') {
                    $item->setCount(2);
                }
                return $item;
            case 162:
                $item = Item::get(162, 0, 1);

                if ($this->getPlayerData($p)->getKit() === 'lumberjack') {
                    $item->setCount(2);
                }
                return $item;
        }

        return $b->getDrops(Item::get(0));
    }

    public function onBlockPlace(BlockPlaceEvent $e)
    {
        $player = $e->getPlayer();
        $b = $e->getBlock();
        if (!$this->inArena($player) || $this->inLobby($player)) {
            return;
        }
        if (!$this->phase >= 1) {
            return;
        }
        if ($this->phase >= 1) {
            if (!$this->contains($this->data['corner1']->x, $this->data['corner1']->y, $this->data['corner1']->z, $this->data['corner2']->x, $this->data['corner2']->y, $this->data['corner2']->z, new Vector3($b->x, $b->y, $b->z))) {
                $e->setCancelled();
                return;
            }

            if ($b->getId() === 14 || $b->getId() === 15 || $b->getId() === 16 || $b->getId() === 21 || $b->getId() === 56 || $b->getId() === 73 || $b->getId() === 74 || $b->getId() === 129 || $b->getId() === 17 || $b->getId() === 162 || $b->getId() === 103 || $b->getId() === 13 || $b->getId() === Item::BEDROCK) {
                $e->setCancelled();
                return;
            }

            $blueNex = $this->data["1Nexus"];
            $redNex = $this->data["2Nexus"];
            $yellowNex = $this->data["3Nexus"];
            $greenNex = $this->data["4Nexus"];
            if ($this->contains($blueNex->x - 15, 0, $blueNex->z - 15, $blueNex->x + 15, 128, $blueNex->z + 15, new Vector3($b->x, $b->y, $b->z)) || $this->contains($redNex->x - 15, 0, $redNex->z - 15, $redNex->x + 15, 128, $redNex->z + 15, new Vector3($b->x, $b->y, $b->z)) || $this->contains($yellowNex->x - 15, 0, $yellowNex->z - 15, $yellowNex->x + 15, 128, $yellowNex->z + 15, new Vector3($b->x, $b->y, $b->z)) || $this->contains($greenNex->x - 15, 0, $greenNex->z - 15, $greenNex->x + 15, 128, $greenNex->z + 15, new Vector3($b->x, $b->y, $b->z))) {
                $e->setCancelled();
                $player->sendMessage(TextFormat::RED . "You can't place blocks close to the nexus");
                return;
            }

            $data = $this->getPlayerData($player);

            if ($b->getId() === Item::SOUL_SAND && $data->getKit() == "operative") {
                if ($this->kitManager->getKit("operative")->placed($player) !== false) {
                    return;
                }
                $this->kitManager->getKit("operative")->onPlace($player, $b);
            }
        }
    }

    public function onBucketFill(PlayerBucketFillEvent $e)
    {
        $p = $e->getPlayer();
        if ($p instanceof Player) {
            $e->setCancelled();
        }
    }

    public function onBucketEmpty(PlayerBucketEmptyEvent $e)
    {
        $p = $e->getPlayer();
        if ($p instanceof Player) {
            $e->setCancelled();
        }
    }

    public function onAchievement(PlayerAchievementAwardedEvent $e)
    {
        $p = $e->getPlayer();
        if ($p instanceof Player) {
            $e->setCancelled();
        }
    }

    private static $blockedItems = [Item::LEATHER_BOOTS, Item::LEATHER_CAP, Item::LEATHER_PANTS, Item::LEATHER_TUNIC, Item::WOODEN_PICKAXE, Item::WOODEN_SWORD, Item::WOODEN_AXE, Item::COMPASS];

    public function onDeath(PlayerDeathEvent $e)
    {
        $p = $e->getEntity();
        //$team = $this->getPlayerTeam($p);
        $e->setDeathMessage("");

        if (!$this->inArena($p) || $this->inLobby($p)) {
            return;
        }


        if ($this->phase >= 1) {
            $data = $this->getPlayerData($p);

            new NormalQuery($this->plugin, MySQLManager::DEATH, [$p->getName()]);
            $this->deathManager->onDeath($e, $data);

            $drops = [];

            foreach ($e->getDrops() as $item) {
                if($item->getCustomName() != TextFormat::GOLD."SoulBound"){
                    $drops[] = $item;
                }
            }

            $e->setDrops($drops);

            if ($data->getTeam()->getNexus()->getHealth() <= 0) {
                $this->handlePlayerQuit($p);
            }
        }
    }

    public function onRespawn(PlayerRespawnEvent $e)
    {
        $p = $e->getPlayer();

        if (!$this->inArena($p)) {
            return;
        }

        if ($this->getPlayerTeam($p) !== 0 && $this->getPlayerTeam($p) !== false && $this->inArena($p)) {
            $this->kitManager->giveKit($p);
            return;
        }

        if ($this->getPlayerData($p)->getKit() == "berserker") {
            $p->setMaxHealth(14);
        }
    }

    /*public function onEntitySpawn(EntitySpawnEvent $ev){
        $e = $ev->getEntity();
        if($e instanceof IronGolem && $e->getHealth() === $e->getMaxHealth()){
            $e->setMaxHealth(200);
            $e->setHealth(200);
        }
    }*/

    public function onInteract(PlayerInteractEvent $e)
    {
        $b = $e->getBlock();
        $p = $e->getPlayer();

        $action = $e->getAction();

        if ($e->isCancelled() || $action === $e::LEFT_CLICK_AIR || $action === $e::RIGHT_CLICK_AIR) {
            return;
        }

        if ($b->getId() === Item::WALL_SIGN || $b->getId() === Item::SIGN_POST) {
            switch ("$b->x:$b->y:$b->z") {
                case "{$this->maindata['sign']->x}:{$this->maindata['sign']->y}:{$this->maindata['sign']->z}":
                    $this->joinToArena($p);
                    break;
                case "{$this->maindata['1sign']->x}:{$this->maindata['1sign']->y}:{$this->maindata['1sign']->z}":
                    $this->joinTeam($p, 1);
                    break;
                case "{$this->maindata['2sign']->x}:{$this->maindata['2sign']->y}:{$this->maindata['2sign']->z}":
                    $this->joinTeam($p, 2);
                    break;
                case "{$this->maindata['3sign']->x}:{$this->maindata['3sign']->y}:{$this->maindata['3sign']->z}":
                    $this->joinTeam($p, 3);
                    break;
                case "{$this->maindata['4sign']->x}:{$this->maindata['4sign']->y}:{$this->maindata['4sign']->z}":
                    $this->joinTeam($p, 4);
                    break;
            }

            if (!$this->phase >= 1 || !$this->inArena($p)) {
                return;
            }

            /** @var Sign $sign */
            $sign = $this->level->getTile($b);

            /*if ($sign instanceof Sign && stripos(strtolower($sign->getText()[0]), "shop")) {
                if (stripos(strtolower($sign->getText()[1]), "brewing")) {
                    //$p->addWindow($this->getPlayerTeam($p)->getBrewingShop()->getInventory());
                } elseif (stripos(strtolower($sign->getText()[1]), "weapon")) {
                    //$p->addWindow($this->getPlayerTeam($p)->getWeaponsShop()->getInventory());
                }
            }*/

            return;
        }

        if (!$this->phase >= 1 || !$this->inArena($p)) {
            return;
        }

        if($p->getLevel()->getId() !== $this->level->getId()){
            return;
        }

        if($b->getId() === Item::CHEST) {
            $data = $this->getPlayerData($p);

            if ($this->isEnderChest($b, $data->getTeam()->getId())) {
                $e->setCancelled();
                $p->sendMessage(Annihilation::getPrefix() . TextFormat::GRAY . "This is your team's Ender Chest. Any items you store or smelt here are safe from all other players.");
                if (!$data->getChest() instanceof ChestInventory) {
                    $this->enderManager->createChest($p);
                }

                $p->addWindow($data->getChest());
            } else if($this->phase < 4 && ($b->equals($this->data["1Brewing"]) || $b->equals($this->data["2Brewing"]) || $b->equals($this->data["3Brewing"]) || $b->equals($this->data["4Brewing"]))){
                $e->setCancelled();
                $p->sendMessage(Annihilation::getPrefix().TextFormat::RED."You can't use brewing until phase IV");
            }
        } elseif ($b->getId() === Item::FURNACE || $b->getId() === Item::BURNING_FURNACE) {
            $data = $this->getPlayerData($p);

            if ($this->isEnderFurnace($b, $data->getTeam()->getId())) {
                $e->setCancelled();
                $p->sendMessage(Annihilation::getPrefix() . TextFormat::GRAY . "This is your team's Ender Furnace. Any items you store or smelt here are safe from all other players.");

                if (!$data->getFurnace() instanceof EnderFurnaceInventory) {
                    $this->enderManager->createFurnace($p);
                }

                /** @var EnderFurnaceInventory $furnace */
                $furnace = $data->getFurnace();

                //$this->level->addTile($furnace);

                $p->addWindow($furnace);

            }
        } elseif($b->getId() === Item::BREWING_STAND_BLOCK){
            $data = $this->getPlayerData($p);

            if($this->isEnderBrewing($b, $data->getTeam()->getId())) {

                $e->setCancelled();
                $p->sendMessage(Annihilation::getPrefix() . TextFormat::GRAY . "This is your team's Ender Brewing. Any items you store or brew here are safe from all other players.");

                if (!$data->getBrewing() instanceof EnderBrewingInventory) {
                    $this->enderManager->createBrewing($p);
                }

                /** @var EnderBrewingInventory $furnace */
                $brewing = $data->getBrewing();

                //$this->level->addTile($furnace);

                $p->addWindow($brewing);
            }
        } elseif ($b->getId() === Item::ENCHANT_TABLE) {
                $item = clone $e->getItem();

                /** @var int[] $swordEnch */
                $swordEnch = [9, 10, 11, 12, 13, 14, 17];
                /** @var int[] $armorEnch */
                $armorEnch = [Enchantment::TYPE_ARMOR_FALL_PROTECTION, Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, Enchantment::TYPE_ARMOR_PROTECTION, Enchantment::TYPE_ARMOR_THORNS];
                /** @var int[] $bowEnch */
                $bowEnch = [17, 19, 20, 21, 22];
                /** @var int[] $pickaxeEnch */
                $pickaxeEnch = [15, 16, 17, 18];

                $lvl = $p->getExpLevel();

            if($item->isTool()){
                if ($item->isSword()) {
                    $type = $swordEnch;
                } elseif ($item->isPickaxe()) {
                    $type = $pickaxeEnch;
                } elseif ($item->getId() > 297 and $item->getId() < 314) {
                    $type = $armorEnch;
                } elseif ($item->isAxe()) {
                    $type = $pickaxeEnch;
                } elseif ($item->getId() == Item::BOW) {
                    $type = $bowEnch;
                } else{
                    return;
                }
            } elseif($item->isArmor()){
                $type = $armorEnch;
            } else{
                return;
            }

            $enchCount = 0;

                if($p->getExpLevel() < 7) {
                    $p->sendMessage(Annihilation::getPrefix().TextFormat::RED."§cYou do not have enough levels");
                    return;
                }

            if ($lvl >= 7 and $lvl < 10) {
                $enchCount = mt_rand(1, mt_rand(1, 3));
                $lv = mt_rand(1, 2);
            } elseif ($lvl >= 10 and $lvl < 20) {
                $lv = mt_rand(1, 3);
                $enchCount = mt_rand(2, 3);
            } elseif ($lvl >= 20) {
                $lv = mt_rand(3, 5);
                $enchCount = mt_rand(mt_rand(0, 7) === 7 ? 1 : 2, mt_rand(1, 5) === 1 ? 4 : 3);
            }

            /*    $id = $type[\mt_rand(0, \count($type) - 1)];
                $xp = 0;

            switch ($lv) {
                case 1:
                    $xp = \mt_rand(3, 7);
                    break;
                case 2:
                    if (Enchantment::getMaxLevel($id) < 2){
                        $id = 1;
                        $xp = \mt_rand(3, 7);
                        break;
                    }
                    $xp = \mt_rand(8, 13);
                    break;
                case 3:
                    if (Enchantment::getMaxLevel($id) === 1){
                        $id = 1;
                        $xp = \mt_rand(3, 7);
                        break;
                    }
                    if (Enchantment::getMaxLevel($id) === 2){
                        $id = 2;
                        $xp = \mt_rand(8, 13);
                        break;
                    }
                        $xp = \mt_rand(11, 19);
                        break;
                case 4:
                    if (Enchantment::getMaxLevel($id) === 1){
                        $id = 1;
                        $xp = \mt_rand(3, 7);
                        break;
                    }
                    if (Enchantment::getMaxLevel($id) === 2){
                        $id = 2;
                        $xp = \mt_rand(8, 13);
                        break;
                    }
                    if (Enchantment::getMaxLevel($id) === 3){
                        $id = 3;
                        $xp = \mt_rand(11, 19);
                        break;
                    }
                        $xp = \mt_rand(17, 30);
                        break;
                case 5:
                    if (Enchantment::getMaxLevel($id) === 1){
                        $id = 1;
                        $xp = \mt_rand(3, 7);
                        break;
                    }
                    if (Enchantment::getMaxLevel($id) === 2){
                        $id = 2;
                        $xp = \mt_rand(8, 13);
                        break;
                    }
                    if (Enchantment::getMaxLevel($id) === 3){
                        $id = 3;
                        $xp = \mt_rand(11, 19);
                        break;
                    }
                    if (Enchantment::getMaxLevel($id) === 4){
                        $id = 4;
                        $xp = \mt_rand(17, 30);
                        break;
                    }
                        $xp = 30;
                        break;
            }*/

            for($i = 0; $i < $enchCount; $i++){
                $id = $type[array_rand($type, 1)];
                $ench = Enchantment::getEnchantment($id);
                $ench->setLevel($lv);

                $item->addEnchantment($ench);

                if ($lvl >= 7 and $lvl < 10) {
                    $lv = \mt_rand(1, 2);
                } elseif ($lvl >= 10 and $lvl < 20) {
                    $lv = \mt_rand(1, 3);
                } elseif ($lvl >= 20) {
                    $lv = \mt_rand(3, 5);
                }
            }

            $p->setExpLevel($p->getExpLevel() <= 30 ? 0 : $p->getExpLevel() - 30);
            $p->getInventory()->setItemInHand($item);
            $p->getInventory()->sendHeldItem($p);
        }
    }

    public function onChat(PlayerChatEvent $e)
    {
        if ($e->isCancelled()) {
            return;
        }

        $e->setCancelled();

        $p = $e->getPlayer();

        $team = $this->getPlayerTeam($p);

        if (!$team instanceof Team) {
            return;
        }

        if ((strpos($e->getMessage(), "!") === 0 && strlen($e->getMessage()) > 1)) {
            $this->messageAllPlayers($e->getMessage(), $p);
            return;
        }

        $team->message($e->getMessage(), $p);
    }

    public function teleportToArena(Player $p)
    {
        $data = $this->getPlayerData($p);
        $team = $data->getTeam();
        $p->teleport($team->getSpawnLocation());
        $p->getInventory()->clearAll();
        $p->setSpawn($team->getSpawnLocation());
        $p->setExpLevel(0);
        $p->setExperience(0);
        $p->setGamemode(0);

        $this->plugin->getServer()->sendRecipeList($p);

        $data->setLobby(false);
        if (($inv = $data->getSavedInventory()) instanceof VirtualInventory) {
            $this->loadInventory($p, $inv); //echo "loading inventory"; print_r($inv);
            $data->removeInventory();

            if ($data->getKit() == "berserker") {
                $p->setHealth(14);
                $p->setMaxHealth(14);
            }
            return;
        }
        $this->kitManager->giveKit($p);
    }

    public function stopGame()
    {
        $this->phase = 0;

        foreach ($this->getAllPlayers() as $p) {
            $p->teleport($this->plugin->mainLobby);
            $p->setSpawn($this->plugin->mainLobby);
            $this->mtcore->setLobby($p);
        }

        $this->unsetAllPlayers();
        $this->registerTeams();
        //$this->worldManager->resetWorld($this->map);
        $this->votingManager->createVoteTable();
        $this->phase = 0;
        $this->ending = false;
        $this->starting = false;
        $this->task->time = 0;
        $this->task->time1 = 120;
        $this->task->pool = [];
        $this->task->popup = 0;

        $this->gamesCount++;

        foreach ($this->plugin->getServer()->getOnlinePlayers() as $pl) {
            $pl->kick(TextFormat::GOLD . "MineTox" . TextFormat::BLUE . " is restarting.", false);
        }

        $this->plugin->getServer()->forceShutdown();
        //(\pocketmine\kill(getmypid())); //konečně :D možná

        /*if($this->gamesCount >= 2){
            Server::getInstance()->shutdown();
        }*/
    }

    public function startGame($force = false)
    {
        if(!$this->isMapLoaded){
            return;
        }

        $this->task->time1 = 120;
        $this->starting = false;

        if (count($this->getAllPlayers()) < 16 && $force === false) {
            $this->messageAllPlayers(Annihilation::getPrefix() . TextFormat::RED . "need 16 players to start");
            return;
        }

        if (!$this->plugin->getServer()->isLevelLoaded($this->map)) {
            $this->plugin->getServer()->loadLevel($this->map);
        }

        $this->level = $this->plugin->getServer()->getLevelByName($this->map);

        $kits = $this->kitManager;

        $data = [];

        foreach ($this->data as $key => $v) {
            if (is_numeric($key1 = substr($key, 0, 1))) {
                $data[intval($key1)][strtolower(substr($key, 1))] = $v;
            }
        }

        for ($i = 1; $i < 5; $i++) {
            $this->getTeam($i)->setData($data[$i], $this);
        }

        foreach ($this->getPlayersInTeams() as $p) {
            $p->setHealth($p->getMaxHealth());
            $this->teleportToArena($p);
            //$kits->giveKit($p);
            /*$team = $this->getPlayerTeam($p);
            $pk = new SetSpawnPositionPacket();
            $pk->x = $this->data[$team->getId()."Nexus"]->x;
            $pk->y = $this->data[$team->getId()."Nexus"]->y;
            $pk->z = $this->data[$team->getId()."Nexus"]->z;*/
        }

        foreach ($this->data["diamonds"] as $v) {
            $this->level->setBlock($v, Block::get(0), true);
        }

        $this->changePhase(1);
    }

    public function broadcastResults($winner)
    {
        foreach ($this->getAllPlayers() as $p) {
            $tip = TextFormat::GOLD . TextFormat::BOLD . "-----------------------------";
            $tip .= TextFormat::DARK_RED . TextFormat::BOLD . "\n     * CONGRATULATIONS *";
            $tip .= "\n     " . TextFormat::BOLD . $winner . TextFormat::DARK_RED . " team wins!";
            $tip .= TextFormat::GOLD . TextFormat::BOLD . "\n-----------------------------";
            $p->sendTip($tip);
        }
    }

    public function spawnDiamonds()
    {
        foreach ($this->data["diamonds"] as $d) {
            $this->level->setBlock($d, Block::get(56, 0), true);
        }
    }

    public function contains($x, $y, $z, $x1, $y1, $z1, Vector3 $pos)
    {
        $axis = new AxisAlignedBB($x, $y, $z, $x1, $y1, $z1);
        if ($axis->isVectorInside($pos)) {
            return true;
        }
        return false;
    }

    public function checkAlive()
    {
        if ($this->phase <= 0 || $this->ending === true) {
            return;
        }

        if (count($this->getPlayersInTeams()) <= 0 && $this->phase > 4) {
            $this->stopGame();
        }
    }

    public function breakNexus(Player $player, Team $damagedTeam)
    {
        if (!$this->inArena($player)) {
            return;
        }

        $nexus = $damagedTeam->getNexus();

        if ($nexus->getHealth() < 1) {
            return;
        }

        $team = $this->getPlayerTeam($player);

        if ($team->getId() === $damagedTeam->getId()) {
            $player->sendMessage(TextFormat::RED . "You can't break your own nexus");
            return;
        }

        if ($this->phase <= 1) {
            $player->sendMessage(TextFormat::RED . "You can not break nexus until phase II");
            return;
        }

        $item = $player->getInventory()->getItemInHand();

        if ($item->isTool()) {
            $item->setDamage(max(0, $item->getDamage() - 1));
        }

        $this->level->addParticle(new LavaDripParticle(self::randVector($nexus->getPosition())));
        //$this->level->addParticle(new CriticalParticle(self::randVector($nexus->getPosition())));

        $this->level->addSound(new AnvilFallSound($nexus->getPosition()), $this->level->getChunkPlayers($nexus->getPosition()->x >> 4, $nexus->getPosition()->z >> 4));

        foreach ($team->getPlayers() as $p) {
            $p->sendMessage($team->getColor() . $player->getName() . TextFormat::DARK_GRAY . " has damaged the " . $damagedTeam->getColor() . ucfirst($damagedTeam->getName()) . " team's Nexus!");
        }

        $sound = new AnvilFallSound($nexus->getPosition());

        foreach ($damagedTeam->getPlayers() as $p) {
            $sound->setComponents($p->x, $p->y, $p->z);
            $this->level->addSound($sound, [$p]);
        }

        $nexus->damage();

        new NormalQuery($this->plugin, MySQLManager::NEXUS_DAMAGE, [$player->getName()]);
        new NormalQuery($this->plugin, "tokens", [strtolower($player->getName())], 3, "freezecraft");

        if ($this->getPlayerData($player)->getKit() == "handyman") {
            if ($this->kitManager->getKit("handyman")->calculateDamage($this->phase)) {
                $team->getNexus()->setHealth($team->getNexus()->getHealth() + 1);
            }
        }

        if ($this->phase === 5 && $nexus->getHealth() >= 1) {
            $nexus->damage();
            new NormalQuery($this->plugin, MySQLManager::NEXUS_DAMAGE, [$player->getName()]);
        }

        if ($nexus->getHealth() <= 0) {
            $this->onNexusDestroy($player, $damagedTeam);
        }
    }

    public function joinTeam(Player $p, $team, $forceJoin = false)
    {
        $team = $this->getTeam($team);

        if (!$this->inLobby($p)) {
            return;
        }

        $pTeam = $this->getPlayerTeam($p);
        if ($pTeam instanceof Team && $pTeam->getId() == $team->getId() && !$p->isOp()) {
            $p->sendPopup($this->plugin->getPrefix() . TextFormat::GRAY . "You are already in " . $team->getColor() . $team->getName() . " team");
            return;
        }

        if (!$this->isTeamFree($team) && !$p->isOp() && !$forceJoin) {
            $p->sendPopup($this->plugin->getPrefix() . TextFormat::GRAY . "This team is full");
            return;
        }

        if ($this->phase >= 5 && $forceJoin == false && !$p->isOp()) {
            $p->sendMessage($this->plugin->getPrefix() . TextFormat::GRAY . "You can't join in this phase");
            $p->teleport($this->plugin->mainLobby);
            return;
        }

        if ($this->getPlayerTeam($p) instanceof Team) {
            $p->sendMessage($this->plugin->getPrefix() . TextFormat::GRAY . " You can not change teams");
            return;
        }

        $this->addToTeam($p, $team);
        $p->sendMessage($this->plugin->getPrefix() . TextFormat::GRAY . "Joined " . $team->getColor() . $team->getName());

        if ($this->phase >= 1) {
            $this->teleportToArena($p);
        }
    }

    public function changePhase($phase)
    {
        switch ($phase) {
            case 1:
                $this->phase = 1;
                $this->messageAllPlayers(TextFormat::GRAY . "===========[ " . TextFormat::DARK_AQUA . "Progress" . TextFormat::GRAY . " ]===========\n"
                    . TextFormat::BLUE . "Phase I " . TextFormat::GRAY . "has started\n"
                    . TextFormat::GRAY . "Each nexus is invicible until Phase II\n"
                    . TextFormat::GRAY . "==================================");
                break;
            case 2:
                $this->phase = 2;
                $this->bossManager->spawnBoss(1);
                $this->bossManager->spawnBoss(2);
                $this->messageAllPlayers(TextFormat::GRAY . "===========[ " . TextFormat::DARK_AQUA . "Progress" . TextFormat::GRAY . " ]===========\n"
                    . TextFormat::GREEN . "Phase II " . TextFormat::GRAY . "has started\n"
                    . TextFormat::GRAY . "Each nexus is no longer invicible\n"
                    . TextFormat::GRAY . "Boss Iron Golems will now spawn\n"
                    . TextFormat::GRAY . "==================================");
                break;
            case 3:
                $this->phase = 3;
                $this->messageAllPlayers(TextFormat::GRAY . "===========[ " . TextFormat::DARK_AQUA . "Progress" . TextFormat::GRAY . " ]===========\n"
                    . TextFormat::YELLOW . "Phase III " . TextFormat::GRAY . "has started\n"
                    . TextFormat::GRAY . "Diamonds now spawn in the middle\n"
                    . TextFormat::GRAY . "==================================");
                $this->spawnDiamonds();
                break;
            case 4:
                $this->phase = 4;
                $this->messageAllPlayers(TextFormat::GRAY . "===========[ " . TextFormat::DARK_AQUA . "Progress" . TextFormat::GRAY . " ]===========\n"
                    . TextFormat::GOLD . "Phase IV " . TextFormat::GRAY . "has started\n"
                    . TextFormat::GRAY . "Now you can brew strength\n"
                    . TextFormat::GRAY . "==================================");
                break;
            case 5:
                $this->phase = 5;
                $this->messageAllPlayers(TextFormat::GRAY . "===========[ " . TextFormat::DARK_AQUA . "Progress" . TextFormat::GRAY . " ]===========\n"
                    . TextFormat::RED . "Phase V " . TextFormat::GRAY . "has started\n"
                    . TextFormat::RED . "Double nexus damage\n"
                    . TextFormat::GRAY . "==================================");
                break;
        }

        $this->plugin->getServer()->getNetwork()->setName(TextFormat::GOLD . " MineTox " . TextFormat::BLUE . TextFormat::BOLD . "Annihilation  ".$this->popupTask->getDisplayPhase($phase));
    }

    public function checkLobby()
    {
        if ($this->phase >= 1) {
            return;
        }
        if (count($this->getAllPlayers()) >= 16) {
            $this->starting = true;
        }
    }

    public function selectMap($force = false)
    {
        if (count($this->getAllPlayers()) < 1 && $force === false) {
            $this->messageAllPlayers($this->plugin->getPrefix() . TextFormat::RED . "Need 16 players to start");
            $this->starting = false;
            $this->task->startTime = 60;
            return;
        }
        $stats = $this->votingManager->stats;
        asort($stats);
        $map = $this->votingManager->currentTable[array_keys($stats)[2] - 1];

        if ($this->plugin->getServer()->isLevelLoaded($map)) {
            $this->plugin->getServer()->unloadLevel($this->plugin->getServer()->getLevelByName($map));
        }

        //$this->worldManager->deleteWorld($map);
        //$this->worldManager->addWorld($map);

        $this->isMapLoaded = false;
        new WorldCopyTask($this->plugin, $map, $this->id, $this->plugin->getServer()->getDataPath(), $force);

        //$this->plugin->getServer()->loadLevel($map);
        $this->map = $map;
        $this->data = $this->plugin->maps[$map];
        foreach ($this->getAllPlayers() as $p) {
            if ($p->isOnline()) {
                $p->sendMessage(TextFormat::BOLD . TextFormat::YELLOW . $map . TextFormat::GOLD . " was chosen");
            }
        }
    }

    public function loadInventory(Player $p, VirtualInventory $inv)
    {
        $newInv = $p->getInventory();

        $newInv->setContents($inv->getContents());
        $newInv->setArmorContents($inv->armor);

        foreach ($inv->hotbar as $slot => $index) {
            $newInv->setHotbarSlotIndex($index, $slot);
        }

        $p->setExperience($inv->xp);
        $p->setExpLevel($inv->xplevel);
        $p->setFood($inv->hunger);

        $newInv->sendContents($p);
        $newInv->sendArmorContents($p);
    }

    public function unsetAllPlayers()
    {
        $this->playersData = [];
        $this->players = [];
    }

    public function getAliveNexuses()
    {
        $nexuses = [];
        for ($i = 1; $i < 5; $i++) {
            if ($this->getTeam($i)->getNexus()->getHealth() > 0) {
                $nexuses[] = $i;
            }
        }
        return $nexuses;
    }

    public function onItemHeld(PlayerItemHeldEvent $e)
    {
        $p = $e->getPlayer();

        if (!$this->inArena($p)) {
            return;
        }
        if ($this->inLobby($p)) {
            $this->kitManager->itemHeld($e->getPlayer(), $e->getInventorySlot());
            $e->setCancelled();
            return;
        }
    }

    public function inLobby(Player $p)
    {
        return $this->getPlayerData($p) instanceof PlayerData ? $this->getPlayerData($p)->isInLobby() : false;
    }

    public function onWaterFlow(BlockSpreadEvent $e)
    {
        $e->setCancelled();
    }

    public function wasInArena(Player $p)
    {
        return $this->getPlayerData($p)->wasInGame();
    }

    public function onNexusDestroy(Player $p, Team $damagedTeam)
    {
        $nexus = $damagedTeam->getNexus();

        $pos = $nexus->getPosition();
        new NormalQuery($this->plugin, MySQLManager::NEXUS_DESTROY, [$p->getName()]);
        new NormalQuery($this->plugin, "tokens", [strtolower($p->getName())], 40, "freezecraft");

        $pk = new ExplodePacket();
        $pk->radius = 1;
        $explode = new ExplodePacket();
        $explode->x = $pos->x;
        $explode->y = $pos->y;
        $explode->z = $pos->z;
        $explode->radius = 5;

        Server::broadcastPacket($this->level->getChunkPlayers($pos->x >> 4, $pos->z >> 4), $explode);

        $this->level->addParticle(new LargeExplodeParticle(self::randVector($pos)));

        $team = $this->getPlayerTeam($p);
        $msg = TextFormat::GRAY . "===============[ " . TextFormat::DARK_AQUA .$damagedTeam->getColor(). "Nexus Destroyed" . TextFormat::GRAY . " ]===============\n"
            . $team->getColor() . $p->getName() . TextFormat::GRAY . " from " . $team->getColor() . ucfirst($team->getName()) . TextFormat::GRAY . " destroyed " . $damagedTeam->getColor() . ucfirst($damagedTeam->getName()). "'s" . TextFormat::GRAY . "  Nexus!\n"
            . TextFormat::GRAY . str_repeat("=", 47);

        foreach ($this->getAllPlayers() as $pl) {
            $pk->x = $pl->x;
            $pk->y = $pl->y;
            $pk->z = $pl->z;
            $pl->dataPacket($pk);


            $pl->sendMessage($msg);
        }

        new NormalQuery($this->plugin, MySQLManager::LOSE, array_keys($damagedTeam->getPlayers()));

        foreach ($damagedTeam->getPlayers() as $pl) {
            $pl->setSpawn($this->plugin->mainLobby);
        }

        $this->checkNexuses();
    }

    /*public function onEntityDeath(EntityDeathEvent $e){
        $entity = $e->getEntity();
        $cause = $entity->getLastDamageCause();

        if($entity instanceof IronGolem) {
            $pname = "";

            if ($cause instanceof EntityDamageByEntityEvent && ($damager = $cause->getDamager()) instanceof Player) {
                $pname = $this->getPlayerTeam($damager)->getColor() . $damager->getName();
            }

            if (strpos($entity->getNameTag(), "Celariel")) {
                $this->bossManager->onBossDeath(2, $pname);
                $e->setDrops([]);
            } elseif (strpos($entity->getNameTag(), "Ferwin")) {
                $this->bossManager->onBossDeath(1, $pname);
                $e->setDrops([]);
            }
        }
    }*/

    public function isEnderChest(Vector3 $b, int $team = 0)
    {
        $x = $b->x;
        $y = $b->y;
        $z = $b->z;

        if($team > 0){
            return $x === $this->data[$team.'Chest']->x && $y === $this->data[$team.'Chest']->y && $z === $this->data[$team.'Chest']->z;
        }

        return ($x === $this->data['1Chest']->x && $y === $this->data['1Chest']->y && $z === $this->data['1Chest']->z) || ($x === $this->data['2Chest']->x && $y === $this->data['2Chest']->y && $z === $this->data['2Chest']->z) || ($x === $this->data['3Chest']->x && $y === $this->data['3Chest']->y && $z === $this->data['3Chest']->z) || ($x === $this->data['4Chest']->x && $y === $this->data['4Chest']->y && $z === $this->data['4Chest']->z);
    }

    public function isEnderFurnace(Vector3 $b, int $team = 0)
    {
        $x = $b->x;
        $y = $b->y;
        $z = $b->z;

        if($team > 0){
            return $x === $this->data[$team.'Furnace']->x && $y === $this->data[$team.'Furnace']->y && $z === $this->data[$team.'Furnace']->z;
        }

        return ($x === $this->data['1Furnace']->x && $y === $this->data['1Furnace']->y && $z === $this->data['1Furnace']->z) || ($x === $this->data['2Furnace']->x && $y === $this->data['2Furnace']->y && $z === $this->data['2Furnace']->z) || ($x === $this->data['3Furnace']->x && $y === $this->data['3Furnace']->y && $z === $this->data['3Furnace']->z) || ($x === $this->data['4Furnace']->x && $y === $this->data['4Furnace']->y && $z === $this->data['4Furnace']->z);
    }

    public function isEnderBrewing(Vector3 $v, int $team = 0)
    {
        $x = $v->x;
        $y = $v->y;
        $z = $v->z;

        if($team > 0){
            return $x === $this->data[$team.'EnderBrewing']->x && $y === $this->data[$team.'EnderBrewing']->y && $z === $this->data[$team.'EnderBrewing']->z;
        }

        return ($x === $this->data['1EnderBrewing']->x && $y === $this->data['1EnderBrewing']->y && $z === $this->data['1EnderBrewing']->z) || ($x === $this->data['2EnderBrewing']->x && $y === $this->data['2EnderBrewing']->y && $z === $this->data['2EnderBrewing']->z) || ($x === $this->data['3EnderBrewing']->x && $y === $this->data['3EnderBrewing']->y && $z === $this->data['3EnderBrewing']->z) || ($x === $this->data['4EnderBrewing']->x && $y === $this->data['4EnderBrewing']->y && $z === $this->data['4EnderBrewing']->z);
    }

    public function onItemTake(InventoryPickupItemEvent $e)
    {
        $inv = $e->getInventory();
        $p = $inv->getHolder();
        if ($p instanceof Player) {
        }
    }

    public static function randy($p, $r, $o)
    {
        return $p + (mt_rand() / mt_getrandmax()) * $r + $o;
    }

    public static function randVector(Vector3 $center)
    {
        return new Vector3(self::randy($center->getX(), 2, -0.5),
            self::randy($center->getY(), 0.5, 0.5),
            self::randy($center->getZ(), 2, -0.5));
    }

    public function onItemDrop(PlayerDropItemEvent $e)
    {
        $p = $e->getPlayer();
        if (!$this->inArena($p) || $this->inLobby($p)) {
            $e->setCancelled(true);
            return;
        }
        $item = $e->getItem();

        $blockedItems = [Item::LEATHER_BOOTS, Item::LEATHER_CAP, Item::LEATHER_PANTS, Item::LEATHER_TUNIC, Item::WOODEN_PICKAXE, Item::WOODEN_SWORD, Item::WOODEN_AXE];

        if ($item->getCustomName() == TextFormat::GOLD."SoulBound") {
            $e->setCancelled();
            $p->getInventory()->setItemInHand(Item::get(0, 0, 0));
        }

        /*if(isset($item->getNamedTag()->Soulbound)){
            $e->setCancelled(true);
            $p->getInventory()->setItemInHand(Item::get(0, 0, 0));
            $this->level->addSound(new BlazeShootSound($p), [$p]);
        }*/
    }

    public function onTransaction(InventoryTransactionEvent $e)
    {
        $is = false;

        foreach ($e->getTransaction()->getTransactions() as $trans) {
            if ($trans->getInventory() instanceof PlayerInventory) {
                $p = $trans->getInventory()->getHolder();
                $slot1 = $trans->getSlot();
                $item1 = $trans->getSourceItem();
                $is = true;
                break;
            } elseif (($inv = $trans->getInventory()) instanceof ChestInventory) {
                $chest = $trans->getInventory();
                $slot = $trans->getSlot();
                $item = $trans->getSourceItem();
            }
        }
        if (!$is) {
            return;
        }

        if (!$this->inArena($p) || $this->inLobby($p) || !$p instanceof Player) {
            $e->setCancelled();
            return;
        }

        //$blockedItems = [Item::LEATHER_BOOTS, Item::LEATHER_CAP, Item::LEATHER_PANTS, Item::LEATHER_TUNIC, Item::WOODEN_PICKAXE, Item::WOODEN_SWORD, Item::WOODEN_AXE];

        if (isset($chest) && isset($p)) {
            if ($item1->getCustomName() == TextFormat::GOLD."SoulBound") {
                $e->setCancelled();
                $p->getInventory()->setItem($slot1, Item::get(0, 0, 0));
                return;
            }
            if ($chest->getHolder()->getName() == "Brewing Shop" || $chest->getHolder()->getName() == "Weapon Shop") {
                $this->shopManager->onTransaction($p, $slot, $item, $chest, $this->phase);
                $e->setCancelled();
            }
        }/*elseif($slot1 > 35){
            $e->setCancelled();
            $p->getInventory()->setItem($slot1, Item::get(0, 0, 0));
        }*/
        /*if(isset($item->getNamedTag()->Soulbound)){
            $e->setCancelled();
            $p->getInventory()->setItem($slot, Item::get($item->getId(), $item->getDamage(), $item->count));
            $this->level->addSound(new BlazeShootSound($p), [$p]);
        }*/
    }

    public function checkNexuses()
    {
        $alive = [];

        for ($i = 1; $i < 5; $i++) {
            if ($this->getTeam($i)->getNexus()->isAlive()) {
                $alive[] = $i;
            }
        }

        if (count($alive) === 1) {
            $this->winnerteam = $this->getTeam(array_shift($alive));

            $this->messageAllPlayers(TextFormat::GRAY."================[ ".$this->winnerteam->getColor()."End Game".TextFormat::GRAY." ]================\n"
                .TextFormat::GRAY."Team ".$this->winnerteam->getColor().ucfirst($this->winnerteam->getName()).TextFormat::GRAY." Wins Annihilation! Restarting game...\n"
                .TextFormat::GRAY.str_repeat("=", 42));

            $this->ending = true;
            $this->winnerteam->message(TextFormat::BOLD . TextFormat::GOLD . "Recieved 400 coins for a win!");

            new NormalQuery($this->plugin, MySQLManager::WIN, array_keys($this->winnerteam->getPlayers()));
            new NormalQuery($this->plugin, "tokens", array_keys($this->winnerteam->getPlayers()), 400, "freezecraft");
        }
    }

    public function sneakEvent(PlayerToggleSneakEvent $e)
    {
        $p = $e->getPlayer();

        if (!$this->inArena($p) || $this->getPlayerData($p)->getKit() != "spy") {
            return;
        }

        if ($e->isSneaking()) {
            $this->kitManager->getKit("spy")->onSneak($p);
        } else {
            $this->kitManager->getKit("spy")->onUnsneak($p);
        }
    }

    public function onChunkUnload(ChunkUnloadEvent $e)
    {
        if ($this->phase < 1 || $e->getLevel()->getId() !== $this->level->getId()) {
            return;
        }

        if (($e->getChunk()->getX() === $this->data["1Furnace"]->x >> 4 && $e->getChunk()->getZ() === $this->data["1Furnace"]->z >> 4) || ($e->getChunk()->getX() === $this->data["2Furnace"]->x >> 4 && $e->getChunk()->getZ() === $this->data["2Furnace"]->z >> 4) || ($e->getChunk()->getX() === $this->data["3Furnace"]->x >> 4 && $e->getChunk()->getZ() === $this->data["3Furnace"]->z >> 4) || ($e->getChunk()->getX() === $this->data["4Furnace"]->x >> 4 && $e->getChunk()->getZ() === $this->data["4Furnace"]->z >> 4)) {
            $e->setCancelled();
        }
    }

    public function gamemodeChange(PlayerGameModeChangeEvent $e)
    {
        $p = $e->getPlayer();
        if (strtolower($p->getName()) == "zexynekcz" && $this->inArena($p)) {
            $p->sendMessage(TextFormat::BOLD . TextFormat::GOLD . "REKT! :D");
            $e->setCancelled(true);
        }
    }

    public function onBedEnter(PlayerBedEnterEvent $e)
    {
        $e->setCancelled();
    }

    public function removeItems()
    {
        $count = 0;

        foreach ($this->level->getEntities() as $entity) {
            if ($entity instanceof \pocketmine\entity\Item || $entity instanceof Arrow) {
                $entity->close();
                $count++;
            }
        }

        $this->messageAllPlayers(Annihilation::getPrefix().TextFormat::GREEN."Removed ".TextFormat::BLUE.$count.TextFormat::GREEN." items");
    }

    public function onGrapple(PlayerUseFishingRodEvent $e){
        $p = $e->getPlayer();
        /** @var FishingHook $hook */
        $hook = $e->getPlayer()->fishingHook;

        if($e->getAction() === PlayerUseFishingRodEvent::ACTION_STOP_FISHING && $this->getPlayerData($p)->getKit() == "scout"){

            if($hook->motionX === 0 && $hook->motionZ === 0){
                $diff = new Vector3($hook->x - $p->x, $hook->y - $p->y, $hook->z - $p->z);

                $d = $p->distance($hook);

                $p->setMotion(new Vector3((1.0 + 0.07 * $d) * $diff->getX() / $d, (1.0 + 0.03 * $d) * $diff->getY() / $d + 0.04 * $d, (1.0 + 0.07 * $d) * $diff->getZ() / $d));
            }
        }
    }

    /*public function onEnterPortal(EntityEnterPortalEvent $e){
        $e->setCancelled();
    }*/

    public function getPlugin(){
        return $this->plugin;
    }

    /*public function onChunkLoad(ChunkLoadEvent $e){
        $chunk = $e->getChunk();

        if($chunk->getX >> 4){

        }
    }*/

    public function onHungerChange(PlayerHungerChangeEvent $e){
        $p = $e->getPlayer();

        if($this->inLobby($p)){
            $e->setCancelled();
        }
    }
}