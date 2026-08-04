package com.supportme.pay.gateway.api.config

import com.supportme.pay.gateway.payments.MockProvider
import com.supportme.pay.gateway.payments.PayUOAuthTokenCache
import com.supportme.pay.gateway.payments.PayUProperties
import com.supportme.pay.gateway.payments.PayUProvider
import com.supportme.pay.gateway.payments.PaymentProvider
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration

/**
 * Odpowiednik `GatewayServiceProvider::registerPaymentProvider` (payu|mock wg
 * configu). `PayUConfigProperties`/`PaymentConfigProperties` rejestrowane
 * automatycznie przez `@ConfigurationPropertiesScan` na klasie głównej —
 * bez dodatkowego `@EnableConfigurationProperties` tutaj (uniknięcie
 * podwójnej rejestracji tego samego beana).
 */
@Configuration
class PaymentProviderConfig {

    @Bean
    fun paymentProvider(payUConfig: PayUConfigProperties, paymentConfig: PaymentConfigProperties): PaymentProvider =
        when (paymentConfig.provider) {
            "payu" -> PayUProvider(
                properties = PayUProperties(
                    env = payUConfig.env,
                    merchantId = payUConfig.merchantId,
                    posId = payUConfig.posId,
                    clientId = payUConfig.clientId,
                    clientSecret = payUConfig.clientSecret,
                    secondKey = payUConfig.secondKey,
                ),
                tokenCache = PayUOAuthTokenCache(),
            )
            // Mockpay to trasa RELATYWNA (`/mockpay/{uuid}`) — frontend/klient
            // rozwiązuje ją względem hosta bieżącego żądania, jak w oryginale.
            else -> MockProvider { transactionId -> "/mockpay/$transactionId" }
        }
}
