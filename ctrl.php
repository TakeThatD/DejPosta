<?php
require_once 'init.php';

getConf()->login_action = 'loginShow';

//przekazanie do Smarty informacji o id użytkownika i jego roli
getSmarty()->assign('id_uzytkownika', getFromSession("_user_id"));
getSmarty()->assign('rola_uzytkownika', getFromSession("_user_role"));

switch ($action){
	//login
	case 'loginShow':
		control('/app/login/','LoginCtrl','generateView'); // publiczna
	case 'login':
		control('/app/login/','LoginCtrl','doLogin'); // publiczna
	case 'logout':
		control('/app/login/','LoginCtrl','doLogout'); // publiczna

	//uzytkownik
	case 'nowyUzytkownik':
		control('/app/uzytkownik/edytuj/','UzytkownikEdytujCtrl','wygenerujWidok');
	case 'edytujUzytkownika':
		control('/app/uzytkownik/edytuj/','UzytkownikEdytujCtrl','edytujUzytkownika', ['admin']);
	case 'zapiszUzytkownika':
		control('/app/uzytkownik/edytuj/','UzytkownikEdytujCtrl','zapiszUzytkownika');
	case 'usunUzytkownika':
		control('/app/uzytkownik/edytuj/','UzytkownikEdytujCtrl','usunUzytkownika', ['admin']);
	case 'uzytkownikLista':
		control('/app/uzytkownik/lista/','UzytkownikListaCtrl','przetwarzaj', ['admin']);

	//posty
	case 'zapiszPost':
		control('/app/post/edycja/', 'PostEdycjaCtrl', 'zapiszPost', ['user', 'admin']);
	case 'usunPost':
		control('/app/post/edycja/', 'PostEdycjaCtrl', 'usunPost', ['user', 'admin']);

	//tematy
	case 'pokazTemat':
		control('/app/temat/pokaz/', 'TematPokazCtrl', 'przetwarzaj', ['user', 'admin']);
	case 'dodajTemat':
		control('/app/temat/edycja/', 'TematEdycjaCtrl', 'wygenerujWidok', ['user', 'admin']);
	case 'edytujTemat':
		control('/app/temat/edycja/', 'TematEdycjaCtrl', 'edytujTemat', ['user', 'admin']);
	case 'zapiszTemat':
		control('/app/temat/edycja/', 'TematEdycjaCtrl', 'zapiszTemat', ['user', 'admin']);
	case 'usunTemat':
		control('/app/temat/edycja/', 'TematEdycjaCtrl', 'usunTemat', ['user', 'admin']);
	case 'tematListaTabela':
		control('/app/temat/lista/','TematListaCtrl','wygenerujWidokTabeli');
	default: //tematLista
		control('/app/temat/lista/','TematListaCtrl','wygenerujWidok');
}
