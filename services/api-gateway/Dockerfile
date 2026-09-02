# Kontekst budowania MUSI być korzeniem repo (patrz ecosystem/docker-compose.yml,
# build.context: ..) — pom.xml odwołuje się do ../../proto tak samo jak lokalnie.
FROM maven:3.9-eclipse-temurin-21 AS build
WORKDIR /build
COPY proto proto
COPY services/api-gateway services/api-gateway
WORKDIR /build/services/api-gateway
RUN mvn -q -DskipTests package

FROM eclipse-temurin:21-jre
WORKDIR /app
COPY --from=build /build/services/api-gateway/target/api-gateway-0.1.0-SNAPSHOT.jar app.jar
EXPOSE 8081
ENTRYPOINT ["java", "-jar", "app.jar"]
