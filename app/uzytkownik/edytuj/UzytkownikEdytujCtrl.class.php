<?php
  require_once "UzytkownikFormularz.class.php";

  class UzytkownikEdytujCtrl {

    /* dane z formularza */
    private $formularz;
    /* lista użytkowników z bazy danych */
    private $rekord;

    /* konstruktor - inicjuje zmienną formularz jako obiekt klasy UzytkownikFormularz */
    public function __construct() {
      $this->formularz = new UzytkownikFormularz();
    }

    /* walidacja danych do zapisu, opisane w TematEditCtrl.class.php i PostEditCtrl.class.php */
    public function walidacjaZapis() {
      $this->formularz->id_uzytkownika = getFromRequest('id_uzytkownika',true,'Błędne wywołanie systemu');
      $this->formularz->login = getFromRequest('login',true,'Błędne wywołanie systemu');
      $this->formularz->haslo = getFromRequest('haslo',true,'Błędne wywołanie systemu');
      /* !!! skrócony if jeżeli funkcja getFromRequest jest różne od null to przypisz getFromRequest, jeżeli nie to 'user'
         jeżeli formularz wywoływany jest jako rejestracja nowego użytkownika to nie ma pola roli więc zwraca null,
         nie jest to błąd, a każdy nowy użytkownik ma być userem.
         jeżeli wywołano z panelu admina to występuje pole roli i powinno zwrócić daną wartość */
      $this->formularz->rola = null !== getFromRequest('rola',false) ? getFromRequest('rola',false) : 'user';

      //nie ma sensu walidować dalej, gdy brak parametrów
      if (getMessages()->isError()) return false;

      // sprawdzenie, czy potrzebne wartości zostały przekazane
      if (empty($this->formularz->login)) {
        getMessages()->addMessage(new Message('Nie podano loginu',Message::ERROR));
      }
      if (empty($this->formularz->haslo)) {
        getMessages()->addMessage(new Message('Nie podano hasła',Message::ERROR));
      }

      //nie ma sensu walidować dalej, gdy brak wartości
      if (getMessages()->isError()) return false;

      //sprawdzenie czy wartość pola rola jest poprawna
      if (($this->formularz->rola != 'user') && ($this->formularz->rola != 'admin')) {
        getMessages()->addMessage(new Message('Błędne wywołanie aplikacji',Message::ERROR));
      }

      return ! getMessages()->isError();
    }

    /* walidacja id przekazanego do edycji - opisane EdycjaTematCtrl i EdycjaPostCtrl */
    public function walidacjaID(){
      $this->formularz->id_uzytkownika = getFromRequest('id_uzytkownika', true, "Błędne wywołanie aplikacji");
      return !getMessages()->isError();
    }

    /* edycja użytkownika analogicznie do tego co jest w edytujTemat() w pliku EdytujTematCtrl.class.php */
    public function edytujUzytkownika() {
      if ($this->walidacjaID()){
        $this->rekord = getDB()->get('uzytkownik', '*', [
          'id_uzytkownika' => $this->formularz->id_uzytkownika
        ]);

        if (getDB()->error()[0]==0){ //jeśli istnieje kod błędu
          $this->formularz->login = $this->rekord['login'];
          $this->formularz->haslo = $this->rekord['haslo'];
          $this->formularz->rola = $this->rekord['rola'];

          $this->wygenerujWidok();
        } else {
          getMessages()->addMessage(new Message('Wystąpił błąd podczas pobierania rekordów',Message::ERROR));
          if (getConf()->debug) getMessages()->addMessage(new Message(var_export(getDB()->error(), true),Message::ERROR));
        }
      } else {
        storeMessages();
        redirectTo('uzytkownikLista');
      }
    }

    /* edycja użytkownika analogicznie do tego co jest w edytujTemat() w pliku EdytujTematCtrl.class.php */
    /*     !!!!!!!!!!!!   poza oznaczonymi fragmentami   !!!!!!!!!!!!!! */
    public function zapiszUzytkownika() {
      // 1. walidacja
      if ($this->walidacjaZapis()) {
        // 1.1 sprawdzenie czy przekazano id
        if ($this->formularz->id_uzytkownika == '') {
          // 1.1.1 jeżeli nie to nowy post

          // 1.1.2 sprawdzenie czy login nie jest zajęty
          /* w bazie może być już użytkownik o tym loginie i należy to sprawdzić, get pobiera z bazy wpisy w których
             login użytkownika odpowiada przesłanemu w formularzu, jeżeli istnieje to dodaje wiadomość błędu
             get opisany w TematEdytujCtrl i PostEdytujCtrl*/
          $this->rekord = getDB()->get('uzytkownik', '*', [ 'login' => $this->formularz->login]);
          if ($this->rekord) {
            // 1.1.2.1 jeżeli użytkownik istnieje to wyświetl błąd
            getMessages()->addMessage(new Message('Użytkownik o takim loginie już istnieje, wybirz inny',Message::ERROR));
            $this->formularz->id_uzytkownika = null;
          } else {
            // 1.1.2.2 jeżeli użytkownik nie istnieje to dodaj użytkownika
            getDB()->insert('uzytkownik', [
              'login' => $this->formularz->login,
              'haslo' => $this->formularz->haslo,
              'rola' => $this->formularz->rola
            ]);
            getMessages()->addMessage(new Message("Dodano nowego użytkownika, możesz się zalogować", Message::INFO));
          }
        } else {
          // 1.1.2 przekazano id, edycja użytkownika - zapis zmian
          getDB()->update('uzytkownik', [
            'login' => $this->formularz->login,
            'haslo' => $this->formularz->haslo,
            'rola' => $this->formularz->rola
          ],[
            'id_uzytkownika' => $this->formularz->id_uzytkownika
          ]);
        }

        // 1.2 obsługa błędów bazy danych
        /* opisane Temat/Post Ctrl */
        if (getDB()->error()[0]!=0){
          getMessages()->addMessage(new Message('Wystąpił błąd podczas zapisu rekordów',Message::ERROR));
          if (getConf()->debug) getMessages()->addMessage(new Message(var_export(getDB()->error(), true),Message::ERROR));
        } else {
          getMessages()->addMessage(new Message("Edytowano użytkownika", Message::INFO));
        }

        // 1.3 zapisz wiadomości w sesji
        /* opisane Temat/PostCtrl */
        storeMessages();

        // 1.4 przekierowanie zapytania
        /* jeżeli jest ktoś w roli admina to wywołano edycje lub dodanie użytkownika z listy użytkowników i tam
           nastąpi przekierowanie, jeżeli nie wywołał admin to była to rejestracja więc należy przekierować
           na stronę logowania */
        if (!getMessages()->isError()){
          if (inRole('admin')) {
            redirectTo('uzytkownikLista');
          } else {
            redirectTo('loginShow');
          }
        }
      }

      // 2. wywołanie funkcji generującej widok
      $this->wygenerujWidok();
    }

    /* usuwa użytkownika zmieniając jego rolę na 'del' */
    /* analogicznie do usuwania postu/tematu */
    public function usunUzytkownika(){
      if ($this->walidacjaID()){
        getDB()->update('uzytkownik', [
          'rola' => 'del'
        ],[
          'id_uzytkownika' => $this->formularz->id_uzytkownika
        ]);
      }

      redirectTo('uzytkownikLista');
    }

    /* funkcja generująca widok */
    public function wygenerujWidok(){
      getSmarty()->assign('formularz', $this->formularz);
      getSmarty()->display(getConf()->root_path.'/app/uzytkownik/edytuj/UzytkownikEdytuj.html');
    }
  }
