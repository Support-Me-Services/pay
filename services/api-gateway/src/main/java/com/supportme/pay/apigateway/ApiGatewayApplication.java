package com.supportme.pay.apigateway;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;

/**
 * Jedyny punkt wejścia REST dla web/mobile w docelowej architekturze.
 * Bez własnej bazy danych, bez logiki biznesowej — tłumaczy REST na gRPC
 * do serwisów domenowych (core-svc, docelowo gateway-svc).
 */
@SpringBootApplication
public class ApiGatewayApplication {

    public static void main(String[] args) {
        SpringApplication.run(ApiGatewayApplication.class, args);
    }
}
