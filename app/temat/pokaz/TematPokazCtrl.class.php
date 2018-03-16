<?php
  /* wczytanie pliku z klasą PostListCtrl, pokazanie tematu wymaga wyświetlenia postów, ale wyświetleniem postów nie powinien się
     zajmować kontroler tematów a postów więc konieczne jest jego załadowanie żeby można go było używać */
  require_once dirname(__FILE__).'\..\..\post\lista\PostListaCtrl.class.php';

  class TematPokazCtrl {

    /* przechowuje id tematu do wyświetlenia */
    private $id_tematu;
    /* przzechowuje informacje o temacie do wyświetlenia */
    private $temat;

    /* konstruktor inicjuje zmienne temat (pusta tablica) i posty (obiekt klasy PostListCtrl) */
    public function __construct(){
      $this->temat = [];
      $this->posty = new PostListaCtrl;
    }

    /* funkcja walidująca poprawność id przekazanego tematu - sprawcdza czy id zostało przekazane
       dokładny opis w TematEdycjaCtrl.class.php */
    public function walidacjaID(){
      $this->id_tematu = getFromRequest('id_tematu', true, "Błędne wywołanie aplikacji");
      return !getMessages()->isError();
    }

    /* funkcja przygotowująca informacje do wyświetlenia */
    public function przetwarzaj(){
      // 1. sprawdzenie poprawności id, funkcja walidująca
      if ($this->walidacjaID()) {
        // 2. pobranie rekordu z bazy danych
        /* get opisany w TematEdycjaCtrl.class.php */
        $this->temat = getDB()->get('temat', [
          '[>]uzytkownik' => 'id_uzytkownika'
        ], '*', [
          'temat.id_tematu' => $this->id_tematu
        ]);

        // 3. wywołanie w utworzonym w konstruktorze obiekcie klasy PostListaCtrl metody przygotujListePostow(id_tematu)
        //która pobiera informacje o postach i przekazuje do smarty listę postów dla danego tematu
        $this->posty->przygotujListePostow($this->id_tematu);

        // 4. przekazanie informacji (zmiennej temat) o temacie do smarty
        getSmarty()->assign('temat', $this->temat);

        // 5. wywołanie funkcji generującej Widok
        $this->wygenerujWidok();

      // 1.2 jeżeli walidacja się nie powiodła (brak id)
      } else {

        // 1.3 zapisz wiadomości do sesji
        /* opis w TematEdycjaCtrl.class.php */
        storeMessages();

        // 1.4 przekieruj na listę tematów
        /* opis w TematEdycjaCtrl.class.php */
        redirectTo('tematLista');
      }
    }

    /* funkcja generująca widok */
    /* opis w TematEdycjaCtrl.class.php */
    public function wygenerujWidok() {
      /* załadowanie widomości z sesji do obiektu Messages */
      loadMessages();
      getSmarty()->display(getConf()->root_path.'/app/temat/pokaz/TematPokaz.html');
    }
  }
