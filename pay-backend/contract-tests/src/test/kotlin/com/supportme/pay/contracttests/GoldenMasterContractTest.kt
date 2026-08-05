package com.supportme.pay.contracttests

import com.fasterxml.jackson.databind.ObjectMapper
import com.fasterxml.jackson.module.kotlin.registerKotlinModule
import org.junit.jupiter.api.Assumptions
import org.junit.jupiter.api.DynamicTest
import org.junit.jupiter.api.TestFactory
import org.springframework.http.HttpHeaders
import org.springframework.http.HttpMethod
import org.springframework.http.MediaType
import org.springframework.web.client.RestClient
import org.springframework.web.client.RestClientResponseException
import java.io.File
import kotlin.test.assertTrue

/**
 * Uruchamia KAŻDE nagranie golden-master (pliki .json w `src/test/resources/fixtures`)
 * przeciw działającej instancji backendu (`TARGET_BASE_URL`, domyślnie
 * `http://localhost:8080` — uruchom `./gradlew :app:bootRun` osobno przed
 * `./gradlew :contract-tests:test`, tak jak w weryfikacjach ręcznych z Faz 0-5).
 *
 * DZIŚ katalog `fixtures/` zawiera TYLKO `_example.json` — samo-test formatu,
 * NIE prawdziwy zapis z Laravela. Żeby domknąć Fazę 6 naprawdę: nagraj
 * request/response z ŻYWEGO stage/PayU sandbox (WireMock w trybie record
 * albo ręczny proxy-log — patrz plan migracji, sekcja "Weryfikacja") i dodaj
 * pliki w tym samym formacie ([GoldenMasterFixture]) do tego katalogu —
 * ten harness podejmie je automatycznie, bez zmian w kodzie testu.
 */
class GoldenMasterContractTest {

    private val objectMapper = ObjectMapper().registerKotlinModule()
    private val targetBaseUrl = System.getenv("TARGET_BASE_URL") ?: "http://localhost:8080"
    private val restClient = RestClient.create(targetBaseUrl)

    @TestFactory
    fun goldenMasterFixtures(): List<DynamicTest> {
        val fixturesDir = fixturesDirectory()
        Assumptions.assumeTrue(fixturesDir.exists(), "Brak katalogu fixtures/ — pomiń (nic do uruchomienia)")

        val files = fixturesDir.listFiles { f -> f.extension == "json" }?.sortedBy { it.name } ?: emptyList()
        Assumptions.assumeTrue(files.isNotEmpty(), "Brak nagrań golden-master — dodaj pliki .json do contract-tests/src/test/resources/fixtures/")

        return files.map { file ->
            val fixture = objectMapper.readValue(file, GoldenMasterFixture::class.java)
            DynamicTest.dynamicTest("${file.name}: ${fixture.description}") { runFixture(fixture) }
        }
    }

    private fun runFixture(fixture: GoldenMasterFixture) {
        val headers = HttpHeaders().apply { fixture.request.headers.forEach { (k, v) -> add(k, v) } }

        val responseText: String?
        val actualStatus: Int
        try {
            val spec = restClient.method(HttpMethod.valueOf(fixture.request.method))
                .uri(fixture.request.path)
                .headers { it.addAll(headers) }

            val response = if (fixture.request.body != null) {
                spec.contentType(MediaType.APPLICATION_JSON).body(fixture.request.body).retrieve()
            } else {
                spec.retrieve()
            }

            responseText = response.body(String::class.java)
            actualStatus = 200 // .retrieve() rzuciłby wyjątek dla 4xx/5xx — patrz catch niżej
        } catch (e: RestClientResponseException) {
            assertTrue(e.statusCode.value() == fixture.expectedResponse.status, "Status: oczekiwano ${fixture.expectedResponse.status}, jest ${e.statusCode.value()} (${fixture.description})")
            compareBodies(fixture, e.responseBodyAsString)
            return
        }

        assertTrue(actualStatus == fixture.expectedResponse.status, "Status: oczekiwano ${fixture.expectedResponse.status}, jest $actualStatus (${fixture.description})")
        compareBodies(fixture, responseText)
    }

    private fun compareBodies(fixture: GoldenMasterFixture, actualBody: String?) {
        val expectedJson = objectMapper.readTreeOrNull(fixture.expectedResponse.body) ?: return
        val actualJson = objectMapper.readTreeOrNull(actualBody)
            ?: throw AssertionError("Brak body w odpowiedzi, oczekiwano JSON (${fixture.description})")

        val differences = JsonComparator.diff(expectedJson, actualJson, fixture.ignoredFields.toSet())
        assertTrue(differences.isEmpty(), "Rozbieżności dla \"${fixture.description}\":\n${differences.joinToString("\n")}")
    }

    private fun fixturesDirectory(): File {
        val url = Thread.currentThread().contextClassLoader.getResource("fixtures")
        return url?.let { File(it.toURI()) } ?: File("src/test/resources/fixtures")
    }
}
