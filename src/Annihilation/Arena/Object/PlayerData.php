<?php

namespace Annihilation\Arena\Object;


use Annihilation\Arena\Tile\EnderBrewingInventory;
use Annihilation\Arena\Tile\EnderFurnace;
use Annihilation\Arena\Tile\EnderFurnaceInventory;
use Annihilation\Arena\VirtualInventory;
use pocketmine\inventory\BrewingInventory;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\FurnaceInventory;
use pocketmine\Player;
use pocketmine\tile\Furnace;

class PlayerData{

    private $name;

    private $kit;
    /** @var  Team $team */
    private $team = null;
    private $lobby = true;
    private $newKit;
    /** @var  PlayerData $killer */
    private $killer;
    private $time;
    /** @var  ChestInventory $chest */
    private $chest = null;
    /** @var  EnderFurnace $furnace */
    private $furnace = null;
    /** @var  EnderBrewingInventory $brewing */
    private $brewing = null;
    private $wasInGame = false;

    private $rank;

    /** @var  VirtualInventory $inventory*/
    private $inventory;

    private $isChangingKit = false;

    public function __construct($name, $kit = "civilian", $rank = "hrac"){
        $this->name = $name;
        $this->newKit = $kit;
        $this->rank = $rank;
    }

    public function getTeam(){
        return $this->team;
    }

    public function setTeam(Team $team){
        $this->team = $team;
    }

    public function getChest(){
        return $this->chest;
    }

    public function setChest(ChestInventory $inv){
        $this->chest = $inv;
    }

    public function getFurnace(){
        return $this->furnace;
    }

    public function setFurnace(EnderFurnaceInventory $inv){
        $this->furnace = $inv;
    }

    public function getBrewing(){
        return $this->brewing;
    }

    public function setBrewing(EnderBrewingInventory $inv){
        $this->brewing = $inv;
    }

    public function wasInGame(){
        return $this->wasInGame && $this->team instanceof Team;
    }

    public function setInGame(){
        $this->wasInGame = true;
    }

    /**
     * @return bool|PlayerData
     */
    public function wasKilled(){
        if($this->time - time() > 0 && $this->killer !== null){
            return $this->killer;
        }
        return false;
    }

    public function getKit(){
        return $this->kit;
    }

    public function setKit($kit){
        $this->kit = $kit;
    }

    public function getNewKit(){
        return $this->newKit;
    }

    public function setNewKit($kit){
        $this->newKit = $kit;
    }

    public function isInLobby(){
        return $this->lobby;
    }

    public function getSavedInventory(){
        return $this->inventory;
    }

    public function saveInventory(Player $p){
        $this->inventory = new VirtualInventory($p);
    }

    public function removeInventory(){
        $this->inventory = null;
    }

    /**
     * @param boolean $value
     */
    public function setLobby($value){
        $this->lobby = $value;
    }

    public function setKiller(PlayerData $p){
        $this->killer = $p;
        $this->time = time() + 10;
    }

    public function getName(){
        return $this->name;
    }

    public function isChangingKit(){
        return $this->isChangingKit;
    }

    public function setKitChanging($value = true){
        $this->isChangingKit = $value;
    }
}