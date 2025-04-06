<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$log_file = 'feedback.log';

function logFeedback($data, $ip) {
    global $log_file;
    $log_entry = "[" . date('Y-m-d H:i:s') . "] from IP: $ip\n";
    $log_entry .= "Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
    $log_entry .= "--------------------------------------------------\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

$response = ['success' => false, 'errors' => []];

$name = $email = $phone = $description = '';

if (!empty($_POST['name'])) {
    $name = $_POST['name'];
} else {
    $response['errors']['name'] = 'name is empty';
}

if (!empty($_POST['email'])) {
    $email = $_POST['email'];
} else {
    $response['errors']['email'] = 'email is empty';
}

if (!empty($_POST['phone'])) {
    $phone = $_POST['phone'];
}
    
if (!empty($_POST['description'])) {
    $description = $_POST['description'];
} else {
    $response['errors']['description'] = 'description is empty';
}
$files_arr = array();
if(isset($_FILES['files']['name'])){
    $countfiles = count($_FILES['files']['name']);

    $upload_location = "uploads/"; 
    if (!is_dir($upload_location)) {
        mkdir($upload_location, 0755, true);
    }
    for($index = 0;$index < $countfiles;$index++){

       if(isset($_FILES['files']['name'][$index]) && $_FILES['files']['name'][$index] != ''){
            $filename = $_FILES['files']['name'][$index];
            $newfilename = time()."_".$filename;
            $path = $upload_location.$newfilename;
            if(move_uploaded_file($_FILES['files']['tmp_name'][$index],$path)){
                $files_arr[] = array("file_path" => $path, "original_name" => $filename);
            }
       }
    }
}
if (!empty($response['errors'])) {
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'];
logFeedback([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'description' => $description,
        'files' => implode(' ', array_column($files_arr, 'original_name'))
], $ip);

$db_host = 'localhost';
$db_name = 'feedback_form';
$db_user = 'root';
$db_pass = '';

try {
    $conn = new mysqli($db_host, $db_user, $db_pass);
    
    if ($conn->connect_error) {
        throw new Exception("Ошибка: " . $conn->connect_error);
    }
    
    $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
    if (!$conn->query($sql)) {
        throw new Exception("Ошибка: " . $conn->error);
    }
        
    $conn->select_db($db_name);
        
    $sql = "CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)";
        
    if (!$conn->query($sql)) {
        throw new Exception("Ошибка: " . $conn->error);
    }
        
    $sql = "CREATE TABLE IF NOT EXISTS feedback_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        feedback_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        FOREIGN KEY (feedback_id) REFERENCES feedback(id) ON DELETE CASCADE)";
        
    if (!$conn->query($sql)) {
        throw new Exception("Ошибка: " . $conn->error);
    }
        
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка инициализации базы данных']);
    exit;
}

try{
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        throw new Exception("Ошибка: " . $conn->connect_error);
    }

    $conn->begin_transaction();
    try {
        $sql = "INSERT INTO feedback (name, email, phone, description) VALUES ('$name', '$email', '$phone', '$description')";

        if(!$conn->query($sql)){
            throw new Exception("Ошибка: " . $conn->error);
        }
        $feedback_id = $conn->insert_id;

        if($files_arr) {
            foreach ($files_arr as $file)
            {
                $file_path = $file["file_path"];
                $original_name = $file["original_name"];
                $sql = "INSERT INTO feedback_files (feedback_id, file_path, original_name) VALUES ('$feedback_id', '$file_path', '$original_name')";

                if(!$conn->query($sql)){
                    throw new Exception("Ошибка: " . $conn->error);
                }
            }
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception("Ошибка: " . $e->getMessage());
    }
    $conn->close();

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
$response['success'] = true;
$response['message'] = 'Форма успешно отправлена';
echo json_encode($response);
?> 
