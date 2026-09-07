package com.supportme.pay.apigateway.organization;

import java.util.List;

/** {@code sections == null} == wszystko widoczne, mirror EnabledSections presence w proto. */
public record UpdateOrganizationSectionsDto(String userId, List<String> sections) {
}
