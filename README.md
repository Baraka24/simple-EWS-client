# simple-EWS-client
Using Garethp/php-ews
# Simple Exchange Web Services (EWS) Client

A PHP-based web application that connects to Microsoft Exchange servers using Exchange Web Services (EWS) to retrieve and display emails and calendar events.

## Installation

1. **Clone or download the project:**
   ```bash
   cd /path/to/your/project
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure your Exchange settings:**
   Edit `index.php` and update the configuration section:
   ```php
   $server = 'your-exchange-server.com/EWS/Exchange.asmx';
   $username = 'your-username@domain.com';
   $password = 'your-password';
   ```

## Testing the Solution

### Method 1: Built-in PHP Web Server (Recommended)

1. **Start the PHP web server:**
   ```bash
   cd /path/to/ews-client
   php -S localhost:8080
   ```

2. **Open your browser and navigate to:**
   ```
   http://localhost:8080
   ```

3. **What you should see:**
   - Connection status messages
   - Number of emails found in inbox
   - List of emails with subjects, senders, and content
   - Number of calendar events found
   - List of calendar events with dates, locations, and descriptions

## Dependencies

- [garethp/php-ews](https://github.com/Garethp/php-ews) - PHP Exchange Web Services library
- [jamesiarmes/php-ntlm](https://github.com/jamesiarmes/php-ntlm) - NTLM authentication support