<?php
// Set header to return JSON
header('Content-Type: application/json');

// --- Global variables ---
$log_file = 'chatbot_debug.log';

// --- Helper Functions ---
function debug_log($message) {
    global $log_file;
    error_log(date('[Y-m-d H:i:s] ') . print_r($message, true) . "
", 3, $log_file);
}

function send_response($type, $payload) {
    echo json_encode(['type' => $type, 'payload' => $payload]);
    exit;
}

// --- Database Connection ---
try {
    require_once 'csdl.php'; // Includes the $connection
} catch (Exception $e) {
    debug_log('Database connection failed: ' . $e->getMessage());
    send_response('text', 'Lỗi kết nối cơ sở dữ liệu.');
}

// --- Main Logic ---

// Get and decode the request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);
debug_log($data);

if (!isset($data['message']) || trim($data['message']) === '') {
    debug_log('Error: No message key found in request.');
    send_response('text', 'Đã có lỗi xảy ra. Không nhận được tin nhắn.');
}

$user_message = trim($data['message']);
$normalized_message = strtolower($user_message);

// --- Intent Routing ---

// 1. Intent: Greeting
if (preg_match('/chào|hello|hi/i', $normalized_message)) {
    send_response('text', 'Chào bạn! Tôi là trợ lý ảo tư vấn sách. Bạn muốn tìm sách theo thể loại hay cần tôi gợi ý một vài cuốn sách hay?');
}

// 2. Intent: Thank you
if (preg_match('/cảm ơn|thank you|cám ơn/i', $normalized_message)) {
    send_response('text', 'Không có gì ạ! Tôi có thể giúp gì thêm cho bạn không?');
}

// 3. Intent: Ask for recommendation
if (preg_match('/gợi ý|tư vấn|recommend|suggest/i', $normalized_message)) {
    try {
        $stmt = $connection->prepare("SELECT MaSach, TenSach, HinhAnh FROM sach ORDER BY RAND() LIMIT 3");
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($books) > 0) {
            // Prepend a text message to the book list
            $response_payload = [
                'message' => 'Tất nhiên rồi! Bạn có thể tham khảo một vài cuốn sau đây:',
                'books' => $books
            ];
            send_response('book_list', $response_payload);
        } else {
            send_response('text', 'Xin lỗi, hiện tại tôi không tìm thấy cuốn sách nào trong kho.');
        }
    } catch (PDOException $e) {
        debug_log('DB Error (Recommendation): ' . $e->getMessage());
        send_response('text', 'Xin lỗi, đã có lỗi xảy ra khi truy vấn cơ sở dữ liệu.');
    }
}

// 4. Intent: Search by Category
if (preg_match('/thể loại (.+)/i', $user_message, $matches) || preg_match('/tìm sách (thể loại |về )?(.+)/i', $user_message, $matches)) {
    // The category name is usually the last captured group
    $category_name = trim(end($matches));
    
    try {
        $stmt_cat = $connection->prepare("SELECT MaTheLoai FROM TheLoai WHERE TenTheLoai LIKE ?");
        $stmt_cat->execute(["%$category_name%"]);
        $category = $stmt_cat->fetch(PDO::FETCH_ASSOC);

        if ($category) {
            $maTheLoai = $category['MaTheLoai'];
            $stmt_books = $connection->prepare("SELECT MaSach, TenSach, HinhAnh FROM sach WHERE MaTheLoai = ? LIMIT 5");
            $stmt_books->execute([$maTheLoai]);
            $books = $stmt_books->fetchAll(PDO::FETCH_ASSOC);

            if (count($books) > 0) {
                 $response_payload = [
                    'message' => "Tôi đã tìm thấy các sách thuộc thể loại '{$category_name}':",
                    'books' => $books
                ];
                send_response('book_list', $response_payload);
            } else {
                send_response('text', "Xin lỗi, tôi không tìm thấy sách nào thuộc thể loại '{$category_name}'.");
            }
        } else {
            send_response('text', "Xin lỗi, tôi không tìm thấy thể loại sách '{$category_name}'. Bạn có muốn tìm các thể loại như 'Văn học', 'Kinh tế', 'Lập trình'?");
        }
    } catch (PDOException $e) {
        debug_log('DB Error (Category Search): ' . $e->getMessage());
        send_response('text', 'Xin lỗi, đã có lỗi xảy ra khi truy vấn cơ sở dữ liệu.');
    }
}

// 5. Fallback
send_response('text', 'Xin lỗi, tôi chưa hiểu rõ yêu cầu của bạn. Bạn có thể hỏi tôi "Tư vấn cho tôi một vài cuốn sách" hoặc "Tìm sách thể loại văn học".');

?>
