<?php

namespace crashbang;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;
use pocketmine\entity\Effect;

class CrashBang extends PluginBase implements Listener {

    const GAME_TIME = 500;

    private $available, $picking, $ps, $motd;
    public $skill, $status, $timer, $cooldown;

    public function onEnable() {
        Skills::init();
        $this->status = 0; // 0: Stopped, 1: choosing, 2: started
        $this->motd = $this->getServer()->getMotd();
        $this->getServer()->getNetwork()->setName(TextFormat::GREEN."[WAITING] ".TextFormat::RESET.$this->motd);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new Timer($this), 1);
        $this->getLogger()->info("Crashbang loaded!");
    }

    public function onTouch(\pocketmine\event\player\PlayerInteractEvent $ev) {
        if($this->status !== 2 or $ev->getItem()->getID() !== Item::BLAZE_ROD) return;
        $ev->setCancelled();
        if($this->cooldown[$ev->getPlayer()->getName()] > 0) {
            $ev->getPlayer()->sendMessage("[CrashBang] 아직 스킬을 사용할 수 없습니다.");
            return;
        }
        switch($this->skill[$ev->getPlayer()->getName()]) {
            case Skills::ZOMBIE:
                $ev->getPlayer()->addEffect(Effect::getEffect(Effect::SLOWNESS)->setAmplifier(2)->setDuration(15*20));
                $ev->getPlayer()->addEffect(Effect::getEffect(Effect::REGENERATION)->setAmplifier(4)->setDuration(10*20));
                $this->startCooldown($ev->getPlayer());
                break;
            case Skills::EARTHQUAKE:
                $dirs = array();
                foreach($this->getServer()->getOnlinePlayers() as $p) {
                    if($p->getName() == $ev->getPlayer()->getName()) continue;
                    $dirs[$p->getName()] = $p->distance($ev->getPlayer());
                }
                asort($dirs);
                $i = 0;
                foreach($dirs as $p => $d) {
                    if(++$i >= 2) break;
                    $p = $this->getServer()->getPlayerExact($p);
                    $ev = new EntityDamageByEntityEvent($ev->getPlayer(), $ev->getPlayer(), EntityDamageEvent::CAUSE_CONTACT, 6);
                    $p->attack($ev->getFinalDamage(), $ev);
                }
                $this->startCooldown($ev->getPlayer());
                break;
        }
    }

    public function onHit(\pocketmine\event\entity\EntityDamageByEntityEvent $ev) {
        if($this->status !== 2) return;
        if(!($ev->getEntity() instanceof Player && $ev->getDamager() instanceof Player)) return;
        $ev->setCancelled();
        if($this->cooldown[$ev->getPlayer()->getName()] > 0) {
            $ev->getPlayer()->sendMessage("[CrashBang] 아직 스킬을 사용할 수 없습니다.");
            return;
        }
    }

    public function onPreLogin(\pocketmine\event\player\PlayerPreLoginEvent $ev) {
        if($this->status > 0) {
            $ev->setCancelled();
            $ev->getPlayer()->close("게임이 진행 중입니다.\n".TextFormat::AQUA.TextFormat::BOLD.$this->timer.TextFormat::RESET."초 뒤에 다시 접속해주세요.");
        }
    }

    public function onQuit(\pocketmine\event\player\PlayerQuitEvent $ev) {
        $ev->getPlayer()->removeAllEffects();
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        if(!($sender instanceof Player)) return true;
        if(count($args) === 0) {
            return false;
        }
        switch($args[0]) {
            case "help":
                if($this->status === 0) {
                    $sender->sendMessage("[CrashBang] 현재 사용할 수 없는 명령어입니다.");
                    break;
                }
                $c = Skills::$cooldown[$sender->getName()];
                $sender->sendMessage(Skills::$desc[$this->skill[$sender->getName()]] . ($c <= 0 ? " (쿨타임 없음)" : " (쿨타임 $c초)"));
                break;
            case "start":
                if($this->status !== 0) {
                    $sender->sendMessage("[CrashBang] 현재 사용할 수 없는 명령어입니다.");
                    break;
                }
                $this->roulette();
                break;
            case "stop":
                if($this->status == 2) {
                    $sender->sendMessage("[CrashBang] 현재 사용할 수 없는 명령어입니다.");
                    break;
                }
                $this->stop();
                break;
            case "yes":
                if($this->status !== 1) {
                    $sender->sendMessage("[CrashBang] 현재 사용할 수 없는 명령어입니다.");
                    break;
                }
                unset($this->picking[$sender->getName()]);
                $sender->sendMessage("[CrashBang] 능력이 확정되었습니다.");
                if(count($this->picking) === 0) {
                    $this->start();
                }
                break;
            case "no":
                if($this->status !== 1) {
                    $sender->sendMessage("[CrashBang] 현재 사용할 수 없는 명령어입니다.");
                    break;
                }
                $sender->sendMessage("[CrashBang] 능력이 재추첨됩니다.");
                $this->available[] = $this->skill[$sender->getName()];
                $this->pick($sender);
                unset($this->picking[$sender->getName()]);
                break;
            case "set":
                if(!$sender->hasPermission("crashbang.set")) {
                    $sender->sendMessage(TextFormat::RED."이 명령어를 사용할 권한이 없습니다.");
                    break;
                }
                if(count($args) < 2 or !is_numeric($args[1])) return false;
                $this->skill[$sender->getName()] = (int) $args[1];
                $this->start();
                break;
            default:
                return false;
        }
        return true;
    }

    public function roulette() {
        $this->status = 1;
        $this->getServer()->getNetwork()->setName(TextFormat::GOLD.TextFormat::ITALIC."[진행 중] ".TextFormat::RESET.$this->motd);
        $this->skill = array();
        $this->ps = array(); // Player keystore
        $this->available = array();
        $this->picking = array();
        $this->cooldown = array();
        $this->timer = 60 + self::GAME_TIME;
        $this->getServer()->broadcastMessage("[CrashBang] 능력 추첨을 시작합니다");
        $this->getServer()->broadcastMessage("[CrashBang] /cb <yes|no>로 능력을 정하세요.");
        $this->getServer()->broadcastMessage("[CrashBang] 1분 내에 확정하지 않으면 킥 처리하고 게임을 시작합니다.");
        foreach(Skills::$cooldown as $k => $c) {
            $this->available[$k] = $k;
        }
        foreach($this->getServer()->getOnlinePlayers() as $p) {
            $this->picking[$p->getName()] = true;
            $this->pick($p);
        }
    }

    public function pick(Player $p) {
        $i = mt_rand(0, count($this->available[$k]) - 1);
        $this->skill[$p->getName()] = $this->available[array_keys($this->available)[$i]];
        unset($this->available[array_keys($this->available)[$i]]);
        $p->sendMessage("[CrashBang] 능력이 주어졌습니다. /cb help로 확인하세요.");
    }

    public function start() {
        $this->status = 2;
        $this->timer = self::GAME_TIME;
        foreach($this->getServer()->getOnlinePlayers() as $p) {
            $this->cooldown[$p->getName()] = 0;
        }
        $this->getServer()->broadcastMessage("[CrashBang] 게임이 시작되었습니다.");
    }

    public function stop() {
        $this->status = 0;
        $this->getServer()->getNetwork()->setName(TextFormat::GREEN."[입장 가능] ".TextFormat::RESET.$this->motd);
    }

    public function startCooldown(Player $p) {
        $this->cooldown[$p->getName()] = Skills::$cooldown[$this->skill[$p->getName()]];
        $p->sendMessage("[CrashBang] 스킬을 사용했습니다.");
    }

}
