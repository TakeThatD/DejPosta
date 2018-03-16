<?php
  require_once "TematFormularzWyszukiwania.class.php";

class TematListaCtrl {

    /* zmienna przechowująca otrzymane wartości z pól formularz_wyszukiwania
      w tym przypadku jedyną otrzymaną wartością jest nazwa tematu (filtrowanie) */
    private $formularz_wyszukiwania;

    /* zmienna przechowująca wyniki otrzymane z zapytania do bazy danych */
    private $rekordy;

    /* konstruktor inicjujący zmienną formularz_wyszukiwania
      (utworzenie obiektu klasy TematFormularzWyszukiwania) */
    public function __construct() {
      /* z $this-> ponieważ zmienna nie została zadeklarowana w funkcji pobierzParametry
         a na zewnątrz, w klasie TematListaCtrl */
      $this->formularz_wyszukiwania = new TematFormularzWyszukiwania();
    }

    /* pobiera parametry z formularza wyszukiwania
       i przypisuje do zmiennej formularz_wyszukiwania (w odpowiednie pola obiektu) */
    public function pobierzParametry(){
      /* z $this-> ponieważ zmienna nie została zadeklarowana w funkcji pobierzParametry
         a na zewnątrz, w klasie TematListaCtrl */
      $this->formularz_wyszukiwania->nazwa = getFromRequest('fw_nazwa_tematu', false);

      /* walidacja nie jest wymagana z uwagi iż w wypadku gdy nie został przekazany żaden
         parametr (a raczej pusta wartość '') wyświetlona zostanie cała lista. */
    }

    /* przetwarza dane do wyświetlenia */
    public function przetwarzaj(){
      // 1. wywołuje funkcję pobierającą parametry z formularza
      $this->pobierzParametry();
      // 2. wczytanie wiadomości przekazanych przez zmienną session
      /* opisane w TematEdycjaCtrl.class.php */
      loadMessages();

      // 3. przygotowanie warunku wyszukiwania
      /* przygotuj pustą zmienną do której zapisane zostaną warunki wyszukiwania tematów
        (bez $this-> ponieważ zmienna będzie używana jedynie wewnątrz funkcji przetwarzaj) */
      $where = [];

      /* jeżeli przekazany został parametr wyszukiwania z formularza (nazwa tematu)
         i nie jest on pustym ciągiem znaków */
      if (isset($this->formularz_wyszukiwania->nazwa) && strlen($this->formularz_wyszukiwania->nazwa) > 0) {
        /* dodaj warunek do zmiennej przechowującej warunki $where
           $where jest tablicą asocjacyjną (["klucz"] => "wartość")
           dodanie % na końcu i początku zastępuje dowolny ciąg znaków */
        $where['temat.nazwa[~]'] = "%".$this->formularz_wyszukiwania->nazwa."%";
      }

      /* Wyłączenie usuniętych postów (data_czas  ustawione na 0) z wyników bazy */
      $where['temat.data_czas[!]'] = 0;

      /* dodanie do zmiennej warunku sposobu sortowania rekordów z bazy danych
         w tym wypadku date_time czyli po czasie utworzenia tematu */
      $where['ORDER'] = ["data_czas" => "DESC"];

      // 4. zapytanie do bazy danych
      /* pobranie rekordów z bazy danych do zmiennej $rekordy
         select z joinem opisany w PostListaCtrl.class.php */
      $this->rekordy = getDB()->select('temat', [
        '[>]uzytkownik' => 'id_uzytkownika'
      ],[
        'temat.id_tematu',
        'temat.nazwa',
        'temat.data_czas',
        'uzytkownik.id_uzytkownika',
        'uzytkownik.login'
      ], $where);

      // 5. obsługa ewentualnych błędów mogących wystąpić podczas obsługi bazy danych
      /* opisane w PostListaCtrl.class.php */
      if (getDB()->error()[0]!=0){
        getMessages()->addMessage(new Message('Wystąpił błąd podczas pobierania rekordów',Message::ERROR));
        if (getConf()->debug) getMessages()->addMessage(new Message(var_export(getDB()->error(), true),Message::ERROR));
      }

      /* przekazanie do smarty zmiennych zawierających wartości pól formularza
         i zmiennej rekordy która zawiera wyniki z bazy danych */
      getSmarty()->assign('formularz_wyszukiwania', $this->formularz_wyszukiwania);
      getSmarty()->assign('tematy', $this->rekordy);
    }

    /* funkcja generująca widok */
    public function wygenerujWidok(){
      /* wywołanie funkcji przetwarzającej dane */
      $this->przetwarzaj();
      /* nakazanie Smarty wyświetlenia danego pliku html */
      getSmarty()->display(getConf()->root_path.'/app/temat/lista/TematLista.html');
    }

    /* przekazuje dane do Smarty - wygeneruj widok samej tabeli (z zapytania ajax)*/
    public function wygenerujWidokTabeli(){
      /* wywołanie funkcji przetwarzającej dane */
      $this->przetwarzaj();
      /* nakazanie Smarty wyświetlenia danego pliku html */
      getSmarty()->display(getConf()->root_path.'/app/temat/lista/TematListaTabela.html');
    }
}
