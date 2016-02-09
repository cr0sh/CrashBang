<?

namespace crashbang;

use pocketmine\scheduler\PluginTask;
use pocketmine\plugin\Plugin

class UpgradeTask extends PluginTask {

    private $player;

    public function __construct(Plugin $owner, Player $p) {
        $this->player = $p;
        parent::__construct($owner);
    }

    public function onRun($tick) {
        if($this->getOwner()->ps[$p->getName()] < 5) {
            $this->getOwner()->ps[$p->getName()]++;
            $p->sendMessage("[CrashBang] 추가 데미지가 +1 증가했습니다. 현재 데미지: " . ++$this->getOwner()->ps[$p->getName()]);
        }
    }

}
