plugins {
    kotlin("plugin.jpa")
}

dependencies {
    api("org.springframework.boot:spring-boot-starter-data-jpa")
    implementation("com.fasterxml.jackson.core:jackson-databind")
    // spring-security-web dla SecurityContextRepository (PanelAuthService) —
    // ten moduł nie zna Spring MVC/kontrolerów, ale PanelAuthService i tak
    // musi zapisywać SecurityContext do sesji HTTP przy logowaniu JSON.
    implementation("org.springframework.security:spring-security-core")
    implementation("org.springframework.security:spring-security-web")
    implementation("jakarta.servlet:jakarta.servlet-api:6.0.0")
}

dependencyManagement {
    imports {
        mavenBom("org.springframework.boot:spring-boot-dependencies:3.3.5")
    }
}
