package com.supportme.pay.apigateway

import org.springframework.boot.autoconfigure.SpringBootApplication
import org.springframework.boot.runApplication

/**
 * Jedyny punkt wejścia REST dla web/mobile w docelowej architekturze.
 * Bez własnej bazy danych, bez logiki biznesowej — tłumaczy REST na gRPC
 * do serwisów domenowych (core-svc, docelowo gateway-svc).
 */
@SpringBootApplication
open class ApiGatewayApplication

fun main(args: Array<String>) {
    runApplication<ApiGatewayApplication>(*args)
}
