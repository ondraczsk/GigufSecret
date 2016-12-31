<?php

namespace Annihilation\Arena;

use pocketmine\level\sound\FizzSound;
use pocketmine\scheduler\Task;
use Annihilation\Arena\Arena;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

class PopupTask extends Task{
    
    public $plugin;
    public $task;
    public $ending = 0;
    
    public function __construct(Arena $plugin){
        $this->plugin = $plugin;
    }
    
    public function onRun($currentTick){
        if($this->plugin->phase >= 1 && $this->plugin->ending !== true){
            $this->sendTeamsStats();
            return;
        }

        if($this->plugin->ending === true){
            if($this->ending === 30){
                $this->plugin->ending = false;
                $this->plugin->stopGame();
                $this->ending = false;

                return;
            }
            $this->ending++;
            $this->sendEnding();
            return;
        }

        if($this->plugin->phase === 0){
            $this->sendVotes();
            return;
        }
    }
    
    public function sendVotes(){
        $vm = $this->plugin->votingManager;
        //$this->plugin->plugin->getServer()->getLogger()->info("{$vm->stats[1]} {$vm->stats[2]} {$vm->stats[3]}");
        $votes = [$vm->currentTable[0], $vm->currentTable[1], $vm->currentTable[2]];

        $tip = "                                                   §8Voting §f| §6/vote <name>"
            . "\n                                                 §b[1] §8{$votes[0]} §c» §a{$vm->stats[1]} Votes"
            . "\n                                                 §b[2] §8{$votes[1]} §c» §a{$vm->stats[2]} Votes"
            . "\n                                                 §b[3] §8{$votes[2]} §c» §a{$vm->stats[3]} Votes";

        foreach($this->plugin->getAllPlayers() as $p){
            $p->sendTip($tip);
        }                        //    |
    }
    
    public function sendEnding(){
        $team = $this->plugin->winnerteam;

        $name = $team->getColor().$team->getName();

        /*$tip = TextFormat::GRAY."                    ================[ ".TextFormat::AQUA."Progress".TextFormat::GRAY." ]================\n"
            . "                               ".TextFormat::BOLD.$name.TextFormat::RESET.TextFormat::GRAY." team won the game!\n"
            .TextFormat::GRAY         . "======================================================";*/

        $sound = new FizzSound(new Vector3());

        foreach($this->plugin->getAllPlayers() as $p){
            //$p->sendTip($tip);

            $sound->setComponents($p->x, $p->y, $p->z);
            $this->plugin->level->addSound($sound);
        }
    }
    
    public function sendTeamsStats(){
        $nex = [$this->plugin->getTeam(1)->getNexus()->getHealth(), $this->plugin->getTeam(2)->getNexus()->getHealth(), $this->plugin->getTeam(3)->getNexus()->getHealth(), $this->plugin->getTeam(4)->getNexus()->getHealth()];
        $map = $this->plugin->map;
        $phase = $this->getDisplayPhase($this->plugin->phase).TextFormat::GRAY." | ".TextFormat::WHITE.$this->plugin->task->time / 3600 % 60 .":".$this->plugin->task->time / 60 % 60 .":".$this->plugin->task->time % 60;
        $tip = "                                                    §8Map: §6$map\n"
                      . "                                                §eYellow Nexus  §c{$nex[2]}\n"
                      . "                                                §cRed Nexus     §c{$nex[1]}\n"
                      . "                                                §9Blue Nexus    §c{$nex[0]}\n"
                      . "                                                §aGreen Nexus  §c{$nex[3]}\n"
                      . "\n\n\n                      $phase";

        foreach($this->plugin->getAllPlayers() as $p){
            $p->sendTip($tip);
            //$p->sendPopup($popup);
        }                                                        //   |
    }
    
    public function getPhaseTime(){
        $time = $this->plugin->task->time;
        switch($this->plugin->phase){
            case 1:
                return $time / 30;
            case 2:
                return ($time - 600) / 30;
            case 3:
                return ($time - 1200) / 30;
            case 4:
                return ($time - 1800) / 30;
            case 5:
                return 20;
        }
        return 0;
    }
    
    public function getDisplayPhase($phase){
        switch($phase){
            case 1:
                return TextFormat::GOLD."Phase: ".TextFormat::DARK_GREEN."I";
            case 2:
                return TextFormat::GOLD."Phase: ".TextFormat::DARK_GREEN."II";
            case 3:
                return TextFormat::RED."Phase: ".TextFormat::DARK_PURPLE."III";
            case 4:
                return TextFormat::RED."Phase: ".TextFormat::DARK_PURPLE."IV";
            case 5:
                return TextFormat::RED."Phase: ".TextFormat::DARK_PURPLE."V";
        }

        return "";
    }
}