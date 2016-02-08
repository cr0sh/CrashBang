<?php

namespace crashbang;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;

class CrashBang extends PluginBase implements Listener {
    public function onEnable() {
        $this->getLogger()->info("Crashbang loaded!");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onTouch(\pocketmine\event\player\PlayerInteractEvent $ev) {
        $this->getLogger()->info($ev->getItem()->toString());
    }

    public function onHit(\pocketmine\event\entity\EntityDamageByEntityEvent $ev) {

    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {

    }

    public function roulette() {

    }

    public function start() {

    }

    public function stop() {
        
    }

}
