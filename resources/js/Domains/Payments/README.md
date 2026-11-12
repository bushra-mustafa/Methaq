# Payments frontend domain

First release uses manual bank transfer: the owner uploads a receipt on `Pages/App/Events/Payment.tsx` and an administrator approves or rejects it from Filament. Payment state always comes from the server (`EventPaymentDTO`); the client never sends amounts, statuses or entitlements. Online gateway checkout remains deferred to phase 14.
