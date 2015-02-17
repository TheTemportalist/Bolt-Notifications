<?php
	namespace Bolt\Extension\TheTemportalist\Notifications;
	use Symfony\Component\HttpFoundation\Request;
	use Bolt;

	class Extension extends \Bolt\BaseExtension {

		public function getName() {
			return "notifications";
		}

		public function initialize(){

			// todo configged 'Notifications'
			// get() post() or match() (for both)
			$this->app->match("/Notifications", array($this, 'onNotify'))->bind('onNotify');

			return true;
		}

		public function onNotify(Request $request, $errors = null) {
			echo "Start<br>";
			$table = 'beta';
			$this->cleanTable($table);
			// todo find out which mod we are looking for
			$subscriptions = $this->getSubscriptions($table, "origin");

			dump($subscriptions);

			return '<h1>GawainLynch said so :P</h1>';
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
			$mods = array(
				"origin" => "Origin",
                "compression" => "Compression",
                "weepingangels" => "Weeping Angels",
                "tardis" => "Tardis"
			);
			$modsStr = "";
			foreach ($mods as $modid => $modname) {
				if ($modsStr !== "") {
					$modsStr = $modsStr . ", ";
				}
				$modsStr = $modsStr . $modid;
			}
			$subscriptions = $this->app['db']->fetchAll(
				"SELECT email, " . $modsStr . " FROM " . $table . " GROUP BY email"
			);
			foreach ($subscriptions as $subscription) {
				//dump($subscription);
				if ($subscription[$column] > 0)
					$sub[] = $subscription['email'];
			}
			return $subs;
		}

	}

?>