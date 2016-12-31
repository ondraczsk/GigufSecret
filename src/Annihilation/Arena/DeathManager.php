<?php

namespace Annihilation\Arena;

use Annihilation\Arena\Object\PlayerData;
use Annihilation\MySQL\NormalQuery;
use Annihilation\MySQLManager;
use pocketmine\entity\Living;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\entity\Projectile;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class DeathManager{
    
    private $plugin;
    
    public function __construct(Arena $plugin){
        $this->plugin = $plugin;
    }

    public function onDeath(PlayerDeathEvent $ev, PlayerData $data){
        $p = $ev->getEntity();

        $cause = $p->getLastDamageCause();
        $event = null;

        $pName = $data->getTeam()->getColor().$p->getName();
        $escape = false;

        $killerData = $data->wasKilled();

        $killerName = "*****";

        if($killerData){
            $killerName = $killerData->getTeam()->getColor() . $killerData->getName();
            $escape = true;

            new NormalQuery($this->plugin->plugin, MySQLManager::KILL, [strtolower($killerData->getName())]);
            new NormalQuery($this->plugin->plugin, "tokens", [strtolower($killerData->getName())], 10, "freezecraft");
        }

        if($cause instanceof EntityDamageEvent){
            $event = $cause;
            $cause = $cause->getCause();
        }

        $message = $pName .TextFormat::GRAY." died";

        switch($cause){
            case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                if($event instanceof EntityDamageByEntityEvent){
                    $e = $event->getDamager();
                    if($e instanceof Player){
                        if(!$escape) {
                            $killerData = $this->plugin->getPlayerData($e);
                        }

                        $message = $pName . TextFormat::GRAY . " was killed by " . $killerData->getTeam()->getColor().$e->getName();
                        break;
                    }
                }
                $message = $pName .TextFormat::GRAY . " was killed";
                break;
            case EntityDamageEvent::CAUSE_PROJECTILE:
                if($event instanceof EntityDamageByEntityEvent){
                    $e = $event->getDamager();
                    if($e instanceof Player){
                        if(!$escape) {
                            $killerData = $this->plugin->getPlayerData($e);
                        }

                        $message = $pName .TextFormat::GRAY . " was shot by " . $killerData->getTeam()->getColor().$e->getName();
                        break;
                    }
                }
                $message = $pName .TextFormat::GRAY . " was shot by arrow";
                break;
            case EntityDamageEvent::CAUSE_SUICIDE:
                $message = $pName .TextFormat::GRAY . " died";
                break;
            case EntityDamageEvent::CAUSE_VOID:
                $message = $pName .TextFormat::GRAY . " fell out of the world";
                break;
            case EntityDamageEvent::CAUSE_FALL:
                if($event instanceof EntityDamageEvent){
                    if($event->getFinalDamage() > 2){
                        $message = $escape ? $pName . TextFormat::GRAY . " was doomed to fall by ".$killerName : $pName .TextFormat::GRAY . " fell from a high place";
                        break;
                    }
                }
                $message = $escape ? $pName . TextFormat::GRAY . " was doomed to fall by ".$killerName : $pName .TextFormat::GRAY . " hit the ground too hard";
                break;

            case EntityDamageEvent::CAUSE_SUFFOCATION:
                $message = $pName .TextFormat::GRAY . " suffocated in a wall";
                break;

            case EntityDamageEvent::CAUSE_LAVA:
                $message = $escape ? $pName . TextFormat::GRAY . " tried to swim in lava while trying to escape " . $killerName : $pName .TextFormat::GRAY . " tried to swim in lava";
                break;

            case EntityDamageEvent::CAUSE_FIRE:
                $message = $escape ? $pName . TextFormat::GRAY . " walked into a fire whilst fighting " : $pName .TextFormat::GRAY . " went up in flames";
                break;

            case EntityDamageEvent::CAUSE_FIRE_TICK:
                $message = $escape ? $pName . TextFormat::GRAY . " was burnt to a crisp whilst fighting " . $killerName : $pName .TextFormat::GRAY . " burned to death";
                break;

            case EntityDamageEvent::CAUSE_DROWNING:
                $message = $escape ? $pName . TextFormat::GRAY . " drowned whilst trying to escape " . $killerName : $pName .TextFormat::GRAY . " drowned";
                break;

            case EntityDamageEvent::CAUSE_CONTACT:
                $message = $escape ? $pName . TextFormat::GRAY . " walked into a cactus while trying to escape " . $killerName : $pName .TextFormat::GRAY . " was pricked to death";
                break;

            case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
            case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
                $message = $escape ? $pName . TextFormat::GRAY . " was blown up by " . $killerName : $pName .TextFormat::GRAY . " blew up";
                break;

            case EntityDamageEvent::CAUSE_MAGIC:
                $message = $pName .TextFormat::GRAY . " was slain by magic";
                break;
        }

        $this->plugin->messageAllPlayers($message);
    }

    /*public function onDeath(PlayerDeathEvent $e){
        $p = $e->getEntity();
        $lastDmg = $p->getLastDamageCause();
        $pColor = $this->plugin->getPlayerTeam($p)->getColor();
        $dColor = "";
        $escape = false;
        if($lastDmg instanceof EntityDamageEvent){
            if($lastDmg instanceof EntityDamageByEntityEvent){
                $killer = $lastDmg->getDamager();
                if($killer instanceof Player){
                    $dColor = $this->plugin->getPlayerTeam($killer)->getColor();
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." was slain by ".$dColor."{$killer->getName()}");
                    $this->plugin->mysql->addKill($killer->getName());
                    $this->plugin->mtcore->mysqlmgr->addTokens($killer->getName(), 10);
                }
                return;
            } elseif($lastDmg instanceof EntityDamageByChildEntityEvent){
                $arrow = $lastDmg->getChild();
                $killer = $lastDmg->getDamager();
                if($arrow instanceof Projectile){
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." was shot by ".$this->plugin->getPlayerTeam($killer)->getColor().$killer->getName());
                    $this->plugin->mysql->addKill($killer->getName());
                    $this->plugin->mtcore->mysqlmgr->addTokens($killer->getName(), 10);
                }
                return;
            }

            $killer = null;
            if(($killer = $this->plugin->getPlayerData($p)->wasKilled())){
                $escape = true;
                $dColor = $killer->getTeam()->getColor();
                $killer = $killer->getName();
            }
            /*if($lastDmg instanceof EntityDamageByBlockEvent){
                if($escape === true){
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." walked into a cactus while trying to escape ".$this->plugi->getTeamColor($this->plugin->getPlayerTeam($killer)).$killer->getName());
                    $this->plugin->mysql->addKill($killer->getName());
                    return;
                }
                $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." was pricked to death");
                return;
            }*/
            /*
            if($escape === true){
                $this->plugin->mysql->addKill($killer);
                $this->plugin->mtcore->mysqlmgr->addTokens($killer->getName(), 10);
                $pl = $this->plugin->plugin->getServer()->getPlayer($killer);
                if($pl instanceof Player){
                    $pl->addExp(15);
                    if($this->plugin->getPlayerData($pl)->getKit() == "berserker" && $pl->getMaxHealth() < 30){
                        $pl->setMaxHealth($pl->getMaxHealth()+1);
                    }
                }
            }

            switch($lastDmg->getCause()){
                case 0:
                    if($escape === true){
                        $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." walked into a cactus while trying to escape ".$dColor.$killer);
                        return;
                    }
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." was pricked to death");
                    break;
                case 3:
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." suffocated in a wall");
                    break;
                case 4:
                    if($escape === true){
                        $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." was doomed to fall by ".$dColor.$killer);
                        break;
                    }
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." fell from high place");
                    break;
                case 5:
                    if($escape === true){
                        $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." walked into a fire whilst fighting ".$dColor.$killer);
                        break;
                    }
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." went up in flames");
                    break;
                case 6:
                    if($escape === true){
                        $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." was burnt to a crisp whilst fighting ".$dColor.$killer);
                        break;
                    }
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." burned to death");
                    break;
                case 7:
                    if($escape === true){
                        $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." tried to swim in lava while trying to escape ".$dColor.$killer);
                        break;
                    }
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." tried to swim in lava");
                    break;
                case 8:
                    if($escape === true){
                        $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." drowned whilst trying to escape ".$dColor.$killer);
                        break;
                    }
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." drowned");
                    break;
                case 9:
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." blew up");
                    break;
                case 10:
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." blew up");
                    break;
                case 11:
                    if($escape === true){
                        $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." was doomed to fall by ".$dColor.$killer);
                        break;
                    }
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." fell out of the world");
                    break;
                case 12:
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." died");
                    break;
                case 13:
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." died");
                    break;
                case 14:
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." died");
                    break;
            }
        }
    }*/
}