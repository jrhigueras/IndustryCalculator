<?php
class CommandHandler {
	public static $registeredCommands = array();
	public static function autocomplete($string, $index) {
		$matches = array();

		foreach(array_keys(self::$registeredCommands) as $command)
			if(stripos($command, $string) === 0)
				$matches[] = $command;

		if($matches == false);
			$matches[] = '';

		return $matches;
	}
	public static function tryExecute(string $command) : bool {
		if(!isset(self::$registeredCommands[$command])) {
			//TODO: exceptions?
			return false;
		}
		self::$registeredCommands[$command]->execute();
		return true;
	}
	public static function discover() {
		include("classes/Command.php");

		// TODO: proper command discovery
		include("commands/help.php");
		include("commands/status.php");

		$commands = array_filter(
			get_declared_classes(),
			function($className) {
				return is_subclass_of($className, "Command");
			}
		);

		foreach($commands as $command)
			self::registerCommand(new $command);
	}

	public static function registerCommand(Command $command) {
		$name = $command->getName();
		self::$registeredCommands[$name] = $command;
	}
	public static function monkeyTest() {
		#This is a first test to tell SHM that there're 1 monkey doing things, for later discovery.
		global $shm_id;
		$bytes = 100;
		if(pcntl_fork() == 0) {
			//guess the available memory block

			do{
				$start = 0;
				$data = shmop_read($shm_id, $start, $bytes);
				$start += $bytes;
			} while(md5($data) != "6d0bb00954ceb7fbee436bb55a8397a9" || $start == 1000);

			$start -= $bytes;
			shmop_write($shm_id, str_repeat("\00", 100), $start);
			shmop_write($shm_id, serialize(array("name" => "syncAmazonFull", "status" => "sleeping for 30")), $start);
			sleep(30);
			shmop_write($shm_id, str_repeat("\00", 100), $start);
			shmop_write($shm_id, serialize(array("name" => "syncAmazonFull", "status" => "sleeping for 60")), $start);
			sleep(60);
			shmop_write($shm_id, str_repeat("\00", 100), $start);
			shmop_write($shm_id, serialize(array("name" => "syncAmazonFull", "status" => "done")), $start);
			sleep(3);
			shmop_write($shm_id, str_repeat("\00", 100), $start);
			exit;
		}
		return;
	}

	public static function exit() {
		exit(0);
	}

	public static function status() {
		# This shit should connect to SHM and guess how many monkeys are connected and doing things.
		global $runningMonkeys, $shm_id;

		$runningMonkeys = 0;
		$start=0;
		$bytes=100;
		$monkeys = array();
		for($i=0;$i<900;$i+=100) {
			$data = shmop_read($shm_id, $start, $bytes);
			if(md5($data) != "6d0bb00954ceb7fbee436bb55a8397a9") {
				$monkeys[] = $data;
				$runningMonkeys++;
			}
			$start+=$bytes;
		}
		$i=0;
		if(count($monkeys) > 0) {
			foreach($monkeys as $monkey) {
				$i++;
				echo "Monkey $i:\n$monkey\n\n";
			}
		}

		if($runningMonkeys == 0) {
			echo "There're no monkeys running at this moment.\n";
			return false;
		}
	}
}