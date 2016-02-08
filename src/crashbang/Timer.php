<?php

namespace crashbang;

use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;

class Timer extends PluginTask {
    public function onRun($tick) {
        if($this->getOwner()->status > 0) {
            if(--$this->getOwner()->timer == 0) $this->getOwner()->stop();
        }
        foreach(Server::getInstance()->getOnlinePlayers() as $p) sendTip($p);
    }

    public function sendTip(Player $p) {
        $msg = "서버 상태: ";
        switch($this->getOwner()->status) {
            case 0:
                $msg .= "게임 중이 아님";
                break;
            case 1:
                $msg .= "능력 추첨/선택 중\n";
                break;
            case 2:
                $msg .= TextFormat::AQUA."게임 중\n".TextFormat::RESET;
        }
        if($this->getOwner()->status === 1) {
            $msg .= TextFormat::GREEN."남은 시간:" . TextFormat::GOLD . ($this->getOwner()->timer - CrashBang::GAME_TIME) . TextFormat::GREEN . "초";
        } elseif($this->getOwner()->status === 2) {
            if($this->getOwner()->timer < 60) {
                $msg .= TextFormat::GREEN."남은 시간:" . TextFormat::RED . $this->getOwner()->timer . "/" . CrashBang::GAME_TIME . TextFormat::GREEN . "초";
            } else {
                $msg .= TextFormat::GREEN."남은 시간:" . TextFormat::GOLD . $this->getOwner()->timer . "/" . CrashBang::GAME_TIME . TextFormat::GREEN . "초";
            }
        }
        $p->sendTip($msg);
    }
}
