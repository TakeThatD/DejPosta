<?php
  require_once "PostFormularz.class.php";

  class PostEdycjaCtrl {

    /*zmienna przechowująca dane z formularza*/
    private $formularz;

    /* konstruktor, inicjacja zmiennej formularza (stworzenie obiektu klasy PostFormularz)*/
    public function __construct(){
      $this->formularz = new PostFormularz();
    }

    /* walidacja przed dokonaniem zapisu w bazie, sprawdza czy wszystkie pola formularza zostały
       przekazane (nawet jeżeli jest puste pole to wartość jest '' a nie null), następnie czy
       wartości przekazane nie są puste (mają wartość '' - nieuzupełnione pole)*/
    public function walidacjaZapis(){
      // 1. sprawdzenie czy wszystkie parametry zostały przekazane
      /* getFormRequest([nazwa pola formularza], [true/false - zmienna wymagana?], [komunikat błędu])
         pobiera dane z zapytania http umieszczone w formularzu, nazwa pola formularza to nazwa (name=)
         z formmularza w pliku html. Jeżeli nie zostało wypełnione przypisana zostanie wartość '', natomiast
         jeżeli akcja zapisu została wywołana z paska adresu (nie przez formularz) to przypisaną wartością
         będzie null - w tym wypadku przy ustawionej wartości false w dugim argumencie zostanie dodana
         wiadomość błędu podana w 3*/
      $this->formularz->id_postu = getFromRequest('id_postu', true, "Błędne wywołanie aplikacji");
      $this->formularz->id_tematu = getFromRequest('id_tematu', true, "Błędne wywołanie aplikacji");
      $this->formularz->tresc = getFromRequest('tresc', true, "Błędne wywołanie aplikacji");

      /* sprawdzenie czy podczas przypisania zostały dodane jakieś wiadomości z błędem
         jeżeli tak to walidacja niepoprawna -> zwróć fałsz
         getMessages()->isError() - zwraca true jeżeli w pamięci są jakieś wiadomości z błędami*/
      if (getMessages()->isError()) { return false; } //jeżeli błąd to koniec

      // 2. sprawdzenie czy przekazane wartości nie są puste
      /* sprawdza czy dane pole w formularzu nie zostało wysłane niewypełnione, jednak tylko w przypadku,
         gdy jest ono wymagane do poprawnego zapisania. W tym przypadku konieczne jest posiadania treści
         posta i id tematu do którego został dodany. id użytkownika pobierane jest z sesji, id postu nadaje
         baza danych (auto increment) a date generuje baza danych
         empty() - zwraca true jeżeli funckja jest pusta
         trim() - usuwa wszystkie białe znaki z początka i końca ciągu*/
      if (empty(trim($this->formularz->tresc))) {
        getMessages()->addMessage(new Message("Wpisz treść posta", Message::ERROR));
      }

      /* zwraca zanegowaną wartość getMessages()->isError() tzn. jeżeli nie wystąpił żaden błąd to
         isError() zwraca true, a w tym przypadku (brak żadnych błędów) funkcja walidująca powinna
         zwrócić prawdę (dane poprawne) dlatego wstawiona jest negacja (!)*/
      return !getMessages()->isError();
    }

    /* walidacja przed usunięciem z bazy, sprawdzany jest fakt przekazania wszystkich niezbędnych wartości
       czyli w tym wypadku id_postu i id_tematu poprzez formularz. później zwraca czy są błędy, wszystko
       opisane wyżej*/
    public function walidacjaUsun(){
      // 1. sprawdzenie czy parametry zostały przekazane
      $this->formularz->id_postu = getFromRequest('id_postu', true, "Błędne wywołanie aplikacji");
      $this->formularz->id_tematu = getFromRequest('id_tematu', true, "Błędne wywołanie aplikacji");

      return !getMessages()->isError();
    }

    /* funkcja zapisująca dane z formularza do bazy danych*/
    public function zapiszPost() {
      // 1. Sprawdzenie poprawności przekazanych danych
      /* wywołanie funkcji walidującej - patrz wyżej - jeżeli dane poprawne (true) można przystąpić do
         zapisu danych w bazie danych */
      if ($this->walidacjaZapis()) {
        // 2.1 jeżeli nie podano id_postu (nowy post)
        /* jeżeli funkcja została wywołana z formularza do którego wcześniej nie zostały przekazane żadne
           dane, to id_postu ma wartość '' ponieważ pole formularza z id jest ukryte i nie można go zmienić
           jeżeli wartość id_postu ma jakąś wartość to wywołanie formularza nastąpiło z przekazaniem id_postu
           (_URL_?zapiszPost&id_postu=XX) i w tym wypadku wiemy że jest to edycja a nie dodanie nowego. */
        if ($this->formularz->id_postu == ""){
          // 2.1.1 zapisz dane do bazy danych
          /* insert([nazwa tabeli], [[kolumna] => [wartość], ...], [warunek])
             dodaje nowy wpis do bazy danych w tabeli post, id_postu dodaje baza danych, id_tematu brane z formmularza
             id_uzytkownika z sessji, treść z formularza, data ustawiana na aktualną przez baze.
             getFromSession(..) składnia taka sama jak getFromRequest() z tą różnicą że zamiast z zapytania pobiera
             ze zmiennej sesyjnej.*/
          getDB()->insert('post', [
            'id_tematu' => $this->formularz->id_tematu,
            'id_uzytkownika' => getFromSession("_user_id"),
            'tresc' => $this->formularz->tresc
          ]);
        // 2.2 jeżeli podano id_postu
        } else {
          // 2.2.1 zaktualizuj wpis w bazie danych
          /* update([nazwa tabeli], [[kolumna] => [dane], ..], warunek)
             aktualizuje w tabeli post, kolumnę treść wartością ze zmiennej $this->formularz->tresc pod warunkiem
             że w kolumnie id_postu znajduje się wartość ze zmiennej $this->formularz->id_postu (czyli przekazana
             z formularza i przypisana do zmiennej $this->formularz->id_postu w funkcji walidacjaZapis())*/
          getDB()->update('post', [
            'tresc' => $this->formularz->tresc
          ], [
            'id_postu' => $this->formularz->id_postu
          ]);
        }

        // 3. sprawdź czy wystąpiły błędy podczas obsługi bazy danych
        /* getDB()->error() zwraca tablicę z błędami [0] odwołuje się do pierwszego błędu i jeżeli istnieje
           (jest różne od 0) to dodaje wiadomość błędu -> getMessages()->addMessage(..)
           drugi if w środku sprawdza czy jest włączone debugowanie (w pliku config.php) jeżeli tak to pobiera
           również błąd z bazy danych i dldaje go jako wiadomość błędu*/
        if (getDB()->error()[0]!=0){ //jeśli istnieje kod błędu
          getMessages()->addMessage(new Message('Wystąpił błąd podczas zapisu rekordów',Message::ERROR));
          if (getConf()->debug) getMessages()->addMessage(new Message(var_export(getDB()->error(), true),Message::ERROR));
        }
      }

      // 4. zapisanie wiadomości do sesji
      /* wiadomości błędu przechowywane są w obiekcie getMessages() który przy przekierowaniu za pomocą funkcji redirectTo()
         zostaje zniszczony, aby wiadomości nie zostały utracone należy je umieścić w zmiennej sesji, dokonuje tego właśnie funkcja
         storeMessages(), aby wczytać wiadmości na innej stronie należy je załadować z pliku sesji do obiektu getMessages() przy
         użyci funkcji loadMessages(). Obiekt getMessages() tworzony jest w pliku init.php*/
      storeMessages();

      // 5. przekierowanie na stronę tematu przekazanego w formularzu (w którym post został dodany/zmieniony)
      /* redirect to wysyła nowe zapytanie do serwera o podaną stronę przy czym jest to przekierowanie na daną akcję w pliku
         ctrl.php czyli redirectTo() dokleja do podanej akcji (tu. pokazTemat z doklejonym id_tematu z formularza)
         adres url pliku ctrl.php (całe przekierowanie wygląda tak: localhost/DejPosta/ctrl.php?a=pokazTemat&id_tematu=X).
         wartości podane po ? i pomiędzy & są to argumenty dołączane do zapytania na zasadzie ...&nazwa=wartość&...
         i można je odczytać funkcją getFromRequest().*/
      redirectTo('pokazTemat&id_tematu='.$this->formularz->id_tematu);
    }

    /* funkcja usuwająca post  - usunięcie oznacza zmianę daty na tzw. początek ery według uniksa 01.03.1970r co oznacza
       wartość timestamp = 0 w bazie danych. Przy odczycie postów warunkiem idącym do bazy jest data różna od 0 (czyli
       od ten daty)*/
    public function usunPost() {
      // 1. sprawdzenie poprawności danych
      /* wywołuje funkcję walidującą opisaną wyżej */
      if ($this->walidacjaUsun()) {
        // 2. jeżeli dane poprawne to zamień datę utworzenia postu na 0
        /* update() opisane przy funkcji zapiszPost() */
        getDB()->update('post', [
          "data_czas" => 0
        ], [
          'id_postu' => $this->formularz->id_postu
        ]);

        /* obsługa błędów bazy opisana w funkcji zapiszPost() */
        if (getDB()->error()[0]!=0){ //jeśli istnieje kod błędu
          getMessages()->addMessage(new Message('Wystąpił błąd podczas zapisu rekordów',Message::ERROR));
          if (getConf()->debug) getMessages()->addMessage(new Message(var_export(getDB()->error(), true),Message::ERROR));
        }

        // 3. zapisanie wiadomości do sesji
        /* opisane wyżel */
        storeMessages();
        // 4. przekierowanie na stronę tematu
        /* opisane wyżej */
        redirectTo('pokazTemat&id_tematu='.$this->formularz->id_tematu);
      }
    }
  }
