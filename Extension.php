<?php
	namespace Bolt\Extension\TheTemportalist\Notifications;
	use Symfony\Component\HttpFoundation\Request;
	use Bolt;
	use Silex\Application;

	class Extension extends \Bolt\BaseExtension {

		public function getName() {
			return "notifications";
		}

		public function initialize() {

			// todo configged 'Notifications'
			// get() post() or match() (for both)
			// $this->config['path']
			$this->app->match("/Notifications, array($this, 'onNotify'))->bind('onNotify');

			return true;
		}

		public function onNotify(Request $request, $errors = null) {
			$log = "onNotify Log:".PHP_EOL;

			$table = $this->config['databaseTable'];
			$fromEmail = $this->config['from']['email'];
			$fromName = $this->config['from']['name'];

			$log .= "Table|".$table.PHP_EOL;
			$log .= "From|".$fromName.":".$fromEmail.PHP_EOL;

			$this->cleanTable($table);
			// modid
			$column = $this->getColumn();
			if (!empty($column)) {
				// todo find out which mod we are looking for
				$subscriptions = $this->getSubscriptions($table, $column["name"]);

				$html = $column["name"] . " has release " . $column['type'] . " " . $column["number"];
				$log .= "Subject|".$html.PHP_EOL;
				$subject = new \Twig_Markup($html, 'UTF-8');
				$html = $column["url"];
				$log .= "Body|".$html.PHP_EOL;
				$body = new \Twig_Markup($html, 'UTF-8');
				$emailToSend = \Swift_Message::newInstance()
					->setSubject($subject)
					->setBody(strip_tags($body))
					->addPart($body, 'text/html')
				;

				$log .= "Email List:".PHP_EOL;
				foreach ($subscriptions as $sub) {
					$emailToSend->setFrom(array(
						$fromEmail => $fromName
					));
					$log .= "\t".$sub["name"].":".$sub["email"].PHP_EOL;
					$emailToSend->setTo(array(
						$sub["email"] => $sub["name"]
					));
					$didSend = $this->app['mailer']->send($emailToSend);
					$log .= "\tDidSend|".$didSend.PHP_EOL;
				}
			}

			file_put_contents(
				$this->app['resources']->getPath("root/logs/Post_".date("n.j.Y").".txt"),
				$log, FILE_APPEND
			);

			return '<h1>GawainLynch said so :P</h1>';
		}

		private function getColumn() {
			$column = array();
			$data = file_get_contents('php://input');
			if (strlen($data) > 0) {
				$json = json_decode($data, true);
				$name = $json['name'];
				$type = $this->config['types'][$name];
				$status = $json[$type]['status'];
				if (empty($status) || $status === 'SUCCESS') {
					$column["name"] = $name;
					$column["type"] = $type;
					$column["url"] = $json[$type]['full_url'];
					$column["number"] = $json[$type]['number'];
				}
			}
			return $column;
		}

		private function cleanTable($table) {
			$emails = $this->app['db']->fetchAll(
				"SELECT email FROM " . $table . " GROUP BY email"
			);
			//dump($emails);
			foreach ($emails as $emailAr) {
				//dump($emailAr);
				$email = $emailAr["email"];
				$emailSet = $this->app['db']->fetchAll(
					"SELECT id FROM " . $table . " WHERE email='" . $email . "'"
				);
				//dump($emailSet);
				$largestID = 0;
				foreach ($emailSet as $ids) {
					//dump($ids);
					$id = $ids["id"];
					//echo $largestID . ":" . $id . ":" . ($id > $largestID);
					if ($id > $largestID) {
						if ($largestID > 0) {
							echo "removing " . $largestID . " of " . $email . "<br>";
							if (!$this->delete(
								$table, array(
									'email' => $email, 'id' => $largestID
								)
							)) echo "Could not remove " . $largestID . "<br>";
						}
						$largestID = $id;
					}
					else {
						echo "removing " . $id . " of " . $email . "<br>";
						if (!$this->delete(
							$table, array(
								'email' => $email, 'id' => $id
							)
						)) echo "Could not remove " . $id . "<br>";
					}
				}
			}
		}

		private function delete($table, $conditions) {
			return $this->app['db']->delete($table, $conditions);
		}

		private function getSubscriptions($table, $column) {
			$subs = array();
			/*
			$mods = array(
				"Origin",
                "Compression",
                "Weeping Angels",
                "Tardis"
			);
			$modsStr = "";
			foreach ($mods as $modname) {
				if ($modsStr !== "") {
					$modsStr = $modsStr . ", ";
				}
				$modsStr = $modsStr . $modname;
			}
			*/
			$subscriptions = $this->app['db']->fetchAll(
				"SELECT name, email, " . $column . " FROM " . $table// . " GROUP BY email"
			);
			foreach ($subscriptions as $subscription) {
				//dump($subscription);
				//echo $subscription[$column];
				if ($subscription[$column] > 0)
					$subs[] = array("email" => $subscription["email"], "name" => $subscription["name"]);
			}
			return $subs;
		}

	}

?>