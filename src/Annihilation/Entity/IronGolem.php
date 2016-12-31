<?php

namespace Annihilation\Entity;

use pocketmine\entity\Creature;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

class IronGolem extends Creature{

    const NETWORK_ID = 20;

    public $height = 2.688;
    public $width = 1.625;
    public $length = 0.906;

    public function getName(){
        return "IronGolem";
    }

    public function spawnTo(Player $player){
        $pk = new AddEntityPacket();
        $pk->eid = $this->getId();
        $pk->type = IronGolem::NETWORK_ID;
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->speedX = $this->motionX;
        $pk->speedY = $this->motionY;
        $pk->speedZ = $this->motionZ;
        $pk->yaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->metadata = $this->dataProperties;
        $player->dataPacket($pk);

        parent::spawnTo($player);
    }


    public function onUpdate($currentTick){
        if($this->closed !== \false){
            return \false;
        }

        if($this->isAlive()){
            if(!$this->isOnGround()){
                $this->motionY -= $this->gravity;
            }

            $this->move($this->motionX, $this->motionY, $this->motionZ);
        }
    }

}