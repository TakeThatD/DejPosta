<?php
require_once "LoginForm.class.php";

class LoginCtrl{
	private $form;
	private $temp;

	public function __construct(){
		//stworzenie potrzebnych obiektów
		$this->form = new LoginForm();
	}

	public function validate() {
		$this->form->login = getFromRequest('login',true,'Błędne wywołanie systemu');
		$this->form->pass = getFromRequest('pass',true,'Błędne wywołanie systemu');

		//nie ma sensu walidować dalej, gdy brak parametrów
		if (getMessages()->isError()) return false;

		// sprawdzenie, czy potrzebne wartości zostały przekazane
		if (empty($this->form->login)) {
			getMessages()->addMessage(new Message('Nie podano loginu',Message::ERROR));
		}
		if (empty($this->form->pass)) {
			getMessages()->addMessage(new Message('Nie podano hasła',Message::ERROR));
		}

		//nie ma sensu walidować dalej, gdy brak wartości
		if (getMessages()->isError()) return false;

		// sprawdzenie, czy dane logowania poprawne
		// (takie informacje najczęściej przechowuje się w bazie danych)
		$this->temp = getDB()->get('uzytkownik', '*', [
			"login" => $this->form->login,
			"haslo" => $this->form->pass,
			"rola[!]" => "del"
		]);

		if ($this->temp!=null) {
			addRole($this->temp['rola']);
			$_SESSION['_user_id'] = $this->temp['id_uzytkownika'];
			$_SESSION['_user_role'] = $this->temp['rola'];
		} else {
			getMessages()->addMessage(new Message('Niepoprawny login lub hasło',Message::ERROR));
		}

		return ! getMessages()->isError();
	}

	public function doLogin(){
		if ($this->validate()){
			//zalogowany => przekieruj na główną akcję (z przekazaniem messages przez sesję)
			getMessages()->addMessage(new Message('Poprawnie zalogowano do systemu',Message::INFO));
			storeMessages();
			redirectTo("tematLista");
		} else {
			//niezalogowany => pozostań na stronie logowania
			$this->generateView();
		}
	}

	public function doLogout(){
		// 1. zakończenie sesji
		session_destroy();
		// 2. idź na stronę główną (z przekazaniem messages przez sesję)
		session_start(); //rozpocznij nową sesję w celu przekazania messages w sesji
		getMessages()->addMessage(new Message('Poprawnie wylogowano z systemu',Message::INFO));
		storeMessages();
		redirectTo('tematLista');
	}

	public function generateView(){
		loadMessages();
		getSmarty()->assign('form',$this->form); // dane formularza do widoku
		getSmarty()->display(getConf()->root_path.'/app/login/LoginView.html');
	}
}
