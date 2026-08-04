plugins {
    kotlin("plugin.spring")
}

dependencies {
    implementation(project(":platform-common"))
    implementation(project(":gateway-domain"))
    implementation(project(":gateway-payments"))
    // Panel Gateway loguje ten sam User co Storefront (jeden model, fizycznie
    // rozdzielony przez host->baza) — stąd zależność na storefront-domain
    // TYLKO dla User/UserRepository, nie dla encji biznesowych Storefrontu
    // (Product/ShopItem/...), których kod Gateway nigdy nie powinien dotykać.
    implementation(project(":storefront-domain"))
    implementation("org.springframework.boot:spring-boot-starter-web")
    implementation("org.springframework.boot:spring-boot-starter-security")
}

dependencyManagement {
    imports {
        mavenBom("org.springframework.boot:spring-boot-dependencies:3.3.5")
    }
}
