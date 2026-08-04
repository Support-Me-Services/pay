plugins {
    kotlin("plugin.spring")
}

dependencies {
    implementation(project(":platform-common"))
    implementation(project(":gateway-domain"))
    implementation(project(":gateway-payments"))
    implementation("org.springframework.boot:spring-boot-starter-web")
    implementation("org.springframework.boot:spring-boot-starter-security")
}

dependencyManagement {
    imports {
        mavenBom("org.springframework.boot:spring-boot-dependencies:3.3.5")
    }
}
