# Fashion App Core API (Laravel 10)
### A secure, decoupled RESTful API built with Laravel 10 to power an external Flutter mobile application, featuring OAuth2 social authentication and token-based state management.

---

## 📐 Architecture & System Overview
This repository serves as the backend engine for a fashion discovery and look-generation mobile ecosystem. 

Designed under a strict API-first architecture, the system completely separates backend business logic from the presentation layer (Flutter client). It provides a secure environment for mobile clients to perform user lifecycle management, session authentication, and transactional data exchanges.

---

## 🚀 Technical Highlights & Security Implementation

- **Decoupled Mobile Authentication:** Engineered a lightweight, token-based authentication pipeline using **Laravel Sanctum**. This ensures secure API communication and request signing for all mobile client instances.
- **Social OAuth2 Integration:** Integrated native Google Authentication workflows utilizing the official `google/apiclient` SDK, validating server-side exchange tokens to enable secure, one-click social login operations.
- **Strict Privacy Lifecycle Compliance (GDPR/Data Privacy):** Programmed automated user data deletion workflows ("Right to be Forgotten") to safely scrub profile metadata and associated storage assets upon account termination.
- **Reliable Client Integration Patterns:** Constructed a standard REST interface for account creation, metadata updates, and profile lifecycle operations, utilizing strong payload validation to prevent client-induced errors.
- **Test-Driven Foundations:** Guarded the API routing and authentication gates with automated integration tests via **PHPUnit**, ensuring stability during early-stage development and iterative client contract changes.

---

## 🛠️ API Architecture & Key Dependencies

- **Core Framework & Environment:** PHP (^8.1) & Laravel Framework (^10.10)
- **Authentication Layers:** Laravel Sanctum (Stateful Mobile API Tokens) & Google API Client SDK (OAuth2)
- **Network & HTTP Layers:** Guzzle HTTP client for external service orchestration
- **Testing Architecture:** Automated Feature and Unit Testing via PHPUnit
