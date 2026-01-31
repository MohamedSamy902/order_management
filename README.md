# OrderTask API

Laravel 12 REST API with JWT authentication and OTP email verification.

## Features

- JWT-based authentication
- OTP email verification for registration
- API versioning (v1)
- Rate limiting
- Strong password validation
- API secret key protection
- Uses **[Advanced File Upload](https://github.com/mohamedsamy902/advanced-file-upload)** (Custom package by the author) for secure image handling

## Requirements

- PHP 8.2+
- Composer
- MySQL/PostgreSQL
- Mail server (for OTP emails)

## Setup

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_DATABASE=ordertask
DB_USERNAME=root
DB_PASSWORD=

# Configure mail in .env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525

# Generate JWT secret
php artisan jwt:secret

# Set API secret key
API_SECRET_KEY=your-secret-key-here

# OTP settings (optional, defaults shown)
OTP_EXPIRY=10
OTP_MAX_ATTEMPTS=5

# Payment Gateway Callback URLs (Standardized)
MYFATOORAH_CALLBACK_URL="${APP_URL}/api/v1/payments/callback/myfatoorah"
MYFATOORAH_ERROR_URL="${APP_URL}/api/v1/payments/callback/myfatoorah"
TABBY_CALLBACK_URL="${APP_URL}/api/v1/payments/callback/tabby"
TAMARA_CALLBACK_URL="${APP_URL}/api/v1/payments/callback/tamara"
TAMARA_WEBHOOK_URL="${APP_URL}/api/v1/payments/webhook/tamara"

# Run migrations
php artisan migrate

# Seed the database (optional)
php artisan db:seed
```

---

## 🚀 API Documentation

### Base URL

```
http://localhost:8000/api/v1
```

### Authentication Endpoints

| Method | Endpoint           | Description       | Auth Required |
| ------ | ------------------ | ----------------- | ------------- |
| POST   | `/auth/register`   | Register new user | No            |
| POST   | `/auth/verify-otp` | Verify OTP code   | No            |
| POST   | `/auth/login`      | Login user        | No            |
| POST   | `/auth/logout`     | Logout user       | Yes           |

### Order Endpoints

| Method     | Endpoint                      | Description              | Auth Required |
| ---------- | ----------------------------- | ------------------------ | ------------- |
| GET        | `/orders`                     | List all orders          | Yes           |
| GET        | `/orders?status=pending`      | Filter by order status   | Yes           |
| GET        | `/orders?payment_status=paid` | Filter by payment status | Yes           |
| POST       | `/orders`                     | Create new order         | Yes           |
| GET        | `/orders/{id}`                | Get order details        | Yes           |
| **PUT**    | `/orders/{id}`                | **Update order**         | Yes           |
| **DELETE** | `/orders/{id}`                | **Delete order**         | Yes           |
| POST       | `/orders/{id}/cancel`         | Cancel order             | Yes           |

**Business Rules:**

- ✅ Orders can only be updated if `payment_status != 'paid'`
- ✅ Orders can only be deleted if no payments exist
- ✅ Orders can only be deleted if `payment_status != 'paid'`
- ✅ Deleted orders restore product stock automatically

### Payment Endpoints

| Method | Endpoint                       | Description      | Auth Required |
| ------ | ------------------------------ | ---------------- | ------------- |
| POST   | `/payments/initiate`           | Initiate payment | Yes           |
| POST   | `/payments/callback/{gateway}` | Payment callback | No            |
| POST   | `/payments/webhook/{gateway}`  | Payment webhook  | No            |

### Product Endpoints

| Method | Endpoint         | Description         | Auth Required |
| ------ | ---------------- | ------------------- | ------------- |
| GET    | `/products`      | List all products   | No            |
| GET    | `/products/{id}` | Get product details | No            |
| POST   | `/products`      | Create product      | Yes           |
| POST   | `/products/{id}` | Update product      | Yes           |
| DELETE | `/products/{id}` | Delete product      | Yes           |

---

## 🔌 Payment Gateway Extensibility

This project uses the **Strategy Pattern** to allow easy integration of new payment gateways.

### Current Supported Gateways

1. **MyFatoorah** - Auto-capture payment
2. **Tabby** - Buy now, pay later
3. **Tamara** - Installment payments

### Architecture

```
PaymentGatewayInterface (Contract)
        ↓
BasePaymentGateway (Abstract Base)
        ↓
    ┌───┴───┬───────┬────────┐
    │       │       │        │
MyFatoorah Tabby Tamara  [NewGateway]
```

### Adding a New Gateway

**Step 1:** Create gateway class

```php
// app/Payment/Gateways/StripeGateway.php
<?php

namespace App\Payment\Gateways;

use App\Models\Order;

class StripeGateway extends BasePaymentGateway
{
    public function __construct()
    {
        $this->apiKey = config('payment.gateways.stripe.secret_key');
        $this->apiURL = config('payment.gateways.stripe.api_url');
        $this->testMode = config('payment.gateways.stripe.test_mode');
    }

    public function createCheckoutSession(Order $order): array
    {
        // Implement Stripe checkout logic
    }

    public function verifyPayment(string $paymentId): bool
    {
        // Implement Stripe verification
    }

    public function capturePayment(Order $order, string $paymentId): array
    {
        // Implement capture (if needed)
    }

    public function refundPayment(Payment $payment, float $amount): array
    {
        // Implement refund logic
    }

    public function handleWebhook(array $payload): void
    {
        // Handle Stripe webhooks
    }

    public function getGatewayName(): string
    {
        return 'stripe';
    }
}
```

**Step 2:** Register in factory

```php
// app/Payment/PaymentGatewayFactory.php
public static function make(string $gateway): PaymentGatewayInterface
{
    return match ($gateway) {
        'myfatoorah' => new MyFatoorahGateway(),
        'tabby' => new TabbyGateway(),
        'tamara' => new TamaraGateway(),
        'stripe' => new StripeGateway(), // ← Add here
        default => throw new \Exception("Unsupported gateway: {$gateway}"),
    };
}
```

**Step 3:** Add configuration

```php
// config/payment.php
'stripe' => [
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    'api_url' => 'https://api.stripe.com',
    'test_mode' => env('STRIPE_TEST_MODE', true),
],
```

**That's it!** ✅ The new gateway is now integrated.

---

## 🧪 Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suites

```bash
# Order tests
php artisan test --filter=OrderApiTest

# Payment tests
php artisan test --filter=PaymentApiTest

# Auth tests
php artisan test --filter=AuthApiTest
```

### Test Coverage

- ✅ **Authentication**: Register, Login, OTP verification
- ✅ **Orders**: CRUD operations, filtering, business rules
- ✅ **Payments**: Initiation, callbacks, status updates
- ✅ **Products**: CRUD operations
- ✅ **Profile**: Update with image upload

---

## 📚 Additional Documentation

- **[Payment Integration Guide](./brain/76ba431c-863c-40ab-ad4d-396b154c7130/payment_integration_guide.md)** - Detailed payment flow
- **[Add New Gateway Guide](./brain/76ba431c-863c-40ab-ad4d-396b154c7130/add_new_payment_gateway_guide.md)** - Step-by-step gateway integration
- **[Complete Order Flow](./brain/76ba431c-863c-40ab-ad4d-396b154c7130/complete_order_flow.md)** - End-to-end order lifecycle
- **[Postman Documentation Collection](./order_management.json)** - Full API documentation with all response cases (200, 400, 401, 404, 422) for all 22 endpoints.

---

## 🎯 Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/V1/  # API Controllers
│   └── Requests/Api/         # Form Requests
├── Services/                 # Business Logic Layer (OrderService, PaymentService, OtpService)
├── Payment/
│   ├── Gateways/            # Payment Gateway Implementations (MyFatoorah, Tabby, Tamara)
│   └── PaymentGatewayFactory.php
├── Models/                   # Eloquent Models
└── Transformers/API/V1/     # Response Transformers

routes/
└── api_v1.php               # API Routes

tests/
├── Feature/Api/V1/          # Feature Tests
└── Unit/                    # Unit Tests
```

---

## 📝 Notes and Assumptions

### Assumptions

1. **Payment Flow**: Orders are created first, then payment is initiated
2. **Payment Callbacks**: All gateways use standardized callbacks configured via `.env`
3. **Stock Management**: Stock is reduced on order creation, restored on cancellation/deletion
4. **Order Updates**: Only notes and addresses can be updated, items cannot be modified after creation

### Security

- JWT token-based authentication
- API secret key middleware
- Rate limiting on sensitive endpoints
- OTP expiration (10 minutes)
- Strong password validation

### Known Limitations

- Payment callback URLs require public access (use ngrok for local testing)
- MyFatoorah uses different IDs for creation vs callback (handled via CustomerReference)
- Stock validation is optimistic (no locks)

---

## 🤝 Contributing

This is a task project. For production use, consider:

- Adding database transactions for complex operations
- Implementing proper logging and monitoring
- Adding queue processing for heavy operations
- Implementing proper error handling and retries

---

## 📄 License

This project is for educational/assessment purposes.

---

## 🚀 Quick Start Example

```bash
# 1. Setup
composer install && cp .env.example .env
php artisan key:generate && php artisan jwt:secret
php artisan migrate

# 2. Start server
php artisan serve

# 3. Test API
# Register user
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@tocaan.com","password":"Password123!"}'

# Create order
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"items":[{"product_id":1,"quantity":2}],"payment_method":"myfatoorah"}'
```

**Project Complete! 🎉**

## API Endpoints

### Authentication

**Register**

```
POST /api/v1/auth/register
Content-Type: application/json
X-SECRET-KEY: your-api-key

{
  "name": "John Doe",
  "email": "john@tocaan.com",
  "password": "SecureP@ssw0rd!",
  "password_confirmation": "SecureP@ssw0rd!"
}
```

**Verify OTP**

```
POST /api/v1/auth/verify-otp
{
  "email": "john@tocaan.com",
  "otp": "123456"
}
```

**Login**

```
POST /api/v1/auth/login
{
  "email": "john@tocaan.com",
  "password": "SecureP@ssw0rd!"
}
```

**Protected Endpoints** (require Bearer token)

```
POST /api/v1/auth/logout
POST /api/v1/auth/refresh
GET /api/v1/auth/me
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Unit/OtpServiceTest.php

# Run with coverage
php artisan test --coverage
```

## Project Structure

```
app/
├── Helpers/
│   └── ApiResponse.php       # Standardized API responses
├── Http/
│   ├── Controllers/Api/V1/   # API controllers
│   ├── Middleware/           # Custom middleware
│   └── Requests/             # Form request validation
├── Mail/                     # Mail templates
├── Models/                   # Eloquent models
└── Services/                 # Business logic
    └── OtpService.php
```

## Security

- All API endpoints require X-SECRET-KEY header
- Passwords must be 8+ characters with mixed case, numbers, and symbols
- Passwords are checked against known data leaks
- JWT tokens expire after configured time
- OTP codes expire after 10 minutes (configurable)
- Maximum 5 OTP attempts before requiring new code
- Rate limiting: 5 login attempts/minute, 60 API calls/minute
