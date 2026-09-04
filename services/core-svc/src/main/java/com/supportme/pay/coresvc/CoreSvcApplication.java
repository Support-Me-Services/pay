package com.supportme.pay.coresvc;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;

/**
 * Nowy backend domenowy — startuje celowo pusty (bez encji, bez tabel).
 * Miejsce na funkcje budowane od teraz; pierwsza domena jeszcze nie ustalona,
 * patrz dokument architektury ekosystemu pay, sekcja "Ryzyka i otwarte pytania".
 */
@SpringBootApplication
public class CoreSvcApplication {

    public static void main(String[] args) {
        SpringApplication.run(CoreSvcApplication.class, args);
    }
}
