<?php

namespace Enes5519\EggWars\Islemler;

use Enes5519\EggWars\EggWars;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\tile\Sign;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\math\AxisAlignedBB;

class Oyun extends PluginTask{
    
    private $p;
    public function __construct($p){
        $this->p = EggWars::getInstance();
        parent::__construct($p);
    }
    
    public function onRun($tick){
        $main = $this->p;
        foreach($main->arenalar() as $arena){
            if($main->arenaHazirmi($arena)){
                $ac = new Config($main->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
                $durum = $ac->get("Durum");
                if($durum == "Lobi"){
                    $lobis = (int) $ac->get("BaslamaSuresi");
                    if($lobis > 0 || $lobis <= 0){
                        if(count($main->arenaOyunculari($arena)) >= $ac->get("Takim")){
                            $lobis--;
                            $ac->set("BaslamaSuresi", $lobis);
                            $ac->save();
                            switch ($lobis){
                                case 60:
                                case 45:
                                case 30:
                                case 15:
                                    $main->arenaMesaj($arena, $main->b."§eOyunun başlamasına §6$lobis §esaniye kaldı.");
                                break;
                                case 5:
                                case 4:
                                case 3:
                                case 2:
                                case 1:
                                    foreach($main->arenaOyunculari($arena) as $olar){
                                        $o = $main->getServer()->getPlayer($olar);
                                        if($o instanceof Player){
                                            $o->sendPopup("§8§l»§r §e$lobis §8§l«§r");
                                        }
                                    }
                                break;
                                default:
                                    if($lobis <= 0) {
                                        foreach ($main->arenaOyunculari($arena) as $olar) {
                                            $o = $main->getServer()->getPlayer($olar);
                                            if ($o instanceof Player) {
                                                if (!$main->oyuncuHangiTakimda($o)) {
                                                    $takim = $main->musaitRastTakim($arena);
                                                    $o->setNameTag($takim . $o->getName());
                                                }
                                                $takim = $main->oyuncuHangiTakimda($o);
                                                $o->teleport(new Position($ac->getNested($takim . ".X"), $ac->getNested($takim . ".Y"), $ac->getNested($takim . ".Z"), $main->getServer()->getLevelByName($ac->get("Dunya"))));
                                                $o->getInventory()->clearAll();
                                                $o->sendMessage($main->b . " §aOyun Başladı!");
                                            }
                                        }
                                        $ac->set("Durum", "Oyunda");
                                        $ac->save();
                                    }
                                break;
                            }
                        }
                    }
                }elseif($durum == "Oyunda"){
                    $level = Server::getInstance()->getLevelByName($ac->get("Dunya"));
                    $tile = $level->getTiles();
                    foreach ($tile as $tabela){
                        if($tabela instanceof Sign){
                            $y = $tabela->getText();
                            if($y[0] == "§fDemir" || $y[0] == "§6Altın" || $y[0] == "§bElmas"){
                                $evet = false;
                                foreach($level->getNearbyEntities(new AxisAlignedBB($tabela->x - 10, $tabela->y - 10, $tabela->z - 10, $tabela->x + 10, $tabela->y + 10, $tabela->z + 10)) as $ent){
                                    if($ent instanceof Player){
                                        $evet = true;
                                    }
                                }
                                if($evet == true){
                                    $im = explode(" ", $y[2]);
                                    $saniye = str_ireplace("§b", "", $im[0]);
                                    $tur = $y[0];
                                    if($saniye != "KIRIK"){
                                        $item = $this->turDonusItem($tur);
                                        if(time() % $saniye == 0){
                                            $level->dropItem(new Vector3($tabela->x, $tabela->y, $tabela->z), $item);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    foreach($main->arenaOyunculari($arena) as $olar){
                        $o = Server::getInstance()->getPlayer($olar);
                        $i = null;
                        foreach($main->durum($arena) as $durum){
                            $i.=$durum;
                        }
                        $o->sendPopup($i);
                    }
                    if($main->tekTakımKaldımı($arena)){
                        $ac->set("Durum", "Bitti");
                        $ac->save();
                        $main->arenaMesaj($arena, $main->b."§aOyunu Kazandınız!");
                        $takim = "";
                        foreach ($main->arenaOyunculari($arena) as $olar) {
                            $o = Server::getInstance()->getPlayer($olar);
                            if(!($o instanceof Player)){
                                return true;
                            }
                            $takim = $main->oyuncuHangiTakimda($o);
                        }
                        Server::getInstance()->broadcastMessage($main->b."§b$arena §9arenasını §b$takim §9kazandı§1!");
                    }
                }elseif($durum == "Bitti"){
                    $bitis = (int) $ac->get("BitisSuresi");
                    if($bitis > 0 || $bitis <= 0){
                        $bitis--;
                        $ac->set("BitisSuresi", $bitis);
                        $ac->save();
                        foreach($main->arenaOyunculari($arena) as $oyuncular){
                            $o = Server::getInstance()->getPlayer($oyuncular);
                            if($bitis <= 1){
                                $main->arenadanOyuncuKaldir($arena, $o->getName());
                            }
                            if($bitis <= 0){
                                $main->arenaYenile($arena);
                                $o->sendPopup("§8» §eArena Yenileniyor §8«");
                                return true;
                            }
                            $o->sendPopup("§cArena yenilenmesine §e$bitis §csaniye kaldı.");
                        }
                    }
                }else{
                    $ac->set("Durum", "Bitti");
                    $ac->save();
                }
            }
        }
        return true;
    }
    
    public function turDonusItem($tur){
        $item = null;
        switch($tur){
            case "§6Altın":
                $item = Item::get(266);
            break;
            case "§bElmas":
                $item = Item::get(264);
            break;
            default:
                $item = Item::get(265);
            break;
        }
        return $item;
    }
}