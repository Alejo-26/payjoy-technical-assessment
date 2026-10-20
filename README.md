# PayJoy - Currency Conversion Automation (Tools Specialist Assessment)

An end-to-end integration designed to automate currency conversion inquiries for customer smartphone financing installments. The system consists of a custom REST API built with PHP and a conversational chatbot flow built on Landbot connected via Webhooks.

## Architecture Overview

1. **Customer Interaction (Frontend):** A conversational bot on Landbot captures the financing installment amount in USD and the target currency code.
2. **Backend API (Middleware):** A lightweight PHP REST service validates user input, handles errors, and acts as a secure reverse proxy/gateway to fetch real-time exchange rates.
3. **External Integration:** Consumes the ExchangeRate API to retrieve up-to-date conversion rates and computes the converted installment.

## Project Structure

```text
├── .env.example        # Template for required environment variables
├── .gitignore          # Excludes sensitive files (.env)
├── env_loader.php      # Custom lightweight environment variable parser
├── index.php           # REST API entrypoint & conversion endpoint
└── README.md           # Documentation, architectural decisions, and production metrics
```

## Local Setup & Installation

### Prerequisites

* **PHP 8.x** installed locally.
* Required PHP extensions enabled in `php.ini`:
  * `curl`
  * `openssl`
  * `mbstring`
* **SSL Certificate Bundle (Windows specific):** Windows does not supply a default CA trust store for PHP cURL. To prevent SSL verification errors (`unable to get local issuer certificate`):
  1. Download the official Mozilla CA certificate bundle: [cacert.pem](https://curl.se/ca/cacert.pem).
  2. Save it to your PHP directory (e.g., `C:\php\cacert.pem`).
  3. Set the absolute paths in `php.ini`:
     ```ini
     curl.cainfo = "C:\php\cacert.pem"
     openssl.cafile = "C:\php\cacert.pem"
     ```
* An API key from [ExchangeRate API](https://www.exchangerate-api.com/).
* [ngrok](https://ngrok.com/) (or any HTTP tunneling tool) for connecting cloud webhooks to your local development environment.

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

## Connecting the Chatbot (Landbot & ngrok Setup)

Because Landbot runs in the cloud and communicates via outgoing webhooks, an HTTP tunnel is required to expose the local PHP service:

1. **Expose Local Server with ngrok:**
   With your local PHP server active on port 8000, run in a separate terminal:
   ```bash
   ngrok http 8000
   ```
   Copy the generated forwarding HTTPS URL (e.g., `https://<unique-subdomain>.ngrok-free.app`).

2. **Configure the Landbot Webhook Block:**
   * **Request Method:** `GET`
   * **URL:**
     `https://<unique-subdomain>.ngrok-free.app/index.php?amount=@amount&currency=@currency`
   * **Response Mapping (Save Responses as Fields):**
     * Map `converted` → `@converted`
     * Map `rate` → `@rate`
   * **Response Routing:**
     * Status `200`: Route to success message (`Ready! The estimated amount for your installment is @converted @currency (applied exchange rate: @rate)`).
     * Status `400` / `500`: Route to friendly error handler (`Sorry, we could not process that currency code or the service is temporarily unavailable. Please verify and try again`).

> **Note for the Live Demonstration:** During the live walkthrough session, an active ngrok tunnel will be maintained to showcase real-time communication between Landbot and the local PHP backend.

## Technical Decisions & Rationale

* **Language Choice (PHP):** Implemented in PHP to showcase fast learning curve adaptation, practical problem solving, and standard backend architecture principles.
* **Lightweight Native Architecture:** Instead of pulling heavy external frameworks or third-party package dependencies that could introduce platform-specific compatibility hurdles, the API utilizes a native modular design with a custom `.env` loader.
* **Security & Credential Encapsulation:** The ExchangeRate API requires passing the API key in the request URL. Exposing this call on client-side applications would compromise the secret key. The PHP backend isolates and encapsulates the API key server-side, preventing credential leakage in access logs or network traces.
* **Resilience & Error Handling:** Comprehensive input validation is applied before reaching external dependencies:
  * Returns `400 Bad Request` for missing query parameters or non-positive numerical values.
  * Returns `404 Not Found` for invalid or unsupported currency codes (e.g., non-ISO codes).
  * Returns `502 Bad Gateway` if external network connectivity fails.

## What I Would Improve With More Time

### 1. CX & Conversational Enhancements
* **Intelligent Country-to-Currency Resolution (AI / Low-Code Middleware):** Instead of requiring customers to provide standard ISO 4217 currency codes (e.g., `BRL`, `COP`), integrate an automation layer (via **n8n**, **Make**, or **Zapier** with an LLM agent) that captures natural language inputs (such as *"Brasil"*, *"Colombia"*, or *"Mexico"*) and normalizes them into target currency codes seamlessly before querying the API.
* **Catalog & Flexible Installment Calculation:** Connect the service directly with PayJoy's device catalog so customers can select a smartphone model (retrieving the USD base price automatically) and choose installment terms (weekly, bi-weekly, or monthly breakdowns) rather than calculating a single raw amount.
* **Automatic Market Detection:** Detect the customer's region automatically via WhatsApp/phone country codes or IP headers to bypass the currency prompt entirely.

### 2. Backend Architecture & Infrastructure
* **In-Memory Caching (Redis / Memcached / File Cache):** Exchange rates do not fluctuate dramatically second by second. Implementing a TTL-based cache (e.g., 1 hour) for rates would reduce network latency to under 10ms and prevent exhausting third-party API rate limits (staying well within the 1,500 monthly free tier requests).
* **Containerization (Docker & Docker Compose):** Provide a `Dockerfile` and `docker-compose.yml` to package PHP, CA certificates, and runtime extensions into a single command (`docker compose up`), removing manual local environment setups.
* **Automated Unit & Integration Testing:** Implement a test suite using PHPUnit with mocked HTTP responses to validate edge cases (API timeouts, malformed JSON, boundary currency values) in a CI/CD pipeline.
* **Structured Logging & Telemetry:** Introduce centralized structured logging (JSON format) sending logs to Datadog or ELK Stack with correlation IDs across both the chatbot and the API.

## Measuring Success in Production

To assess the operational impact and reliability of this solution for the Global Tools team, I would track:

* **Customer Resolution Rate (First Contact Resolution - FCR):** Percentage of users whose currency inquiries are fully answered by the bot without escalating to a human CX agent.
* **API Availability & SLA:** Endpoint uptime (targeting 99.9%) and p95/p99 latency metrics for rate retrieval.
* **Fallback / Error Rate:** Frequency of `4xx` (user input errors) and `5xx` (external provider failures) to evaluate the health of external integrations and prompt clarity in the bot.
* **Support Ticket Deflection & Cost Savings:** Measuring the direct reduction in customer support queue volume and agent handling time for installment questions.