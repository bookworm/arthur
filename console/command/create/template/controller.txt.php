namespace {:namespace};

use {:use};
use arthur\action\DispatchException;

class {:class} extends \arthur\action\Controller {

	public function index() {
		${:plural} = {:model}::all();
		return compact('{:plural}');
	}

	public function view() {
		${:singular} = {:model}::first($this->request->id);
		return compact('{:singular}');
	}

	public function add() {
		${:singular} = {:model}::create();

		if (($this->request->data) && ${:singular}->save($this->request->data)) {
			$this->redirect(array('{:name}::view', 'args' => array(${:singular}->id)));
		}
		return compact('{:singular}');
	}

	public function edit() {
		${:singular} = {:model}::find($this->request->id);

		if (!${:singular}) {
			$this->redirect('{:name}::index');
		}
		if (($this->request->data) && ${:singular}->save($this->request->data)) {
			$this->redirect(array('{:name}::view', 'args' => array(${:singular}->id)));
		}
		return compact('{:singular}');
	}

	public function delete() {
		if (!$this->request->is('post') && !$this->request->is('delete')) {
			$msg = "{:name}::delete can only be called with http:post or http:delete.";
			throw new DispatchException($msg);
		}
		{:model}::find($this->request->id)->delete();
		$this->redirect('{:name}::index');
	}
}