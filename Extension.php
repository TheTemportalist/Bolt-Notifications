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
			$this->app->post("/Notifications", array($this, 'onNotify'))->bind('onNotify');

			


			return true;
		}

		public function onNotify(Request $request, $errors = null) {
			$table = 'beta';
			$fromEmail = "the.country.gamer@gmail.com";
			$fromName = "TheTemportlistSite";

			$this->cleanTable($table);
			// modid
			$column = $this->getColumn();
			if (!empty($column)) {
				// todo find out which mod we are looking for
				$subscriptions = $this->getSubscriptions($table, $column["name"]);

				$subject = new \Twig_Markup($column["name"] . " has updated to " . $column["number"], 'UTF-8');
				$body = new \Twig_Markup($column["url"], 'UTF-8');
				$emailToSend = \Swift_Message::newInstance()
					->setSubject($subject)
					->setBody(strip_tags($body))
					->addPart($body, 'text/html')
				;

				foreach ($subscriptions as $sub) {
					$emailToSend->setFrom(array(
						$fromEmail => $fromName
					));
					$emailToSend->setTo(array(
						$sub["email"] => $sub["name"]
					));
					$this->app['mailer']->send($emailToSend);
				}
			}

			return '<h1>GawainLynch said so :P</h1>';
		}

		private function getColumn() {
			$column = array();
			$data = file_get_contents('php://input');
			if (strlen($data) > 0) {
				$json = json_decode($data, true);
				if ($json['build']['status'] === 'SUCCESS') {
					$column["name"] = $json['name'];
					$column["url"] = $json['build']['full_url'];
					$column["number"] = $json['build']['number'];
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