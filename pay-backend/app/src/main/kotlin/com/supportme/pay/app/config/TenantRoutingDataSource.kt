package com.supportme.pay.app.config

import com.supportme.pay.platform.tenant.TenantContext
import org.springframework.jdbc.datasource.lookup.AbstractRoutingDataSource

/**
 * Odpowiednik `ResolveTenant` na połączeniu domyślnym (`mysql` w Laravelu):
 * wybiera pulę połączeń na podstawie tenanta ustalonego przez
 * `TenantResolvingFilter` dla BIEŻĄCEGO wątku/żądania. W przeciwieństwie do
 * PHP (`config(...); app('db')->purge($conn)` — fizyczny reconnect na
 * request) każda fizyczna baza ma tu WŁASNY, stale utrzymywany pool (HikariCP)
 * — routing tylko wybiera który pool użyć, bez kosztu rozłączania/łączenia.
 */
class TenantRoutingDataSource : AbstractRoutingDataSource() {
    override fun determineCurrentLookupKey(): Any = TenantContext.current().db
}
