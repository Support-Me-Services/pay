# Kontekst budowania MUSI być korzeniem repo (patrz ecosystem/docker-compose.yml,
# build.context: ..) — pom.xml odwołuje się do ../../proto tak samo jak lokalnie.
FROM maven:3.9-eclipse-temurin-21 AS build
WORKDIR /build
COPY proto proto
COPY services/cms-svc services/cms-svc
WORKDIR /build/services/cms-svc
RUN mvn -q -DskipTests package

FROM eclipse-temurin:21-jre
WORKDIR /app
COPY --from=build /build/services/cms-svc/target/cms-svc-0.1.0-SNAPSHOT.jar app.jar
EXPOSE 8083 9093
ENTRYPOINT ["java", "-jar", "app.jar"]
