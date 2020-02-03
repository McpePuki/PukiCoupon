<?php
/**
 * @name PukiCoupon
 * @main coupon\Coupon
 * @author puki
 * @version 1.0.0
 * @api 3.9.5
 * @description puki
 */

namespace coupon;

use pocketmine\event\Listener;

use pocketmine\item\Item;

use pocketmine\plugin\PluginBase;

use pocketmine\command\PluginCommand;

use pocketmine\utils\Config;

use pocketmine\command\Command;

use pocketmine\command\CommandSender;

use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;

use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\event\server\DataPacketReceiveEvent;

class Coupon extends PluginBase implements Listener
{

  public $sub = [];

  public function onEnable ()
  {
    date_default_timezone_set('Asia/Seoul');
    $this->data = new Config ($this->getDataFolder() . 'CouponList.yml', Config::YAML,[
      '쿠폰' => []
    ]);
    $this->db = $this->data->getAll();

    $this->dataitem = new Config ($this->getDataFolder() . 'item.yml', Config::YAML);
    $this->item = $this->dataitem->getAll();

    $this->playerdata = new Config ($this->getDataFolder() . 'PlayerData.yml', Config::YAML);
    $this->player = $this->playerdata->getAll();

    $this->getServer()->getPluginManager()->registerEvents($this, $this);

    $cmd = new PluginCommand('쿠폰',$this);
    $cmd->setDescription('쿠폰');

    $this->getServer()->getCommandMap()->register('쿠폰', $cmd);

  }
  public function coupon()
  {

        $text = [
            "type" => "custom_form",
            "title" => "쿠폰",
      "content" => [
        [
            "type" => "input",
          "text" => "쿠폰명을 써주세요!"
        ]
        ]
            ];
    return json_encode ( $text );
  }
  public function CouponUi (DataPacketReceiveEvent $event)
  {

		$p = $event->getPacket ();
		$player = $event->getPlayer ();
		if ($p instanceof ModalFormResponsePacket and $p->formId == 8038 )
    {

			$button = json_decode ( $p->formData, true );
      if ( $button == null) {
        $player->sendMessage('쿠폰을 입력해주세요');
      } else{
        if (isset($this->db['쿠폰'][$button[0]])) {
          if(isset($this->player['데이터'][$button[0]][$player->getName()] )) {
            $player->sendMessage('이미 §e'.$button[0].'§f의 보상을 받으셨습니다.');
            return true;
          }
          if (time() > $this->db['쿠폰'][$button[0]]['기간']) {
  $player->sendMessage('유효기간이지난 쿠폰');
  } else {
      $player->sendMessage ('성공적으로 §e'.$button[0].'§f의 쿠폰보상을 획득');
      for($a =1; $a <= count($this->db['쿠폰'][$button[0]]['아이템목록']); $a++) {
       $player->getInventory()->addItem (Item::jsonDeserialize($this->db['쿠폰'] [$button[0]] ['아이템목록'] [$a] ['아이템']) ) ;
     }
       $this->player['데이터'][$button[0]][$player->getName()] = [];

       $this->save();

  }




}  else {
$player->sendMessage('그런 쿠폰은 없습니다.');

}

}
}
}
public function save()
{
  $this->data->setAll($this->db);
  $this->data->save();

  $this->dataitem->setAll($this->item);
  $this->dataitem->save();

  $this->playerdata->setAll($this->player);
  $this->playerdata->save();
}


  public function onCommand(CommandSender $sender, Command $command, $label, array $args) : bool
  {
    $cmd = $command->getName();

    if($cmd === '쿠폰')
    {

      if(!isset($args[0]))
      {
        if(!$sender->isOp()){
          $sender->sendMessage('/쿠폰 입력');
          return true;
        } else {
          $sender->sendMessage('/쿠폰 입력');
          $sender->sendMessage('/쿠폰 생성 [쿠폰명] [1당 1시간]');
          $sender->sendMessage('/쿠폰 삭제 쿠폰명');
          $sender->sendMessage('/쿠폰 아이템 │ 자신이들고있는 아이템으로 쿠폰 보상 Nbt 여러개 가능');
          $sender->sendMessage('/쿠폰 활동시작 │ 쿠폰 생성활동을 시작합니다.');
          $sender->sendMessage('/쿠폰 활동중지 │ 쿠폰활동을 초기화합니다.');
        }

        return true;

      }
      switch ($args[0])
      {

        case '입력':
        if(!isset($args[0]))
        {
            $sender->sendMessage('/쿠폰 입력');
          return true;

        }
        $p = new ModalFormRequestPacket ();
        $p->formId =   8038;
        $p->formData = $this->coupon();
        $sender->dataPacket ($p);
          break;
          case '활동시작':
          if(!isset($args[0]))
          {
              $sender->sendMessage('/쿠폰 활동시작 │ 쿠폰 생성 활동을 시작합니다.');
              $sender->sendMessage('잘못할경우 /쿠폰 활동중단 or 잘되셨으면 /쿠폰 생성 [쿠폰명] [1당 1시간]');
            return true;

          }
            $this->item[$sender->getName()]['등록'] = [];
            $sender->sendMessage('쿠폰 생성 활동을 시작합니다! /쿠폰 아이템 ');
            break;
          case '아이템':
          if(!isset($args[0]))
          {
              $sender->sendMessage('/쿠폰 아이템 [번호]│ 자신이들고있는 아이템으로 쿠폰 보상 Nbt 여러개 가능');
              $sender->sendMessage('잘못할경우 /쿠폰 활동중단 or 잘되셨으면 /쿠폰 생성 [쿠폰명] [1당 1시간]');
            return true;

          }
          if(!isset($this->item[$sender->getName()]['등록']))
          {
              $sender->sendMessage('/쿠폰 활동시작 부터 해주세요!');
            return true;

          }
            $hi = $sender->getInventory()->getItemInHand();
            $nbt =$hi->jsonSerialize();
            $itemcount = count($this->item[$sender->getName()]['등록']) + 1;
            $this->item[$sender->getName()]['등록'][$itemcount] = [
              '아이템' => $nbt
            ];
            $sender->sendMessage('§e'.Item::jsonDeserialize($nbt)->getName().' §f아이템을 등록했습니다. 현재 번호 §e: '.$itemcount);
            break;
            case '활동중단':
            if(!isset($args[0]))
            {
                $sender->sendMessage('/쿠폰 중단 │ 지금까지한 활동을 중단합니다.');
              return true;

            }
            unset($this->item[$sender->getName()]['등록']);

              break;

          case '생성':
          if(!$sender->isOp()){
            $sender->sendMessage('권환이 없습니다.');
            return true;
          }

          if(!isset($args[1]))
          {
              $sender->sendMessage('/쿠폰 생성 [쿠폰명] [1당 1시간]');
            return true;

          }
          for ($i=1; $i<= count($this->item[$sender->getName()]['등록']); $i++){
              $this->db['쿠폰'][$args[1]] = [
              '기간' => time() + 60 * 60 * (int) $args[2],
              '아이템목록' => $this->item[$sender->getName()]['등록']
            ];
          }
            $hi = $sender->getInventory()->getItemInHand();
            $nbt =$hi->jsonSerialize();
            unset($this->item[$sender->getName()]['등록']);
          $sender->sendMessage('성공적으로 쿠폰이름 : §e'.$args[1].' §f쿠폰을 생성했습니다. 남은 시간 : §e'. $args[2].'§f 시간');
          $this->save();
            break;

            case '삭제':
            if(!$sender->isOp()){
              $sender->sendMessage('권환이 없습니다.');
              return true;
            }

            if(!isset($args[1]))
            {
                $sender->sendMessage('/쿠폰 삭제 쿠폰명');
              return true;

            }
            if(!isset($this->db['쿠폰'][$args[1]])) {
              $sender->sendMessage($args[1].' 그런 쿠폰은 생성된적이 없습니다.');
              return true;
              }
            unset($this->db['쿠폰'][$args[1]]);
            $sender->sendMessage('성공적으로 삭제');
            $this->save();
              break;
              return true;


      }
      return true;

    }

}
}
