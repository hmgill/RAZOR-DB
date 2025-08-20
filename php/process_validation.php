<?php
// Configuration
$admin_email = "hungill@iu.edu";
$subject = "RAZOR Primer Validation Submission";
$upload_dir = "uploads/"; // Directory for storing uploads
$max_doc_size = 5 * 1024 * 1024; // 5MB
$max_img_size = 2 * 1024 * 1024; // 2MB
$allowed_doc_types = array('application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
$allowed_img_types = array('image/jpeg', 'image/png', 'image/gif');

// Create uploads directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Initialize response array
$response = array(
    'success' => false,
    'message' => '',
    'errors' => array()
);

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $virus = isset($_POST['virus']) ? sanitize_input($_POST['virus']) : '';
    $primer_id = isset($_POST['primer_id']) ? sanitize_input($_POST['primer_id']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';

    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors'][] = "Please provide a valid email address.";
    }

    // Validate virus selection
    if (empty($virus)) {
        $response['errors'][] = "Please select a virus.";
    }

    // Validate primer ID
    if (empty($primer_id)) {
        $response['errors'][] = "Please select a primer pair ID.";
    }

    // File upload paths
    $doc_path = "";
    $img_path = "";
    // Handle document upload
    if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
        $doc_info = pathinfo($_FILES['document']['name']);
        $doc_ext = $doc_info['extension'];
        $doc_name = uniqid() . '.' . $doc_ext;
        $doc_path = $upload_dir . $doc_name;
        $doc_type = $_FILES['document']['type'];

        // Validate document file size
        if ($_FILES['document']['size'] > $max_doc_size) {
            $response['errors'][] = "Document file size exceeds the limit of 5MB.";
        }

        // Validate document file type
        if (!in_array($doc_type, $allowed_doc_types)) {
            $response['errors'][] = "Invalid document file type. Only PDF, DOC, and DOCX are allowed.";
        }
    } else {
        $response['errors'][] = "Please provide documentation of the validation experiment.";
    }

    // Handle image upload (optional)
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $img_info = pathinfo($_FILES['image']['name']);
        $img_ext = $img_info['extension'];
        $img_name = uniqid() . '.' . $img_ext;
        $img_path = $upload_dir . $img_name;
        $img_type = $_FILES['image']['type'];
        // Validate image file size
        if ($_FILES['image']['size'] > $max_img_size) {
            $response['errors'][] = "Image file size exceeds the limit of 2MB.";
        }

        // Validate image file type
        if (!in_array($img_type, $allowed_img_types)) {
            $response['errors'][] = "Invalid image file type. Only JPG and PNG are allowed.";
        }
    }

    // If no errors, process the form
    if (empty($response['errors'])) {
        // Move uploaded files
        if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
            if (!move_uploaded_file($_FILES['document']['tmp_name'], $doc_path)) {
                $response['errors'][] = "Failed to upload document file.";
            }
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $img_path)) {
                $response['errors'][] = "Failed to upload image file.";
            }
        }
        // If files uploaded successfully, send email
        if (empty($response['errors'])) {
            // Compose email content
            $message = "
            <html>
            <head>
                <title>RAZOR Primer Validation Submission</title>
            </head>
            <body>
                <h2>RAZOR Primer Validation Submission</h2>
                <p><strong>Submission Date:</strong> " . date("Y-m-d H:i:s") . "</p>
                <p><strong>Virus:</strong> $virus</p>
                <p><strong>Primer Pair ID:</strong> $primer_id</p>
                <p><strong>Submitter Email:</strong> $email</p>
                <p>The submission includes attached documentation" . ($img_path ? " and images" : "") . ".</p>
            </body>
            </html>
            ";

            // Set email headers
            $headers = "From: RAZOR System <no-reply@example.com>\r\n";
            $headers .= "Reply-To: $email\r\n";
            $headers .= "CC: $email\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            // Create file attachments
            $attachments = array();
            if ($doc_path) {
                $attachments[] = $doc_path;
            }
            if ($img_path) {
                $attachments[] = $img_path;
            }
            // Send email with attachments
            if (!empty($attachments)) {
                // Boundary for mime separation
                $boundary = md5(time());

                // Modify headers for attachments
                $headers = "From: RAZOR System <no-reply@example.com>\r\n";
                $headers .= "Reply-To: $email\r\n";
                $headers .= "CC: $email\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

                // Compose multipart message
                $mime_message = "--$boundary\r\n";
                $mime_message .= "Content-Type: text/html; charset=UTF-8\r\n";
                $mime_message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
                $mime_message .= $message . "\r\n";

                // Add attachments
                foreach ($attachments as $file) {
                    if (file_exists($file)) {
                        $content = file_get_contents($file);
                        $content = chunk_split(base64_encode($content));
                        $name = basename($file);
                        $mime_type = mime_content_type($file);

                        $mime_message .= "--$boundary\r\n";
                        $mime_message .= "Content-Type: $mime_type; name=\"$name\"\r\n";
                        $mime_message .= "Content-Transfer-Encoding: base64\r\n";
                        $mime_message .= "Content-Disposition: attachment; filename=\"$name\"\r\n\r\n";
                        $mime_message .= $content . "\r\n";
                    }
                }

                $mime_message .= "--$boundary--";

                // Send email
                if (mail($admin_email, $subject, $mime_message, $headers)) {
                    $response['success'] = true;
                    $response['message'] = "Thank you! Your validation results will be reviewed by the RAZOR team.";
                } else {
                    $response['errors'][] = "Failed to send email. Please try again later.";
                }
            } else {
                // Send email without attachments
                if (mail($admin_email, $subject, $message, $headers)) {
                    $response['success'] = true;
                    $response['message'] = "Thank you! Your validation results will be reviewed by the RAZOR team.";
                } else {
                    $response['errors'][] = "Failed to send email. Please try again later.";
                }
            }
        }
    }
}
// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
