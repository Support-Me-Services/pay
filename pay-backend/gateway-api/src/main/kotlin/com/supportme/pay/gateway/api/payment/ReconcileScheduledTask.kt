package com.supportme.pay.gateway.api.payment

import com.supportme.pay.gateway.domain.entity.TransactionStatus
import com.supportme.pay.gateway.domain.repository.TransactionRepository
import org.slf4j.LoggerFactory
import org.springframework.scheduling.annotation.Scheduled
import org.springframework.stereotype.Component
import java.time.Instant
import java.time.temporal.ChronoUnit

/**
 * Odpowiednik komendy `payu:reconcile` (`Schedule::command('payu:reconcile')->everyMinute()`)
 * — kandydaci `pending`, najstarsze pierwsze, max 50/przebieg, do 11 dni
 * wstecz (po tym czasie transakcja i tak jest praktycznie martwa).
 * Operuje bezpośrednio na `TransactionRepository` (zawsze `nfc_pay`) —
 * BEZ zależności od tenanta bieżącego "żądania" (nie ma go, to scheduler).
 */
@Component
class ReconcileScheduledTask(
    private val transactionRepository: TransactionRepository,
    private val transactionService: TransactionService,
) {
    private val log = LoggerFactory.getLogger(ReconcileScheduledTask::class.java)

    // initialDelay: bez niego pierwsze uruchomienie startuje niemal natychmiast
    // po odświeżeniu kontekstu, wyścigowo z migracjami Flyway (które lecą jako
    // ApplicationRunner PO starcie — realny bug znaleziony przy weryfikacji:
    // "relation transactions does not exist" przy starcie na pustej bazie).
    @Scheduled(initialDelay = 60_000, fixedDelay = 60_000)
    fun reconcilePending() {
        val since = Instant.now().minus(MAX_AGE_DAYS, ChronoUnit.DAYS)
        val candidates = transactionRepository
            .findByStatusAndCreatedAtAfterOrderByCreatedAtAsc(TransactionStatus.PENDING, since)
            .take(MAX_PER_RUN)

        candidates.forEach { transaction ->
            try {
                transactionService.reconcileWithProvider(transaction)
            } catch (e: Exception) {
                log.warn("payu:reconcile — błąd dla transakcji {}: {}", transaction.id, e.message)
            }
        }
    }

    companion object {
        private const val MAX_AGE_DAYS = 11L
        private const val MAX_PER_RUN = 50
    }
}
