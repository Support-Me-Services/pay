package com.supportme.pay.storefront.domain.auth

/** Współdzielone kształty żądań obu paneli (Gateway/Storefront) — identyczne pola. */
data class LoginRequest(val email: String, val password: String)

data class ChangePasswordRequest(val currentPassword: String, val newPassword: String, val newPasswordConfirmation: String)
