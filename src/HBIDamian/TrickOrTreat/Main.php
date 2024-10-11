<?php

namespace HBIDamian\TrickOrTreat;

use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase {

    private Config $config;
    private array $cooldowns = [];
    private int $cooldown;

    public function onEnable(): void {
        if (!$this->softDependenciesMet()) {
            $this->getLogger()->warning("  - For the full experience, please install the LMAO plugin from Poggit.");
        }
        $this->saveDefaultConfig();
        $this->config = $this->getConfig();
        $this->cooldown = $this->config->get("cooldown") ?? 60;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() !== "trickortreat") {
            return false;
        }

        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used in-game.");
            return true;
        }

        $playerName = $sender->getName();
        $currentTime = time();

        // Check if the player is on cooldown
        if (isset($this->cooldowns[$playerName]) && $currentTime - $this->cooldowns[$playerName] < $this->cooldown) {
            $remainingTime = $this->cooldown - ($currentTime - $this->cooldowns[$playerName]);
            $sender->sendMessage(TextFormat::RED . "You must wait " . $remainingTime . " seconds before using this command again.");
            return true;
        }

        // Set the new cooldown time
        $this->cooldowns[$playerName] = $currentTime;
        $choice = random_int(0, 3);
        if ($choice === 0) {
            $this->giveTreat($sender);
        } elseif ($choice === 1 || $choice === 3) {
            $this->giveTrick($sender);
        } else {
            $this->sendNothingMessage($sender);
        }

        return true;
    }

    private function giveTreat(Player $player): void {
        $amount = random_int(1, 6);
        $cookie = VanillaItems::COOKIE();
        $cookie->setCount($amount);
        $player->getInventory()->addItem($cookie);
        $player->sendMessage(TextFormat::GREEN . "You got a treat! You've received " . $amount . " cookie" . ($amount > 1 ? "s" : "") . "!");
    }

    private function giveTrick(Player $player): void {
        if ($this->softDependenciesMet()) {
            $choice = random_int(0, 1);
            if ($choice === 0) {
                $this->applyNegativeEffect($player);
            } else {
                $this->executeLMAOCommand($player);
            }
        } else {
            $this->applyNegativeEffect($player);
        }
    }

    private function applyNegativeEffect(Player $player): void {
        $negativeEffects = [
            VanillaEffects::BLINDNESS(),
            VanillaEffects::POISON(),
            VanillaEffects::NAUSEA(),
            VanillaEffects::HUNGER(),
            VanillaEffects::WITHER()
        ];

        $effect = $negativeEffects[array_rand($negativeEffects)];
        $duration = random_int(15, 24) * 20;
        $amplifier = random_int(0, 2);

        $effectInstance = new EffectInstance($effect, $duration, $amplifier);
        $player->getEffects()->add($effectInstance);
        $player->sendMessage(TextFormat::RED . "You got tricked! You've been affected by " . Server::getInstance()->getLanguage()->translate($effect->getName()) . " for " . ($duration / 20) . " seconds!");

        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            if ($p !== $player) {
                $p->sendMessage(TextFormat::YELLOW . $player->getName() . " got tricked! They've been affected by " . Server::getInstance()->getLanguage()->translate($effect->getName()) . " for " . ($duration / 20) . " seconds!");
            }
        }
    }

    private function executeLMAOCommand(Player $player): void {
        $console = new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage());
        $randLMAO = random_int(1, 8);
        $commands = [
            1 => ["rickroll", "You've been rickrolled!"],
            2 => ["push", "Away you go!"],
            3 => ["boom", "You've been blown up!"],
            4 => ["launch", "To the moon you go!"],
            5 => ["spam", "You've been spammed!"],
            6 => ["crash", "You've crashed out of the game!"],
            7 => ["infinitedeath", "You've been set to near-infinite death!", random_int(8, 24)],
            8 => ["burn ", "You've been set on fire for a few seconds!",  random_int(2, 16)]
        ];

        $cmd = $commands[$randLMAO][0];
        $msg = $commands[$randLMAO][1];
        $cmdArg = isset($commands[$randLMAO][2]) ? $commands[$randLMAO][2] : "";
        Server::getInstance()->getCommandMap()->dispatch($console, "lmao " . $cmd . " " . $player->getName() . " " . $cmdArg);
        $player->sendMessage(TextFormat::RED . "You got tricked! " . $msg);

        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            if ($p !== $player) {
                $p->sendMessage(TextFormat::YELLOW . $player->getName() . " got tricked! " . $msg);
            }
        }
    }

    private function sendNothingMessage(Player $player): void {
        $messages = [
            "You got nothing this time!",
            "Should you be pleased or disappointed? You decide!",
            "Were you expecting a treat or a trick? You got nothing this time!"
        ];
        $player->sendMessage(TextFormat::GRAY . $messages[array_rand($messages)]);
    }

    private function softDependenciesMet(): bool {
        return Server::getInstance()->getPluginManager()->getPlugin("lmao") !== null;
    }
}
