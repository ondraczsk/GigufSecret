<?php

namespace Annihilation\MySQL;

use Annihilation\Annihilation;
use MTCore\MySQL\AsyncQuery;

class JoinQuery extends AsyncQuery{

    public function __construct(Annihilation $plugin, $player){
        $this->player = $player;
        $this->table = "annihilation";

        parent::__construct($plugin);
    }

    public function onQuery(array $data){

        if($data == null){
            $this->registerPlayer($this->player);
        }
    }

    public function registerPlayer($player){
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
                "kits" => "civilian|handyman"
            ];

        $this->getMysqli()->query
        (
            "INSERT INTO annihilation (
            name, kills, deaths, wins, losses, nexuses, nexusdmg, kits)
            VALUES
            ('".$this->getMysqli()->escape_string($name)."', '".$data["kills"]."', '".$data["deaths"]."', '".$data["wins"]."', '".$data["losses"]."', '".$data["nexuses"]."', '".$data["nexusdmg"]."', '".$data["kits"]."')"
        );

        //$this->plugin->getLogger()->Info($this->plugin->getPrefix().TextFormat::GREEN."Zaregistrovan novy hrac ". $player);
        return $data;
    }
}