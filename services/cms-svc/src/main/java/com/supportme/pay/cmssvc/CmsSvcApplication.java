package com.supportme.pay.cmssvc;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;

/**
 * Backend domenowy "treść i katalog" — pierwszy krok w kierunku zniknięcia
 * PHP z projektu (patrz plan sesji). Przejmuje z Laravela: Organization,
 * BeneficiaryNode, JobPosition/JobApplication (kariery), ShopItem, Lead.
 */
@SpringBootApplication
public class CmsSvcApplication {

    public static void main(String[] args) {
        SpringApplication.run(CmsSvcApplication.class, args);
    }
}
