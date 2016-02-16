<?php
namespace maru;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\event\TranslationContainer;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
class CommandSupport extends PluginBase implements Listener{
	public $supportlist, $adminlist;
	public function onEnable() {
		$this->loadList();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function onDisable() {
		$this->save(true);
	}
	public function loadList() {
		@mkdir($this->getDataFolder());
		$this->supportlist = (new Config($this->getDataFolder()."supportlist.json", Config::JSON))->getAll();
		$this->adminlist = (new Config($this->getDataFolder()."adminlist.json", Config::JSON, [ ]))->getAll();
	}
	public function save($async) {
		$supportlist = new Config($this->getDataFolder()."supportlist.json", Config::JSON);
		$supportlist->setAll($this->supportlist);
		$supportlist->save($async);
		
		$adminlist = new Config($this->getDataFolder(). "adminlist.json", Config::JSON);
		$adminlist->setAll($this->adminlist);
		$adminlist->save($async);
	}
	public function onCommand(CommandSender $sender, Command $command, $label, Array $args) {
		if (strtolower($command->getName()) == "후원") {
			if (!isset($args[0])) {
				return false;
			}
			$pin = $this->checkPinNumber(implode(' ', $args));
			if (is_bool($pin)) {
				$sender->sendMessage(TextFormat::RED."잘못된 핀번호 입니다.");
				$sender->sendMessage(TextFormat::BLUE."핀번호 예시 :");
				$sender->sendMessage(TextFormat::BLUE."xxxx-xxxx-xxxx-xxxxxx");
				$sender->sendMessage(TextFormat::BLUE."xxxx xxxx xxxx xxxxxx");
				$sender->sendMessage(TextFormat::BLUE."xxxxxxxxxxxxxxxxxx");
				return true;
			}
			$pinnum = implode(' ', $pin);
			if (!isset($this->supportlist[$sender->getName()])) {
				$this->supportlist[$sender->getName()] = [];
			}
			$this->supportlist[$sender->getName()][count($this->supportlist[$sender->getName()])] = $pinnum;
			$sender->sendMessage(TextFormat::AQUA."후원이 정상적으로 완료되었습니다. 어드민이 확인시 후원 보상을 바로 지급해줍니다.");
			return true;
		}
		if (strtolower($command->getName() == "후원목록")) {
			if (!isset($args[0])) {
				return false;
			}
			switch ($args[0]) {
				case '권한추가' :
					if (!$sender->hasPermission("commandsupport.cmd.list.addperm")) {
						$sender->sendMessage(new TranslationContainer(TextFormat::RED."%commands.generic.permission"));
						return true;
					}
					if ($sender instanceof Player) {
						$sender->sendMessage(TextFormat::RED."이 명령어는 콘솔에서만 사용 가능합니다.");
						return true;
					}
					if (!isset($args[1])) {
						$sender->sendMessage("사용법: /후원목록 권한추가 <플레이어>");
						return true;
					}
					$this->adminlist[strtolower($args[1])] = true;
					$sender->sendMessage($args[1]."에게 후원목록 권한을 줬습니다.");
					break;
				case '권한제거' :
					if (!$sender->hasPermission("commandsupport.cmd.list.rmperm")) {
						$sender->sendMessage(new TranslationContainer(TextFormat::RED."%commands.generic.permission"));
						return true;
					}
					if ($sender instanceof Player) {
						$sender->sendMessage(TextFormat::RED."이 명령어는 콘솔에서만 사용 가능합니다.");
						return true;
					}
					if (!isset($args[1])) {
						$sender->sendMessage("사용법: /후원목록 권한제거 <플레이어>");
						return true;
					}
					if (!isset($this->adminlist[strtolower($args[1])])) {
						$sender->sendMessage(TextFormat::RED."해당 플레이어는 권한이 없습니다.");
						return true;
					}
					unset($this->adminlist[strtolower($args[1])]);
					$sender->sendMessage(TextFormat::RED."해당 플레이어의 권한을 제거했습니다.");
					break;
				case '확인' :
					if (!$sender->hasPermission(("commandsupport.cmd.list.check") || !isset($this->adminlist[strtolower($sender->getName())])) && $sender instanceof Player){
						$sender->sendMessage(new TranslationContainer(TextFormat::RED."%commands.generic.permission"));
						return true;
					}
					if (!isset($args[1])) {
						$sender->sendMessage("사용법: /후원목록 확인 <플레이어>");
						return true;
					}
					if (!isset($this->supportlist[$args[1]])) {
						$sender->sendMessage($args[1]."은 후원을 하지 않았습니다.");
						return true;
					}
					unset($this->supportlist[$args[1]]);
					$sender->sendMessage($args[1]."의 후원을 확인해줬습니다.");
					break;
				default :
					if (!is_numeric($args[0])) {
						$sender->sendMessage(TextFormat::RED."인덱스는 숫자만 입력 가능합니다.");
						return true;
					}
					if (!isset($this->adminlist[strtolower($sender->getName())]) && $sender instanceof Player) {
						$sender->sendMessage(new TranslationContainer(TextFormat::RED."%commands.generic.permission"));
						return true;
					}
					foreach ($this->makeArray($this->supportlist, $args[0]) as $str) {
						$array = explode(':', $str);
						$sender->sendMessage($array[0]. ': '.$array[1]);
					}
			}
		}
		return true;
	}
	private function makeArray($supportlist, $index) {
		$array = [];
		$result = [];
		foreach ($supportlist as $name=>$args1) {
			foreach ($args1 as $pinnum) {
				array_push($array, "{$name}:{$pinnum}");
			}
		}
		for ($j = 0, $i = $index * 5 - 5; $i < $index * 5; $i++) {
			if (isset($array[$i])) {
				$result[$j++] = $array[$i];
			}
		}
		return $result;
	}
	private function checkPinNumber($pinNumber) {
		if (strlen($pinNumber) <= 3) {
			return false;
		}
		if ($pinNumber{4} == '-') {
			$pin = explode('-', $pinNumber);
		} else if ($pinNumber{4} == ' ') {
			$pin = explode(' ', $pinNumber);
		} else {
			if (strlen($pinNumber) !== 16 && strlen($pinNumber) !== 18) {
				return false;
			}
			$pin = str_split($pinNumber, 4);
			if (count($pin) < 4) {
				return false;
			}
			if (isset($pin[5])) {
				$pin[4] .= $pin[5];
				unset($pin[5]);
			}
		}
		for ($i = 0; $i < 3; $i++) {
			if (strlen($pin[$i]) > 4) {
				return false;
			}
		}
		if (strlen($pin[3]) != 4 && strlen($pin[3]) != 6) {
			return false;
		}
		return $pin;
	}
	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		if (isset($this->adminlist[strtolower($player->getName())]) && $this->supportlist !== [ ]) {
			$player->sendMessage(TextFormat::RED."후원목록이 존재합니다. /후원목록 <인덱스> 로 확인해보세요.");
		}
	}
}
?>