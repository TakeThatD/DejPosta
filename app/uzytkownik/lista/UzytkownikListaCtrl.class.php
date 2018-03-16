<?php
  class UzytkownikListaCtrl {

    private $rekordy;

    public function przetwarzaj () {
      /* wczytaj wszystkich użytkowników z bazy, prócz tych których rola równa jest 'del' - czyli zostali usunięci */
      $this->rekordy = getDB()->select('uzytkownik', '*', [
        'rola[!]' => 'del'
      ]);

      getSmarty()->assign("uzytkownicy", $this->rekordy);

      $this->wygenerujWidok();
    }

    public function wygenerujWidok() {
      loadMessages();
      getSmarty()->display(getConf()->root_path."/app/uzytkownik/lista/UzytkownikLista.html");
    }
  }
