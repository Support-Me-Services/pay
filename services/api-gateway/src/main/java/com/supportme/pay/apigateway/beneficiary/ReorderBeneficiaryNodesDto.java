package com.supportme.pay.apigateway.beneficiary;

import java.util.List;

public record ReorderBeneficiaryNodesDto(long organizationId, List<Long> orderedIds) {
}
