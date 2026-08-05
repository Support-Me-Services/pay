package com.supportme.pay.contracttests

/**
 * Format nagrania golden-master — JEDEN plik JSON = jedno żądanie/odpowiedź
 * nagrane z ŻYWEGO Laravela (stage lub sandbox PayU), traktowane jako
 * "źródło prawdy" do porównania z nowym backendem Kotlin.
 *
 * `ignoredFields` — nazwy pól (proste, nie pełny JSONPath) pomijane przy
 * porównaniu, bo z natury się różnią między przebiegami (np. `id`, `uuid`,
 * `createdAt`, `paidAt`, wygenerowany `apiKey`) — porównujemy WSZYSTKO
 * poza nimi, żeby realnie łapać rozjazdy zachowania, a nie tylko strukturę.
 */
data class FixtureRequest(
    val method: String,
    val path: String,
    val headers: Map<String, String> = emptyMap(),
    val body: String? = null,
)

data class FixtureExpectedResponse(
    val status: Int,
    val body: String? = null,
)

data class GoldenMasterFixture(
    val description: String,
    val request: FixtureRequest,
    val expectedResponse: FixtureExpectedResponse,
    val ignoredFields: List<String> = emptyList(),
)
