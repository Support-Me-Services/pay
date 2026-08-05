package com.supportme.pay.storefront.api.storage

import org.springframework.boot.context.properties.ConfigurationProperties
import org.springframework.stereotype.Service
import org.springframework.web.multipart.MultipartFile
import java.nio.file.Files
import java.nio.file.Path
import java.util.UUID

/** Odpowiednik `config('filesystems.disks.local'/'public')` — zostaje lokalny dysk (decyzja z planu, nie S3 teraz). */
@ConfigurationProperties(prefix = "storage")
data class StorageProperties(
    val privateRoot: String = "storage/private",
    val publicRoot: String = "storage/public",
)

/**
 * Odpowiednik dwóch dysków Laravela: `local` (PRYWATNY — CV rekrutacyjne,
 * nigdy web-dostępne) i `public` (obrazy produktów/kategorii/beneficjentów,
 * serwowane pod `/storage/...`).
 */
@Service
class FileStorageService(private val properties: StorageProperties) {

    /** Zwraca ścieżkę względną (zapisywaną w DB) — NIGDY absolutną, jak w oryginale. */
    fun storePrivate(file: MultipartFile, subdirectory: String): String = store(Path.of(properties.privateRoot), file, subdirectory)

    fun storePublic(file: MultipartFile, subdirectory: String): String = store(Path.of(properties.publicRoot), file, subdirectory)

    fun deletePublic(relativePath: String) {
        runCatching { Files.deleteIfExists(Path.of(properties.publicRoot, relativePath)) }
    }

    fun readPrivate(relativePath: String): ByteArray = Files.readAllBytes(Path.of(properties.privateRoot, relativePath))

    fun deletePrivate(relativePath: String) {
        runCatching { Files.deleteIfExists(Path.of(properties.privateRoot, relativePath)) }
    }

    private fun store(root: Path, file: MultipartFile, subdirectory: String): String {
        val dir = root.resolve(subdirectory)
        Files.createDirectories(dir)
        val extension = file.originalFilename?.substringAfterLast('.', "")?.takeIf { it.isNotBlank() }
        val filename = UUID.randomUUID().toString() + (extension?.let { ".$it" } ?: "")
        Files.copy(file.inputStream, dir.resolve(filename))
        return "$subdirectory/$filename"
    }
}
