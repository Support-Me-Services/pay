package com.supportme.pay.contracttests

import com.fasterxml.jackson.databind.JsonNode
import com.fasterxml.jackson.databind.ObjectMapper
import com.fasterxml.jackson.databind.node.ObjectNode

/**
 * Porównanie JSON tolerancyjne na pola z natury zmienne (czas, losowe id) —
 * patrz [GoldenMasterFixture.ignoredFields]. Zwraca listę rozbieżności
 * (puste = zgodność); nie rzuca wyjątku samodzielnie, żeby wywołujący test
 * mógł zebrać WSZYSTKIE różnice naraz zamiast zatrzymywać się na pierwszej.
 */
object JsonComparator {

    fun diff(expected: JsonNode, actual: JsonNode, ignoredFields: Set<String>, path: String = "$"): List<String> {
        if (expected.isObject && actual.isObject) {
            val differences = mutableListOf<String>()
            val expectedFields = (expected as ObjectNode).fieldNames().asSequence().toSet()
            val actualFields = (actual as ObjectNode).fieldNames().asSequence().toSet()

            (expectedFields + actualFields).filterNot { it in ignoredFields }.sorted().forEach { field ->
                val childPath = "$path.$field"
                when {
                    field !in actualFields -> differences += "$childPath: brak w odpowiedzi (oczekiwano ${expected[field]})"
                    field !in expectedFields -> differences += "$childPath: nieoczekiwane dodatkowe pole (${actual[field]})"
                    else -> differences += diff(expected[field], actual[field], ignoredFields, childPath)
                }
            }
            return differences
        }

        if (expected.isArray && actual.isArray) {
            if (expected.size() != actual.size()) {
                return listOf("$path: różna długość tablicy (oczekiwano ${expected.size()}, jest ${actual.size()})")
            }
            return expected.indices().flatMap { i -> diff(expected[i], actual[i], ignoredFields, "$path[$i]") }
        }

        return if (expected == actual) emptyList() else listOf("$path: oczekiwano $expected, jest $actual")
    }

    private fun JsonNode.indices(): IntRange = 0 until size()
}

fun ObjectMapper.readTreeOrNull(text: String?): JsonNode? = text?.takeIf { it.isNotBlank() }?.let { readTree(it) }
