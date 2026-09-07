# Kontekst budowania MUSI być korzeniem repo (patrz ecosystem/docker-compose.yml,
# build.context: ..) — pom.xml odwołuje się do ../../proto tak samo jak lokalnie.
FROM maven:3.9-eclipse-temurin-21 AS build
WORKDIR /build
COPY proto proto
COPY services/org-svc services/org-svc
WORKDIR /build/services/org-svc
RUN mvn -q -DskipTests package

FROM eclipse-temurin:21-jre
WORKDIR /app
COPY --from=build /build/services/org-svc/target/org-svc-0.1.0-SNAPSHOT.jar app.jar
EXPOSE 8083 9092
ENTRYPOINT ["java", "-jar", "app.jar"]
