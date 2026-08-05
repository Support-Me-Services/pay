package com.supportme.pay.gateway.api.panel.lead

import com.supportme.pay.gateway.domain.repository.LeadRepository
import org.springframework.data.domain.PageRequest
import org.springframework.data.domain.Sort
import org.springframework.http.HttpHeaders
import org.springframework.http.MediaType
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RequestParam
import org.springframework.web.bind.annotation.RestController
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter

data class LeadSummary(val id: Long, val date: String, val name: String, val email: String, val phone: String, val company: String?, val message: String)
data class LeadPage(val items: List<LeadSummary>, val page: Int, val totalPages: Int, val totalElements: Long)

/** Odpowiednik `Panel\LeadController` — paginacja 50/stronę + eksport CSV. */
@RestController
@RequestMapping("/api/gateway/panel/leads")
class LeadController(private val leadRepository: LeadRepository) {

    @GetMapping
    fun index(@RequestParam(defaultValue = "0") page: Int): LeadPage {
        val result = leadRepository.findAllByOrderByCreatedAtDesc(PageRequest.of(page, 50, Sort.by("createdAt").descending()))
        return LeadPage(
            items = result.content.map { summarize(it.id!!, it.createdAt, it.name, it.email, it.phone, it.company, it.message) },
            page = page,
            totalPages = result.totalPages,
            totalElements = result.totalElements,
        )
    }

    /** UTF-8 BOM + `;` — jak `LeadController::exportCsv` (Excel-friendly), chunk 200. */
    @GetMapping("/export")
    fun exportCsv(): ResponseEntity<ByteArray> {
        val builder = StringBuilder()
        builder.append('﻿') // BOM — Excel rozpoznaje UTF-8 z polskimi znakami
        builder.append("Data;Imię i nazwisko;E-mail;Telefon;Firma;Wiadomość\r\n")

        var page = 0
        while (true) {
            val chunk = leadRepository.findAllByOrderByCreatedAtDesc(PageRequest.of(page, 200, Sort.by("createdAt").descending()))
            chunk.content.forEach { lead ->
                builder.append(csvField(formatDate(lead.createdAt))).append(';')
                    .append(csvField(lead.name)).append(';')
                    .append(csvField(lead.email)).append(';')
                    .append(csvField(lead.phone)).append(';')
                    .append(csvField(lead.company ?: "")).append(';')
                    .append(csvField(lead.message)).append("\r\n")
            }
            if (chunk.isLast) break
            page++
        }

        val filename = "leady_${System.currentTimeMillis() / 1000}.csv"
        return ResponseEntity.ok()
            .contentType(MediaType.parseMediaType("text/csv;charset=UTF-8"))
            .header(HttpHeaders.CONTENT_DISPOSITION, "attachment; filename=\"$filename\"")
            .body(builder.toString().toByteArray(Charsets.UTF_8))
    }

    private fun csvField(value: String): String =
        if (value.contains(';') || value.contains('"') || value.contains('\n')) {
            "\"${value.replace("\"", "\"\"")}\""
        } else {
            value
        }

    private fun formatDate(instant: java.time.Instant?): String =
        instant?.atZone(ZoneOffset.UTC)?.format(DateTimeFormatter.ofPattern("dd.MM.yyyy HH:mm")) ?: ""

    private fun summarize(id: Long, createdAt: java.time.Instant?, name: String, email: String, phone: String, company: String?, message: String) =
        LeadSummary(id, formatDate(createdAt), name, email, phone, company, message)
}
