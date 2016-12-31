<?php

namespace Annihilation;

use Annihilation\MySQLPingTask;
use Annihilation\Annihilation;
use pocketmine\utils\TextFormat;

class MySQLManager{

    const KILL = "kills";
    const DEATH = "deaths";
    const WIN = "wins";
    const LOSE = "losses";
    const NEXUS_DESTROY = "nexuses";
    const NEXUS_DAMAGE = "nexusdmg";
    
    public $plugin;
    /** @var  \mysqli $database */
    public static $database;
    
    public function __construct(Annihilation $plugin){
        $this->plugin = $plugin;
    }
    
    public function createMySQLConnection(){
        $database = new \mysqli("93.91.250.135", "180532_mysql_db", "kaktus01", "180532_mysql_db");
        $this->setDatabase($database);
        if($database->connect_error)
        {
            $this->plugin->getLogger()->critical("Nepodarilo se navazat pripojeni s databazi". $database->connect_error);
        }
        else
        {
            $this->plugin->getLogger()->info("§2Navazano pripojeni k §l§6Annihilation §r§3MySQL §2Serveru!");
            $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask(new MySQLPingTask($this), 20);
        }
    }
    
    public static function registerPlayer($player){
        $name = trim(strtolower($player));
        $data =
        [
            "name" => $name,
            "kills" => 0,
            "deaths" => 0,
            "wins" => 0,
            "losses" => 0,
            "nexuses" => 0,
            "nexusdmg" => 0,
            "kits" => "civilian"
        ];

        self::getDatabase()->query
        (
            "INSERT INTO annihilation (
            name, kills, deaths, wins, losses, nexuses, nexusdmg, kits)
            VALUES
            ('".self::getDatabase()->escape_string($name)."', '".$data["kills"]."', '".$data["deaths"]."', '".$data["wins"]."', '".$data["losses"]."', '".$data["nexuses"]."', '".$data["nexusdmg"]."', '".$data["kits"]."')"
        );

        //$this->plugin->getLogger()->Info($this->plugin->getPrefix().TextFormat::GREEN."Zaregistrovan novy hrac ". $player);
        return $data;
    }
    
    public static function getPlayer($player){
        $result = self::getDatabase()->query
        (
            "SELECT * FROM annihilation WHERE name = '" . self::getDatabase()->escape_string(trim(strtolower($player)))."'"
        );
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["name"]) and $data["name"] === trim(strtolower($player))){
                unset($data["name"]);
                return $data;
            }
        }
        return null;
    }
    
    public function setDatabase(\mysqli $database){
        self::$database = $database;
    }
    
    public static function getDatabase(){
        return self::$database;
    }
    
    public static function isPlayerRegistered($player){
        return self::getPlayer($player) !== null;
    }
    
    public static function addKill($player, $kills = 1){
        self::getDatabase()->query
        (
            "UPDATE annihilation SET kills = kills+'".$kills."' WHERE name = '".self::getDatabase()->escape_string(trim(strtolower($player)))."'"
        );
    }
    
    public static function addDeath($player, $deaths = 1){
        self::getDatabase()->query
        (
            "UPDATE annihilation SET deaths = deaths+'".$deaths."' WHERE name = '".self::getDatabase()->escape_string(trim(strtolower($player)))."'"
        );
    }
    
    public static function addWin($player, $kills = 1){
        self::getDatabase()->query
        (
            "UPDATE annihilation SET wins = wins+'".$kills."' WHERE name = '".self::getDatabase()->escape_string(trim(strtolower($player)))."'"
        );
    }
    
    public static function addLoss($player, $kills = 1){
        self::getDatabase()->query
        (
            "UPDATE annihilation SET losses = losses+'".$kills."' WHERE name = '".self::getDatabase()->escape_string(trim(strtolower($player)))."'"
        );
    }
    
    public static function addNexus($player, $kills = 1){
        self::getDatabase()->query
        (
            "UPDATE annihilation SET nexuses = nexuses+'".$kills."' WHERE name = '".self::getDatabase()->escape_string(trim(strtolower($player)))."'"
        );
    }
    
    public static function addNexusDmg($player, $kills = 1){
        self::getDatabase()->query
        (
            "UPDATE annihilation SET nexusdmg = nexusdmg+'".$kills."' WHERE name = '".self::getDatabase()->escape_string(trim(strtolower($player)))."'"
        );
    }
    
    public static function getKills($p){
        $data = self::getPlayer($p);
        return $data['kills'];
    }
    
    public static function getDeaths($p){
        $data = self::getPlayer($p);
        return $data['deaths'];
    }
    
    public static function getWins($p){
        $data = self::getPlayer($p);
        return $data['wins'];
    }
    
    public static function getLosses($p){
        $data = self::getPlayer($p);
        return $data['losses'];
    }
    
    public static function getNexuses($p){
        $data = self::getPlayer($p);
        return $data['nexuses'];
    }

    public static function getNexusDmg($p){
        $data = self::getPlayer($p);
        return $data['nexusdmg'];
    }
    
    public static function addKit($p, $kit){
        self::getDatabase()->query
        (
            "UPDATE annihilation SET kits = '".self::getKits($p)."|".$kit."' WHERE name = '".self::getDatabase()->escape_string(trim(strtolower($p)))."'"
        );
    }
    
    public static function getKits($p){
        $data = self::getPlayer($p);
        return $data['kits'];
    }
}