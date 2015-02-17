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
			$emails = $this->$app['db']->fetchAssoc(
				'SELECT email FROM beta GROUP BY email'
			);
			var_dump($emails);

		}

	}

?>