<?php

namespace Annihilation\MySQL;

use Annihilation\Annihilation;
use Annihilation\MySQLManager;
use MTCore\MySQL\AsyncQuery;

class NormalQuery extends AsyncQuery{

    private $players;
    private $key;
    private $value;

    public function __construct(Annihilation $plugin, $key, array $players, $value = 1, $table = "annihilation"){
        $this->key = $key;
        $this->value = $value;
        $this->players = $players;
        $this->table = $table;

        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        foreach($this->players as $p){
            $this->getMysqli()->query
            (
                "UPDATE ".trim($this->table)." SET ".$this->key." = ".$this->key."+'".$this->value."' WHERE name = '".$this->getMysqli()->escape_string(trim(strtolower($p)))."'"
            );
        }
    }
}