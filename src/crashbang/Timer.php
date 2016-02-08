<?php

namespace crashbang;

use pocketmine\scheduler\PluginTask;
use pocketmine\Player;

class Timer extends PluginTask {
    public function onRun($tick) {
        if $this->getOwner()->status > 0 {
            $this->getOwner()->timer--;
        }
    }

    public function sendTip(Player $p) {

    }
}
