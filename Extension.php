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
			$emails = $this->app['db']->fetchAll(
				'SELECT email FROM ' . $table . ' GROUP BY email'
			);
			dump($emails);

			return '<h1>GawainLynch said so :P</h1>';
		}

	}

?>