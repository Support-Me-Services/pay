package com.supportme.pay.app.config

import com.fasterxml.jackson.core.JsonProcessingException
import org.slf4j.LoggerFactory
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.http.converter.HttpMessageNotReadableException
import org.springframework.web.bind.MethodArgumentNotValidException
import org.springframework.web.bind.MissingServletRequestParameterException
import org.springframework.web.bind.annotation.ExceptionHandler
import org.springframework.web.bind.annotation.RestControllerAdvice
import org.springframework.web.multipart.MaxUploadSizeExceededException
import org.springframework.web.multipart.support.MissingServletRequestPartException
import org.springframework.web.servlet.resource.NoResourceFoundException
import jakarta.validation.ConstraintViolationException
import java.util.NoSuchElementException

/**
 * Siatka bezpieczeństwa dla WYJĄTKÓW NIEOBSŁUŻONYCH jawnie w kontrolerach —
 * większość endpointów buduje WŁASNE, precyzyjne odpowiedzi błędów (dokładnie
 * dopasowane do oryginalnych komunikatów PHP, np. "Transakcja nie istnieje"),
 * ten handler NIE ma ich nadpisywać, tylko łapać to, co przeciekło (walidacja
 * @Valid, `.orElseThrow()`, nieoczekiwane wyjątki) — spójny kształt
 * `{"error": "..."}` zamiast białej strony/stack trace'u Spring Boota.
 */
@RestControllerAdvice
class GlobalExceptionHandler {
    private val log = LoggerFactory.getLogger(GlobalExceptionHandler::class.java)

    @ExceptionHandler(MethodArgumentNotValidException::class)
    fun handleValidation(ex: MethodArgumentNotValidException): ResponseEntity<Map<String, Any>> {
        val fieldErrors = ex.bindingResult.fieldErrors.associate { it.field to (it.defaultMessage ?: "Nieprawidłowa wartość") }
        return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Błąd walidacji", "fields" to fieldErrors))
    }

    /** Ręczna walidacja poza `@Valid` (np. gdy trzeba sprawdzić coś PRZED walidacją, jak honeypot). */
    @ExceptionHandler(ConstraintViolationException::class)
    fun handleConstraintViolation(ex: ConstraintViolationException): ResponseEntity<Map<String, Any>> {
        val fieldErrors = ex.constraintViolations.associate { it.propertyPath.toString() to (it.message ?: "Nieprawidłowa wartość") }
        return ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to "Błąd walidacji", "fields" to fieldErrors))
    }

    @ExceptionHandler(NoSuchElementException::class)
    fun handleNotFound(ex: NoSuchElementException): ResponseEntity<Map<String, String>> =
        ResponseEntity.status(HttpStatus.NOT_FOUND).body(mapOf("error" to "Nie znaleziono zasobu"))

    /**
     * Żaden kontroler/statyczny zasób nie dopasował ścieżki (np. usunięta trasa
     * jak `/kategoria/{slug}`) — Spring domyślnie mapuje to na 404, MUSI mieć
     * jawny handler tutaj z tego samego powodu co `handleBadRequest`.
     */
    @ExceptionHandler(NoResourceFoundException::class)
    fun handleNoResource(ex: NoResourceFoundException): ResponseEntity<Map<String, String>> =
        ResponseEntity.status(HttpStatus.NOT_FOUND).body(mapOf("error" to "Nie znaleziono"))

    /**
     * JSON niepoprawny/niekompletny (w tym brakujące pole non-nullable Kotlin
     * data class — Jackson rzuca to PRZED walidacją @Valid, więc nie trafia
     * do `handleValidation`). Spring domyślnie mapuje to na 400 — MUSI mieć
     * jawny handler tutaj, inaczej złapałby to zbyt szeroki `handleUnexpected`
     * i zwrócił błędne 500 (realny bug złapany przy weryfikacji).
     * `JsonProcessingException` dodane dla kontrolerów, które ręcznie
     * parsują body PRZED walidacją (np. `LandingController` — honeypot musi
     * być sprawdzony przed deserializacją do docelowego DTO).
     */
    @ExceptionHandler(HttpMessageNotReadableException::class, MissingServletRequestParameterException::class, MissingServletRequestPartException::class, JsonProcessingException::class)
    fun handleBadRequest(ex: Exception): ResponseEntity<Map<String, String>> =
        ResponseEntity.status(HttpStatus.BAD_REQUEST).body(mapOf("error" to "Nieprawidłowe żądanie."))

    @ExceptionHandler(MaxUploadSizeExceededException::class)
    fun handlePayloadTooLarge(ex: MaxUploadSizeExceededException): ResponseEntity<Map<String, String>> =
        ResponseEntity.status(HttpStatus.PAYLOAD_TOO_LARGE).body(mapOf("error" to "Plik jest za duży."))

    @ExceptionHandler(Exception::class)
    fun handleUnexpected(ex: Exception): ResponseEntity<Map<String, String>> {
        log.error("Nieobsłużony wyjątek", ex)
        return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR).body(mapOf("error" to "Wystąpił nieoczekiwany błąd."))
    }
}
