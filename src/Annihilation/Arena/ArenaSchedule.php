<?php

namespace Annihilation\Arena;

use Annihilation\Annihilation;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\tile\Sign;
use Annihilation\Arena\Arena;
use pocketmine\block\Block;

class ArenaSchedule extends Task{
    
    public $plugin;
    public $time = 0;
    public $time1 = 120;
    public $popup = 0;
    
    public $pool = [];
    public $cobble = [];

    private $remove = 0;
    
    public function __construct(Arena $plugin) {
	$this->plugin = $plugin;
    }
    
    public function onRun($currentTick){
        while($this->next($currentTick) === true){
            $this->unhashBlock(array_shift($this->pool));
        }

        if($this->popup === 0){
            $this->setJoinSigns();
        }
        $this->popup++;
        
        if($this->popup === 2){ 
            $this->popup = 0;
        }
        if($this->plugin->phase === 0 && $this->plugin->starting === false){
            $this->plugin->checkLobby();
        }
        if($this->plugin->starting === true){
            $this->starting();
        }
            
        if($this->plugin->phase >= 1){
            $this->running();
        }
    }
    
    public function onCancel(){ 
        foreach($this->pool as $string){
            $this->unhashBlock($string);
        }
    }

    public function push(Block $block){
        switch($block->getId()){
            case 14:
                $restoreTick = $this->getOwner()->getServer()->getTick() + 600; 
                array_push($this->pool, "$restoreTick:$block->x:$block->y:$block->z:{$block->getId()}:{$block->getDamage()}:{$block->getLevel()->getName()}");
                //$block->getLevel()->setBlock(new Vector3($block->x, $block->y, $block->z), Block::get(4, 0));
                break;
            case 15:
                $restoreTick = $this->getOwner()->getServer()->getTick() + 400; 
                array_push($this->pool, "$restoreTick:$block->x:$block->y:$block->z:{$block->getId()}:{$block->getDamage()}:{$block->getLevel()->getName()}");
                //$block->getLevel()->setBlock(new Vector3($block->x, $block->y, $block->z), Block::get(4, 0));
                break;
            case 16:
                $restoreTick = $this->getOwner()->getServer()->getTick() + 200; 
                array_push($this->pool, "$restoreTick:$block->x:$block->y:$block->z:{$block->getId()}:{$block->getDamage()}:{$block->getLevel()->getName()}");
                //$block->getLevel()->setBlock(new Vector3($block->x, $block->y, $block->z), Block::get(4, 0));
                break;
            case 21:
                $restoreTick = $this->getOwner()->getServer()->getTick() + 600; 
                array_push($this->pool, "$restoreTick:$block->x:$block->y:$block->z:{$block->getId()}:{$block->getDamage()}:{$block->getLevel()->getName()}");
                //$block->getLevel()->setBlock(new Vector3($block->x, $block->y, $block->z), Block::get(4, 0));
                break;
            case 56:
                $restoreTick = $this->getOwner()->getServer()->getTick() + 600; 
                array_push($this->pool, "$restoreTick:$block->x:$block->y:$block->z:{$block->getId()}:{$block->getDamage()}:{$block->getLevel()->getName()}");
                //$block->getLevel()->setBlock(new Vector3($block->x, $block->y, $block->z), Block::get(4, 0));
                break;
            case 73:
                $restoreTick = $this->getOwner()->getServer()->getTick() + 600; 
                array_push($this->pool, "$restoreTick:$block->x:$block->y:$block->z:{$block->getId()}:{$block->getDamage()}:{$block->getLevel()->getName()}");
                //$block->getLevel()->setBlock(new Vector3($block->x, $block->y, $block->z), Block::get(4, 0));
                break;
            case 74:
                $restoreTick = $this->getOwner()->getServer()->getTick() + 600; 
                array_push($this->pool, "$restoreTick:$block->x:$block->y:$block->z:{$block->getId()}:{$block->getDamage()}:{$block->getLevel()->getName()}");
                //$block->getLevel()->setBlock(new Vector3($block->x, $block->y, $block->z), Block::get(4, 0));
                break;
            case 129:
                $restoreTick = $this->getOwner()->getServer()->getTick() + 1200; 
                array_push($this->pool, "$restoreTick:$block->x:$block->y:$block->z:{$block->getId()}:{$block->getDamage()}:{$block->getLevel()->getName()}");
                //$block->getLevel()->setBlock(new Vector3($block->x, $block->y, $block->z), Block::get(4, 0));
                break;
            case 13:
                $restoreTick = $this->getOwner()->getServer()->getTick() + 200; 
                array_push($this->pool, "$restoreTick:$block->x:$block->y:$block->z:{$block->getId()}:{$block->getDamage()}:{$block->getLevel()->getName()}");
                break;
            case 17:
                $restoreTick = $this->getOwner()->getServer()->getTick() + 300; 
                array_push($this->pool, "$restoreTick:$block->x:$block->y:$block->z:{$block->getId()}:{$block->getDamage()}:{$block->getLevel()->getName()}");
                break;
            case 162:
                $restoreTick = $this->getOwner()->getServer()->getTick() + 300; 
                array_push($this->pool, "$restoreTick:$block->x:$block->y:$block->z:{$block->getId()}:{$block->getDamage()}:{$block->getLevel()->getName()}");
                break;
            case 103:
                $restoreTick = $this->getOwner()->getServer()->getTick() + 300; 
                array_push($this->pool, "$restoreTick:$block->x:$block->y:$block->z:{$block->getId()}:{$block->getDamage()}:{$block->getLevel()->getName()}");
                break;
        }
    }
    public function next($currentTick){
        if(isset($this->pool[0])){
            $nextTick = (int) explode(':', $this->pool[0])[0];
            if($nextTick <= $currentTick){
                return true;
            }
        }
        return false;
    }
    
    private function unhashBlock($string){
        list($tick, $x, $y, $z, $id, $damage, $lvName) = explode(":", $string);
        $this->plugin->level->setBlock(new Vector3(intval($x), intval($y), intval($z)), Block::get($id, $damage), true);
    }
    
    public function getOwner(){
        return $this->plugin->plugin;
    }

    public function setJoinSigns(){
        $lobby = $this->plugin->plugin->level;

        $sign = $lobby->getTile($this->plugin->maindata["sign"]);
        $signb = $lobby->getTile($this->plugin->maindata["1sign"]);
        $signr = $lobby->getTile($this->plugin->maindata["2sign"]);
        $signy = $lobby->getTile($this->plugin->maindata["3sign"]);
        $signg = $lobby->getTile($this->plugin->maindata["4sign"]);
        
        if($sign instanceof Sign){
            $map = $this->plugin->map;
        if($this->plugin->phase <= 0){
            $map = "---";
        }
            $game = TextFormat::GREEN."Lobby";
            if($this->plugin->phase >= 1){
                switch($this->plugin->phase){
                    case 1:
                        $game = TextFormat::GOLD."Phase: I";
                        break;
                    case 2:
                        $game = TextFormat::GOLD."Phase: II";
                        break;
                    case 3:
                        $game = TextFormat::RED."Phase: III";
                        break;
                    case 4:
                        $game = TextFormat::RED."Phase: IV";
                        break;
                    case 5:
                        $game = TextFormat::RED."Phase: V";
                        break;
                }
            }
            $sign->setText(TextFormat::DARK_RED."■".$this->plugin->id."■", TextFormat::BLACK.count($this->plugin->getAllPlayers())."/150", $game, TextFormat::BOLD.TextFormat::BLACK.$map);
        }
        
        if($signb instanceof Sign){
            $signb->setText("", TextFormat::DARK_BLUE."[BLUE]", TextFormat::GRAY.count($this->plugin->getTeam(1)->getPlayers()).TextFormat::GRAY." players", "");
        }
        if($signr instanceof Sign){
            $signr->setText("", TextFormat::DARK_RED."[RED]", TextFormat::GRAY.count($this->plugin->getTeam(2)->getPlayers()).TextFormat::GRAY." players", "");
        }
        if($signy instanceof Sign){
            $signy->setText("", TextFormat::YELLOW."[YELLOW]", TextFormat::GRAY.count($this->plugin->getTeam(3)->getPlayers()).TextFormat::GRAY." players", "");
        }
        if($signg instanceof Sign){
            $signg->setText("", TextFormat::DARK_GREEN."[GREEN]", TextFormat::GRAY.count($this->plugin->getTeam(4)->getPlayers()).TextFormat::GRAY." players", "");
        }
    }
    
    public function starting()
    {
        $this->time1--;

        if($this->time1 > 0) {


            foreach ($this->plugin->getAllPlayers() as $p) {
                $p->setExpLevel($this->time1);
            }
        }

        if ($this->time1 === 5) {
            $this->plugin->selectMap();
            return;
        }

        if ($this->time1 <= 0) {
            $this->plugin->startGame();
        }
    }

    private $checkAlive = 0;

    private $items = 0;
    
    public function running(){
        //$this->plugin->checkAlive();
        $this->checkAlive++;
        $this->items++;

        if($this->checkAlive >= 5){
            $this->plugin->checkAlive();
        }

        if($this->items === 195){
            $this->plugin->messageAllPlayers(Annihilation::getPrefix().TextFormat::GRAY."Removing all items in ".TextFormat::YELLOW."5".TextFormat::GRAY." seconds");
        } elseif($this->items >= 200){
            $this->items = 0;
            $this->plugin->removeItems();
        }

        $this->time++;
        if($this->time == 600){
            $this->plugin->changePhase(2);
        }
        if($this->time == 1200){
           $this->plugin->changePhase(3);
        }
        if($this->time == 1800){
            $this->plugin->changePhase(4);
        }
        if($this->time == 2400){
            $this->plugin->changePhase(5);
        }
        if($this->time == 5400){
            $this->plugin->ending = true;
        }
    }
    
    
}