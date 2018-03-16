<?php
  require_once "TematEdycjaFormularz.class.php";

  class TematEdycjaCtrl {

    /* zmienna przechowująca dane formularza (przesłane z widoku) */
    private $formularz;

    /* zmienna przechowująca rekord z bazy danych (używany do pobrania danych z bazy
       i przekazaniu ich do smarty który wypełni pola formularza danymi wartościami) */
    private $rekord;

    /* konstruktor inicjujący zmienną $formularz, tworzy obiekt klasy TematEdycjaFormularz */
    public function __construct() {
      $this->formularz = new TematEdycjaFormularz();
    }

    /* walidacja danych przed zapisaniem */
    public function walidacjaZapis(){
      // 1. sprawdzenie czy wszystkie wymagane pola zostały przesłane
      /* Pobranie danych formularza z zapytania przy pomocy funkcji getFromRequest,
         zapis danych do zmiennej $this->formularz, jeżeli aplikacja została wywoływana
         bezpośrednio, a nie z formularza (czyli tak jak należy) to wartość przesłanych
         danych będzie równa null (wtedy getFromRequest doda wiadomość błędu do wiadomości) */
      $this->formularz->id_tematu = getFromRequest("id_tematu", true, "Błędne wywołanie aplikacji");
      $this->formularz->nazwa = getFromRequest("nazwa", true, "Błędne wywołanie aplikacji");
      $this->formularz->tresc = getFromRequest("tresc", true, "Błędne wywołanie aplikacji");

      /* jeżeli wystąpiły jakieś wiadomości błędów, zwróć fałsz (walidacja niepoprawna) */
      if (getMessages()->isError()) { return false; }

      // 2. sprawdzenie czy przekazane wartości nie są puste
      /* jeżeli pole formularza nie zostało wypełnione to wartość będzie pusta, należy sprawdzić tą zależność
         tylko jeżeli dana wartość jest konieczna do poprawnego zapisu. Jeżeli będzie pusta - dodaj błąd
         empty() zwraca true jeżeli podany ciąg jest pusty
         trim() usuwa wszystkie znaki białe z początku i końca podanego ciągu znaków */
      if (empty(trim($this->formularz->nazwa))) {
        getMessages()->addMessage(new Message('Wprowadz nazwę',Message::ERROR));
      }
      if (empty(trim($this->formularz->tresc))) {
        getMessages()->addMessage(new Message('Wprowadz treść',Message::ERROR));
      }

      /* isError zwraca true jeżeli wystąpił jakiś błąd, jednak wystąpienie błędu oznacza że walidacja nie zakończyła się
         pomyślnie więc trzeba tą wartość zanegować (!). tak samo jeżeli nie wystąpił żaden błąd to walidacja musi zwrócić
         wartość true */
      return !getMessages()->isError();
    }

    /*walidacja przekazanego id przed wczytaniem danych do edycji */
    public function walidacjaEdycja(){
      /* do przeprowadzenia edycji wymagane jest podanie id tematu który będzie edytowany, aby sprawdzić czy zostało pobrane
      i przypisać je do zmiennej formularza, używana jest funkcja getFromRequest (opisana wyżej), pobiera id z zapytania
      (sprawdzenie czy zostało podane, jeżeli nie - błąd w postaci wiadomości )
      funkcja zwraca zanegowaną wartość którą zwróci funkcja isError() - opis wyżej */
      $this->formularz->id_tematu = getFromRequest("id_tematu", true, "Błędne wywołanie aplikacji");
      return !getMessages()->isError();
    }

    /*funkcja pobierająca z bazy danych informacji o temacie z danym id (pobranym z zapytania),
      następnie wstawia informacje w zmienną formularz która później przekazywana jest przez Smarty do pliku html
      gdzie pola formularza są uzupełniane jej wartościami (value={$zmiennaSmarty})*/
    public function edytujTemat(){
      // 1.1 jeżeli walidacja danych zakończy się sukcesem
      if ($this->walidacjaEdycja()){
        // 1.1.1 pobierz rekord o podanym id z bazy danych
        /* get([tabela], [kolumny], [warunek]) pobiera jeden rekord z bazy danych (wybrane kolumny) z podanej tabeli
           rekord musi spełniać przekazane warunki.
           tutaj z tabeli temat pobieramy wiersz o id_tematu równym przekazanemy w formularzu, interesują nas jedynie
           kolumny nazwa i treść bo tylko te można edytować (edycja nie zmienia ani daty utworzenia ani autora tematu,
           więc ich edycja jest zbędna) */
        $this->rekord = getDB()->get('temat', [
          'nazwa',
          'tresc'
        ],[
          'id_tematu' => $this->formularz->id_tematu
        ]);

        // 1.1.2 jeżeli pobrano z bazy, wpisz do zmiennej formularza
        /* jeżeli nie wystąpił żaden błąd podczas obsługi bazy danych, przepisz pobrane informacje do zmziennej
           przechowującej dane z formularza */
        if (getDB()->error()[0]==0){
          // 1.1.2.1 przypisz do zmiennej formularza wartości pobrane z bazy danych
  				$this->formularz->nazwa = $this->rekord['nazwa'];
  				$this->formularz->tresc = $this->rekord['tresc'];
        // 1.1.3 jeżeli wystąpił błąd przy pobieraniu z bazy
  			} else {
          // 1.1.3.1 dodaj wiadomość błędu
          /* if w środku sprawdza czy włączony jest tryb debugowania w config.php - jeżeli tak to doda kolejną
             wiadomość błędu z błędem bazy danych */
  				getMessages()->addMessage(new Message('Nieoczekiwany błąd podczas odczytu',Message::ERROR));
  				if (getConf()->debug) getMessages()->addMessage(new Message(var_export(getDB()->error(), true),Message::ERROR));
  			}

        // 1.1.4 wywołaj funkcję generującą widok
        $this->wygenerujWidok();

      // 1.2 jeżeli walidacja niepoprawna = nie podano id tematu
      } else {

        // 1.2.1 zapisz błędy do zmiennej sesji
        /* wiadomości przechowywane są w obiekcie Messages, przy każdym zapytaniu jest on tworzony, i po każdym zapytaniu
           niszczony, aby można było przesyłać wiadomości między zapytaniami trzeba je przekazać do zmiennej sesyjnej,
           służy do tego funkcja storeMessages(), aby załadować wiadomości ze zmiennej sesyjnej na powrót do obieku
           Messages należy wywołać funkcję loadMessages() */
        storeMessages();

        // 1.2.2 przekieruj na stronę z tematami
        /* wysłanie zapytania do pliku ctrl.php z parametrem akcji tematLista. w skrócie parametr akcji tematLista jest doklejany
           do adresu url pliku ctrl.php i całość wygląda tak: localhost/DejPosta/ctrl.php?a=tematLista */
        redirectTo(tematLista);
      }
    }

    /*funkcja zapisująca przekazane dane z formularza jako nowy temat lub edycję
      istniejącego już tematu (jeżeli zostało przekazane jego id) */
    public function zapiszTemat(){
      // 1. wywołanie funkcji walidującej
      if ($this->walidacjaZapis()) {
        // 1.1 jeżeli walidacja zwróci prawdę, sprawdz czy został przekazany parametr id_tematu
        /* przy edycji przed wygenerownaiem formularza wywoływana jest funkcja edytujTemat która
           przekazuje do formularza id edytowanego tematu (i inne wartości jak treść i nazwa)
           jeżeli formularz został wywołany bezpośrednio (funkcja wygenerujWidok) to nie zostaje
           przekazane do niego żadne id) */
        if ($this->formularz->id_tematu == '') {
          // 1.1.1 jeżeli nie - nowy temat, zapisz dane do bazy
          /* insert([tabela], [[kolumna] => [wartosc], ...]) dodaje do tabeli nowy wpis, przypisując kolumnie podaną wartość */
          getDB()->insert('temat', [
            'id_uzytkownika' => getFromSession("_user_id"),
            'nazwa' => $this->formularz->nazwa,
            'tresc' => $this->formularz->tresc
          ]);

          // 1.1.2 wpisz do formularza id ostatnio wstawionego elementu
          /* potrzebne żeby później przekierować na stronę nowo utworzonego tematu */
          $this->formularz->id_tematu = getDB()->id();

        } else {
          // 1.2.1 jeżeli tak - edycja tematu, zaktualizuj dane w bazie
          /* update([tabela], [[kolumna] => [wartość], ...], [warunek]) aktualizuje podane wpisty w tabeli, aktualizacja
             dotyczy tylko wpisów spełniające warunek i dotyczy jedynie podanych kolumn */
          getDB()->update('temat', [
            'nazwa' => $this->formularz->nazwa,
            'tresc' => $this->formularz->tresc
          ],[
            'id_tematu' => $this->formularz->id_tematu
          ]);
        }

        // 1.3 obsługa błędów bazy danych
        /* opisane wyżej - dokładnie to samo */
        if (getDB()->error()[0]!=0){
          getMessages()->addMessage(new Message('Wystąpił błąd podczas pobierania rekordów',Message::ERROR));
          if (getConf()->debug) getMessages()->addMessage(new Message(var_export(getDB()->error(), true),Message::ERROR));
        }

        // 1.4 zapisz wiadomości do zmiennej sesji
        /* opisane wyżej - to samo */
        storeMessages();

        // 1.5 przekieruj na listę tematów
        /* opisane wyżej - to samo */
        redirectTo('pokazTemat&id_tematu='.$this->formularz->id_tematu);

      // 2. jeżeli walidacja zakończyła się niepowodzeniem (nieporawne/niekompletne dane)
      } else {
        // 2.1 wygeneruj ponownie widok formularza
        $this->wygenerujWidok();
      }
    }

    /* funkcja usuwająca temat z bd - usunięcie polega na zmianie daty na 0 */
    public function usunTemat(){
      // 1. wywołaj funkcję walidującą dane (parametr id)
      if ($this->walidacjaEdycja()) {
        // 2. usuń post - zmieniając jego datę na 0
        /* update opisany wyżej */
        getDB()->update('temat', [
          "data_czas" => 0
        ], [
          'id_tematu' => $this->formularz->id_tematu
        ]);

        // 3. obsługa błędów bazy daych
        /* opisane wyżej */
        if (getDB()->error()[0]!=0){ //jeśli istnieje kod błędu
          getMessages()->addMessage(new Message('Wystąpił błąd podczas zapisu rekordów',Message::ERROR));
          if (getConf()->debug) getMessages()->addMessage(new Message(var_export(getDB()->error(), true),Message::ERROR));
        }

        // 4. zapisane wiadmości w sesji
        /* opisane wyżej */
        storeMessages();

        // 5. przekierowanie na listę tematów
        /* opisane wyżej */
        redirectTo('tematLista');
      }
    }

    /* funkcja generująca widok formularza */
    public function wygenerujWidok(){

      /*przekazanie do smaryty zmiennej przechowywującej dane formularza
        jeżeli funkcja wygenerujWidok wywoływana jest bezpośrednio to przekazane
        zostaną puste wartości stworzone przez konstruktor klasy (nie wystąpi null) */
      getSmarty()->assign('formularz', $this->formularz);

      /* Wywołanie Smarty do wyświetlenia pliku html */
      getSmarty()->display(getConf()->root_path.'/app/temat/edycja/TematEdycja.html');
    }
  }
