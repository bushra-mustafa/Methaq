# Infrastructure boundary

This directory contains adapters for services outside Methaq, such as private object storage, rendering workers, notifications, and payment gateways. Domain actions depend on contracts; infrastructure classes implement those contracts. HTTP controllers do not call vendor SDKs directly.
