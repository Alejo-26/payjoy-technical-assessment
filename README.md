# PayJoy - Currency Conversion Automation (Tools Specialist Assessment)

An end-to-end integration designed to automate currency conversion inquiries for customer smartphone financing installments. The system consists of a custom REST API built with PHP and a conversational chatbot flow built on Landbot connected via Webhooks.

---

## Architecture Overview

1. **Customer Interaction (Frontend):** A conversational bot on Landbot captures the financing installment amount in USD and the target currency code.
2. **Backend API (Middleware):** A lightweight PHP REST service validates user input, handles errors, and acts as a secure reverse proxy/gateway to fetch real-time exchange rates.
3. **External Integration:** Consumes the ExchangeRate API to retrieve up-to-date conversion rates and computes the converted installment.

---

## Project Structure

```text
├── .env.example        # Template for required environment variables
├── .gitignore          # Excludes sensitive files (.env)
├── env_loader.php      # Custom lightweight environment variable parser
├── index.php           # REST API entrypoint & conversion endpoint
└── README.md           # Documentation, architectural decisions, and production metrics
```

---

## Local Setup & Installation

### Prerequisites
* **PHP 8.x** installed locally.
* Required PHP extensions enabled in `php.ini`:
  * `curl`
  * `openssl`
  * `mbstring`
* An API key from [ExchangeRate API](https://www.exchangerate-api.com/).

### Installation Steps

1. **Clone the repository:**
   ```bash
   git clone <YOUR_REPOSITORY_URL>
   cd <REPOSITORY_FOLDER>
   ```

2. **Environment Configuration:**
   Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
   Open `.env` and set your personal API key:
   ```ini
   EXCHANGE_RATE_API_KEY=your_actual_api_key_here
   ```
   *(Note: The `.env` file is excluded from version control to prevent exposing credentials).*

3. **Run the API locally:**
   Start the built-in PHP development server:
   ```bash
   php -S localhost:8000
   ```

4. **Verify the Endpoint:**
   Open your browser or make a GET request using cURL:
   ```bash
   curl "http://localhost:8000/index.php?amount=200&currency=BRL"
   ```
   **Expected Response (HTTP 200):**
   ```json
   {
       "amount_usd": 200,
       "currency": "BRL",
       "converted": 1009.94,
       "rate": 5.0497
   }
   ```

---

## Technical Decisions & Rationale

* **Language Choice (PHP):** Implemented in PHP to showcase fast learning curve adaptation, practical problem solving, and standard backend architecture principles.
* **Lightweight Native Architecture:** Instead of pulling heavy external frameworks or third-party package dependencies that could introduce platform-specific compatibility hurdles, the API utilizes a native modular design with a custom `.env` loader.
* **Security & Credential Encapsulation:** The ExchangeRate API requires passing the API key in the request URL. Exposing this call on client-side applications would compromise the secret key. The PHP backend isolates and encapsulates the API key server-side, preventing credential leakage in access logs or network traces.
* **Resilience & Error Handling:** Comprehensive input validation is applied before reaching external dependencies:
  * Returns `400 Bad Request` for missing query parameters or non-positive numerical values.
  * Returns `404 Not Found` for invalid or unsupported currency codes (e.g., non-ISO codes).
  * Returns `502 Bad Gateway` if external network connectivity fails.

---

## What I Would Improve With More Time

1. **In-Memory Caching (Redis / Memcached / File Cache):** Exchange rates do not fluctuate dramatically second by second. Implementing a TTL-based cache (e.g., 1 hour) for rates would reduce network latency to under 10ms and prevent exhausting third-party API rate limits (staying well within the 1,500 monthly free tier requests).
2. **Containerization (Docker & Docker Compose):** Provide a `Dockerfile` and `docker-compose.yml` to package PHP, CA certificates, and runtime extensions into a single command (`docker compose up`), removing manual local environment setups.
3. **Automated Unit & Integration Testing:** Implement a test suite using PHPUnit with mocked HTTP responses to validate edge cases (API timeouts, malformed JSON, boundary currency values) in a CI/CD pipeline.
4. **Structured Logging & Telemetry:** Introduce centralized structured logging (JSON format) sending logs to Datadog or ELK Stack with correlation IDs across both the chatbot and the API.

---

## Measuring Success in Production

To assess the operational impact and reliability of this solution for the Global Tools team, I would track:

* **Customer Resolution Rate (First Contact Resolution - FCR):** Percentage of users whose currency inquiries are fully answered by the bot without escalating to a human CX agent.
* **API Availability & SLA:** Endpoint uptime (targeting 99.9%) and p95/p99 latency metrics for rate retrieval.
* **Fallback / Error Rate:** Frequency of `4xx` (user input errors) and `5xx` (external provider failures) to evaluate the health of external integrations and prompt clarity in the bot.
* **Support Ticket Deflection & Cost Savings:** Measuring the direct reduction in customer support queue volume and agent handling time for installment questions.