dependencies {
    implementation("org.springframework.boot:spring-boot")
    implementation("org.springframework:spring-context")
    implementation("org.springframework:spring-web")
    implementation("jakarta.servlet:jakarta.servlet-api:6.0.0")
    implementation("com.github.ben-manes.caffeine:caffeine:3.1.8")
}

dependencyManagement {
    imports {
        mavenBom("org.springframework.boot:spring-boot-dependencies:3.3.5")
    }
}
