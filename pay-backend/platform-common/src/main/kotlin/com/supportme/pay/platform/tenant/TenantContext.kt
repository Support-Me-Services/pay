package com.supportme.pay.platform.tenant

/**
 * ThreadLocal, odpowiednik `app()->instance('tenant', ...)` w Laravelu.
 * Ustawiany przez TenantResolvingFilter na początku żądania i MUSI być
 * czyszczony w `finally` — wątki Tomcata są poolowane, więc bez czyszczenia
 * tenant "przecieka" z poprzedniego żądania do następnego na tym samym wątku.
 */
object TenantContext {
    private val holder = ThreadLocal<TenantInfo>()

    fun set(tenant: TenantInfo) {
        holder.set(tenant)
    }

    fun current(): TenantInfo =
        holder.get() ?: error("TenantContext nie został ustawiony dla tego wątku — brakuje TenantResolvingFilter w łańcuchu?")

    fun currentOrNull(): TenantInfo? = holder.get()

    fun clear() {
        holder.remove()
    }
}
