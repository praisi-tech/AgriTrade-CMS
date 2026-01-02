<?php
$to = "trade@agritrade-cms.com"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = strip_tags(trim($_POST["name"]));
    $company = strip_tags(trim($_POST["company"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $country = strip_tags(trim($_POST["country"]));
    $product = strip_tags(trim($_POST["product"]));
    $message = strip_tags(trim($_POST["message"]));

    if (empty($name) || empty($email) || empty($message)) {
        echo "Please complete the form and try again.";
        exit;
    }

    $subject = "New Export Inquiry from: $name ($company)";

    $email_content = "You have received a new inquiry from your website.\n\n";
    $email_content .= "Name: $name\n";
    $email_content .= "Company: $company\n";
    $email_content .= "Email: $email\n";
    $email_content .= "Country of Destination: $country\n";
    $email_content .= "Product Interest: $product\n\n";
    $email_content .= "Message/Specifications:\n$message\n";

    $headers = "From: $name <$email>";

    if (mail($to, $subject, $email_content, $headers)) {
        echo "<script>
                alert('Thank you! Your inquiry has been sent. We will respond within 24 hours.');
                window.location.href='index.php';
              </script>";
    } else {
        echo "Oops! Something went wrong, and we couldn't send your message.";
    }

} else {
    echo "There was a problem with your submission, please try again.";
}
?>