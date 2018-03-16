<?php
  class PostListaCtrl {

    /* zmienna do której pakowana jest lista postów z bazy danych */
    private $posty;

    /* funkcja pobierająca listę postów z tematu o id przekazanym w argumencie z bazy danych i przekazująca
       ją do smarty, nie generuje widoku jest wywoływana przez inny obiekt  klasy PokazTematCtrl*/
    public function przygotujListePostow($id_tematu){
      // 1. pobierz posty z bazy danych
      /* select([tabela], [join], [kolumny], [warunek]) pobiera wszystkie wpisy z tabeli
         post, następnie dąłącza do nich (join) wpisy z tabeli uzytkownik. Jest to tzw. left join [>]
         czyli do każdego wpisu z tabeli post, dobierany jest taki wpis z tabeli użytkownicy który spełnia
         warunek [post.id_uzytkownika == uzytkownik.id_uzytkownika] taki zapis (czyli id_uzytkownika w obu
         tabelach) można skrócić do samego 'id_uzytkownika'. gwiazdka oznacza że pobrane mają zostać wszystkie
         kolumny a ostatni nawias zawiera dwa warunki. post.id_tematu musi odpowiadać id tematu przekazanemu
         jako parametr funkcji i czas utworzenia musi być większy od 0 (a taki występuje tylko gdy post
         został usunięty). Jak się robi JOIN to później do kolumn odwołujesz się poprzedzając ją nazwą
         tabeli której dotyczy.*/
      $this->posty = getDB()->select('post', [
        '[>]uzytkownik' => 'id_uzytkownika'
      ], '*', [
        'post.id_tematu' => $id_tematu,
        'post.data_czas[!]' => 0
      ]);

      // 2. przekazanie do smarty listy postów
      /* assign([zmienna smarty], [zmienna php])*/
      getSmarty()->assign('posty', $this->posty);
    }
  }
