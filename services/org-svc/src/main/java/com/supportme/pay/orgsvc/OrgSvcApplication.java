package com.supportme.pay.orgsvc;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;

/**
 * Backend domenowy dla organizacji — pierwsza domena przeniesiona z
 * Laravela do tego ekosystemu (patrz README.md tego serwisu).
 */
@SpringBootApplication
public class OrgSvcApplication {

    public static void main(String[] args) {
        SpringApplication.run(OrgSvcApplication.class, args);
    }
}
