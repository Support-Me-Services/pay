package com.supportme.pay.coresvc

import org.springframework.boot.autoconfigure.SpringBootApplication
import org.springframework.boot.runApplication

/**
 * Nowy backend domenowy — startuje celowo pusty (bez encji, bez tabel).
 * Miejsce na funkcje budowane od teraz; pierwsza domena jeszcze nie ustalona,
 * patrz dokument architektury ekosystemu pay, sekcja "Ryzyka i otwarte pytania".
 */
@SpringBootApplication
open class CoreSvcApplication

fun main(args: Array<String>) {
    runApplication<CoreSvcApplication>(*args)
}
