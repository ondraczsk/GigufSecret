<?php

namespace Annihilation\Arena\Object;

use Annihilation\Arena\Arena;
use pocketmine\block\Block;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\level\particle\LargeExplodeParticle;
use pocketmine\level\particle\LavaDripParticle;
use pocketmine\level\Position;
use pocketmine\level\sound\AnvilFallSound;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Nexus{

    private $health;

    /** @var  Position $position */
    private $position;

    /** @var  Team $team */
    private $team;

    private $plugin;

    public function __construct(Team $team, Position $pos, $health = 75){
        $this->team = $team;
        $this->position = $pos;
        $this->health = $health;
    }

    public function getHealth(){
        return $this->health;
    }

    public function setHealth($amount){
        $this->health = $amount;
    }

    public function getTeam(){
        return $this->team;
    }

    public function getPosition(){
        return $this->position;
    }

    public function damage($damage = 1){
        if(!$this->isAlive()){
            return;
        }
        $this->health -= $damage;

        if($this->getHealth() <= 0){
            $this->getPosition()->getLevel()->setBlock($this->getPosition(), Block::get(7), true);
            $this->setHealth(0);
        }
    }

    public function isAlive(){
        return $this->getHealth() > 0 ? true : false;
    }
}