<?php
namespace ImagicalGamer\SurvivalGames;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

use pocketmine\Server;
use pocketmine\Player;

use pocketmine\level\Level;

use pocketmine\utils\TextFormat as C;

use ImagicalGamer\SurvivalGames\Commands\SurvivalGamesCommand;
use ImagicalGamer\SurvivalGames\Tasks\RefreshSigns;
use ImagicalGamer\SurvivalGames\Tasks\GameSender;

use pocketmine\level\Position;
use pocketmine\utils\Config;

/* Copyright (C) ImagicalGamer - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Jake C <imagicalgamer@outlook.com>, July 2016
 */

class Main extends PluginBase implements Listener{

  public $mode = 0;
  public $prefix = C::GREEN . "[SG] " . C::RESET . C::GRAY;
  public $format = C::GREEN . "[SG] " . C::RESET . C::GRAY;
  public $current_lev = "";
  public $joinText = C::AQUA . "JOIN";
  public $runningText = C::RED . "[FULL]";
  public $arenas = array();

  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this ,$this);
    if(is_dir($this->getDataFolder())){
      $cfg = new Config($this->getDataFolder() . "/arenas.yml", Config::YAML);
      $cfg->save();
      $this->getLogger()->info(C::GREEN . "Data Found!");
      $this->refreshArenas();
      $this->loadArenas();
    }
    else{
      $this->getLogger()->info(C::YELLOW . "Initializing Startup...");
      $this->newStart();
      $cfg = new Config($this->getDataFolder() . "/arenas.yml", Config::YAML);
      $cfg->save();
    }
    $this->getServer()->getCommandMap()->register("sg", new SurvivalGamesCommand("sg", $this));
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    $this->getServer()->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), 20);
    $this->getServer()->getScheduler()->scheduleRepeatingTask(new GameSender($this), 25);
    $this->getLogger()->info(C::GREEN . "Enabled!");
  }

  public function onDisable(){
    $this->refreshArenas();
    $this->saveData();
  }

  public function newStart(){
    @mkdir($this->getDataFolder());
    $this->saveResource("/config.yml");
    $this->saveResource("/arenas.yml");
  }

  public function newArena(Player $player, String $lv){
    if($this->isArena($lv)){
      $player->sendMessage(C::RED . "Theres already an arena in level " . $lv . "!");
      return false;
    }
    if(!file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1])){
      $player->sendMessage(C::RED . "Level not found");
      return false;
    }
    $this->getServer()->loadLevel($lv);
    $lev = $this->getServer()->getLevelByName($lv);
    $player->teleport($this->getServer()->getLevelByName($lv)->getSafeSpawn(),0,0);
    $this->current_lev = $lv;
    $player->setGamemode(1);
    $player->sendMessage($this->prefix . "Your about to register an arena! Tap a block to set a spawn!");
    $this->mode = 1;
  }

  public function minPlayer(){
    return 2;
  }

  public function isArena(String $arena){
    $cfg = new Config($this->getDataFolder() . "/arenas.yml", Config::YAML);
    if(in_array($arena, $this->arenas)){
      return true;
    }
    else{
      return false;
    }
  }

  public function addArena(String $arena){
    array_push($this->arenas, $arena);
    $this->refreshArenas();
  }

  public function refresh(String $arena){
    $cfg = new Config($this->getDataFolder() . "/arenas.yml", Config::YAML);
    $cfg->set($arena . "StartTime", 30);
    $cfg->set($arena . "PlayTime", 780);
    $cfg->save();
  }

  public function saveData(){
    $cfg = new Config($this->getDataFolder() . "/arenas.yml", Config::YAML);
    $cfg->set("Arenas",$this->arenas);
    $cfg->save();
  }

  public function loadArenas(){
    $cfg = new Config($this->getDataFolder() . "/arenas.yml", Config::YAML);
    foreach($cfg->get("Arenas") as $lev)
    {
      array_push($this->arenas, $lev);
      $this->getServer()->loadLevel($lev);
    }

  }

  public function refreshArenas(){
    $cfg = new Config($this->getDataFolder() . "/arenas.yml", Config::YAML);
    foreach($this->arenas as $arena)
    {
      $cfg->set($arena . "PlayTime", 780);
      $cfg->set($arena . "StartTime", 60);
    }
    $cfg->save();
  }

  public function refreshArena(String $arena){
    $cfg = new Config($this->getDataFolder() . "/arenas.yml", Config::YAML);
    $cfg->set($arena . "PlayTime", 780);
    $cfg->set($arena . "StartTime", 60);
    $cfg->save();
  }

  public function getRank(Player $p){
    return;
    }

  public function getDefaultLevel(){
    $cfg = new Config($this->getDataFolder() . "/arenas.yml", Config::YAML);
    $lev = $cfg->get("DefaultWorld");
    if($this->getServer()->getLevelByName($lev) instanceof Level){
      return $this->getServer()->getLevelByName($lev);
    }
    return $this->getServer()->getDefaultLevel();
  }

  public function worldChat(){
    $cfg = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
    if($cfg->get("WorldChat") == true){
      return true;
    }
    return;
  }

  public function refillChests(Level $level){ 
    $cfg = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
    $tiles = $level->getTiles();
    foreach($tiles as $t) {
      if($t instanceof Chest) 
      {
        $chest = $t;
        $chest->getInventory()->clearAll();
        if($chest->getInventory() instanceof ChestInventory)
        {
          for($i=0;$i<=26;$i++)
          {
            $rand = rand(1,3);
            if($rand==1)
            {
              $k = array_rand($config->get("chestitems"));
              $v = $cfg->get("chestitems")[$k];
              $chest->getInventory()->setItem($i, Item::get($v[0],$v[1],$v[2]));
            }
          }
        }
      }
    }
  }

  public function getVersion(){
    $cfg = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
    return $cfg->get("Version");
  }

  public function hasUpdate(){
    return;
  }
}
