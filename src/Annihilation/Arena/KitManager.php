<?php

namespace Annihilation\Arena;

use Annihilation\Annihilation;
use Annihilation\Arena\Arena;
use Annihilation\Arena\Kits\Kit;
use Annihilation\Arena\Object\Team;
use Annihilation\MySQL\KitSelectQuery;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\LeatherCap;
use pocketmine\nbt\NBT;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerItemHeldEvent;
use Annihilation\Arena\Kits\Archer;
use Annihilation\Arena\Kits\Berserker;
use Annihilation\Arena\Kits\Civilian;
use Annihilation\Arena\Kits\Miner;
use Annihilation\Arena\Kits\Operative;
use Annihilation\Arena\Kits\Scout;
use Annihilation\Arena\Kits\Spy;
use Annihilation\Arena\Kits\Thor;
use Annihilation\Arena\Kits\Warrior;
use Annihilation\Arena\Kits\Acrobat;
use Annihilation\Arena\Kits\Lumberjack;
use Annihilation\Arena\Kits\Handyman;

class KitManager{

    public $plugin;

    /** @var  Kit[] $kits*/
    public $kits;
    
    public function __construct(Arena $plugin){
        $this->plugin = $plugin;
        $this->kits = ['archer' => new Archer($this->plugin), 'berserker' => new Berserker($this->plugin), 'civilian' => new Civilian($this->plugin), 'miner' => new Miner($this->plugin), 'operative' => new Operative($this->plugin), /*'spy' => new Spy($this->plugin),*/ 'scout' => new Scout($this->plugin), /*'thor' => new Thor($this->plugin),*/ 'warrior' => new Warrior($this->plugin), 'acrobat' => new Acrobat($this->plugin), 'lumberjack' => new Lumberjack($this->plugin), 'handyman' => new Handyman($this->plugin)];
    }
    
    public function onKitChange(Player $p, $kit, $direct = false){
        new KitSelectQuery($this->plugin->plugin, $p->getName(), $kit);

        /*if(!$this->hasKit($p, $kit) and !in_array($kit, ["handyman", "spy", "operative"]) && !$p->isOp() && !in_array(\strtolower($this->plugin->mtcore->mysqlmgr->getRank($p->getName())), ["vip", "vip+", "extra", "co-owner", "owner", "youtuber"])){
            $p->sendMessage($this->plugin->plugin->getPrefix().TextFormat::RED."You have not bought this kit");
            return;
        }
        if ($kit == "spy" and !in_array(\strtolower($this->plugin->mtcore->mysqlmgr->getRank($p->getName())), ["vip+", "extra", "co-owner", "owner"])){
            $p->sendMessage($this->plugin->plugin->getPrefix().TextFormat::RED."This kit is only avaible to VIP+s!");
            $p->sendMessage($this->plugin->plugin->getPrefix().TextFormat::ITALIC.TextFormat::GRAY."You can buy VIP+ at ".TextFormat::RESET.TextFormat::GREEN."bit.ly/mtBUY");
            return;
        }
        if (($kit == "handyman" or $kit == "operative") and !in_array(\strtolower($this->plugin->mtcore->mysqlmgr->getRank($p->getName())), ["vip", "vip+", "extra", "co-owner", "owner"])){
            $p->sendMessage($this->plugin->plugin->getPrefix().TextFormat::RED."This kit is only avaible to VIPs!");
            $p->sendMessage($this->plugin->plugin->getPrefix().TextFormat::ITALIC.TextFormat::GRAY."You can buy VIP at ".TextFormat::RESET.TextFormat::GREEN."bit.ly/mtBUY");
            return;
        } 
        if($direct){
            $this->plugin->getPlayerData($p)->setKit($kit);
        }*/
    }

    /**
     * @param Player $p
     */

    public function giveKit(Player $p){
        $data = $this->plugin->getPlayerData($p);

        $data->setKit($data->getNewKit());

        $inv = $p->getInventory();
        $inv->clearAll();

        $this->kits[$data->getKit()]->give($p);

        $color = $data->getTeam()->getDec();

        $helmet = Item::get(Item::LEATHER_CAP, 0, 1);
        $helmet->setCustomColor($color);
        $helmet->setCustomName(TextFormat::GOLD."SoulBound");

        $chestplate = Item::get(Item::LEATHER_TUNIC, 0, 1);
        $chestplate->setCustomColor($color);
        $chestplate->setCustomName(TextFormat::GOLD."SoulBound");

        $leggins = Item::get(Item::LEATHER_PANTS, 0, 1);
        $leggins->setCustomColor($color);
        $leggins->setCustomName(TextFormat::GOLD."SoulBound");

        $boots = Item::get(Item::LEATHER_BOOTS, 0, 1);
        $boots->setCustomColor($color);
        $boots->setCustomName(TextFormat::GOLD."SoulBound");

        $inv->setHelmet($helmet);
        $inv->setChestplate($chestplate);
        $inv->setLeggings($leggins);
        $inv->setBoots($boots);

        //print_r($inv->getArmorContents());
        //echo "\n slots: ".$inv->getSize();

        $inv->sendContents($p);
        $inv->sendArmorContents($p);
    }

    /**
     * @param Player|ChestInventory $p
     */

    public function addKitWindow($p){
        if($p instanceof Player){
            $inv = $p->getInventory();
        }else{
            $inv = $p;
        }

        $inv->setItem(0, Item::get(58, 0, 1, "Civilian")); //civilian
        $inv->setItem(1, Item::get(274, 0, 1), "Miner"); //miner
        $inv->setItem(2, Item::get(275, 0, 1), "Lumberjack"); //lumberjack
        $inv->setItem(3, Item::get(272, 0, 1), "Warrior"); //warrior
        $inv->setItem(4, Item::get(346, 0, 1), "Scout"); //scout
        $inv->setItem(5, Item::get(303, 0, 1), "Berserker"); //berserker
        $inv->setItem(6, Item::get(261, 0, 1), "Archer"); //archer
        //$inv->setItem(7, Item::get(373, 8, 1), "Spy"); //spy
        $inv->setItem(8, Item::get(88, 0, 1), "Operative"); //operative
        //$inv->setItem(9, Item::get(286, 0, 1), "Thor"); //thor
        $inv->setItem(10, Item::get(288, 0, 1), "Acrobat"); //acrobat
        $inv->setItem(11, Item::get(145, 0, 1), "Handyman"); //handyman

        if($inv instanceof PlayerInventory){
            $inv->setHotbarSlotIndex(0, 35);
            $inv->setHotbarSlotIndex(1, 35);
            $inv->setHotbarSlotIndex(2, 35);
            $inv->setHotbarSlotIndex(3, 35);
            $inv->setHotbarSlotIndex(4, 35);
            $inv->setHotbarSlotIndex(5, 35);
            $inv->setHotbarSlotIndex(6, 35);
            $inv->setHotbarSlotIndex(7, 35);
            $inv->setHotbarSlotIndex(8, 35);
            $inv->sendContents($p);
        }
    }
    
    /*public function onItemTrans(InventoryTransactionEvent $e){
        if($e->getTransaction() instanceof Transaction){
        $inv = $e->getTransaction()->getInventory();
        if($inv instanceof PlayerInventory){
            $p = $inv->getHolder();
            if($this->plugin->getPlayerTeam($p) !== 0){
                return;
            }
            $e->setCancelled();
        }
        }
    }*/
    
    public function itemHeld(Player $p, $slot, $direct = false){
        switch($slot){
                case 0:
                    $this->onKitChange($p, "civilian", $direct);
                    break;
                case 1:
                    $this->onKitChange($p, "miner", $direct);
                    break;
                case 2:
                    $this->onKitChange($p, "lumberjack", $direct);
                    break;
                case 3:
                    $this->onKitChange($p, "warrior", $direct);
                    break;
                case 4:
                    $this->onKitChange($p, "scout", $direct);
                    break;
                case 5:
                    $this->onKitChange($p, "berserker", $direct);
                    break;
                case 6:
                    $this->onKitChange($p, "archer", $direct);
                    break;
                case 7:
                    $this->onKitChange($p, "spy", $direct);
                    break;
                case 8:
                    $this->onKitChange($p, "operative", $direct);
                    break;
                case 9:
                    $this->onKitChange($p, "thor", $direct);
                    break;
                case 10:
                    $this->onKitChange($p, "acrobat", $direct);
                    break;
                case 11:
                    $this->onKitChange($p, "handyman", $direct);
                    break;
                default:
                    return;
            }
    }
    
    public function hasKit(Player $p, $kit){
        //return true; //for testing
        return strpos($this->plugin->mysql->getKits($p->getName()), strtolower($kit)) === false ? false : true;
/*
        $kits = explode("|", $this->plugin->mysql->getKits($p->getName()));
        foreach($kits as $result){
            if(strtolower($result) == strtolower($kit)){
                return true;
            }
        }
        return false;*/
    }
    
    public function getKit($name){
        return $this->kits[strtolower($name)];
    }

    public function buyKit(Player $p, $kit){
        if($this->hasKit($p, $kit)){
            $p->sendMessage(Annihilation::getPrefix().TextFormat::YELLOW."You have already purchased this kit.");
            return;
        }
        $tokens = $this->plugin->mtcore->mysqlmgr->getTokens($p->getName());
        $price = [
          "civilian" => 0,    
          "miner" => 5000,      
          "lumberjack" => 5000,  
          "warrior" => 5000,     
          "berserker" => 15000,  
          "acrobat" => 15000,   
          "archer" => 15000,     
          "operative" => "vip",  
          "handyman" => "vip",  
          "spy" => "vip+"           
        ];
        if (!isset($price[$kit])){
          $p->sendMessage(Annihilation::getPrefix().TextFormat::RED."Invalid kit.");
          return;
        }
         if (is_numeric($price[$kit])){
           if (in_array(\strtolower($this->plugin->mtcore->mysqlmgr->getRank(\strtolower($p->getName()))), ["vip", "vip+", "extra", "co-owner", "owner", "youtuber"])){
               $p->sendMessage(Annihilation::getPrefix().TextFormat::GREEN."VIPs have all kits unlocked!");
               return; 
           }
           if ($tokens < $price[$kit]){
              $p->sendMessage(Annihilation::getPrefix().TextFormat::RED."You don't have enough tokens");
              $p->sendMessage("Your tokens: ".TextFormat::AQUA.$tokens);
              $p->sendMessage(TextFormat::ITALIC.TextFormat::GRAY."Buy some credits at ".TextFormat::RESET.TextFormat::GREEN."bit.ly/mtBUY".TextFormat::ITALIC.TextFormat::GRAY." in section Ranks & Tokens");
           }
           else {
               $this->plugin->mtcore->mysqlmgr->takeTokens($p->getName(), $price[$kit]);
               $this->plugin->mysql->addKit($p->getName(), $kit);
               $p->sendMessage(Annihilation::getPrefix().TextFormat::GREEN."Purchased kit ".$kit." for ".TextFormat::AQUA.$price[$kit].TextFormat::GREEN." tokens");
           }
         }
         else {
            if (!in_array(\strtolower($this->plugin->mtcore->mysqlmgr->getRank($p->getName())), [$price[$kit], "vip+", "extra", "co-owner", "owner", "youtuber"])){
                $p->sendMessage("This kit is only avaible for ".strtoupper($price[$kit])."s and higher ranks!");
                $p->sendMessage(TextFormat::GRAY.TextFormat::ITALIC."You can buy VIP at ".TextFormat::GREEN."bit.ly/mtBUY");
            }
            else {
               $p->sendMessage(Annihilation::getPrefix().TextFormat::GREEN."VIPs have all kits unlocked!");    
            }
         }
    }
}