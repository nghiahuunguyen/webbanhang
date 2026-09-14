<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/database/dbhelper.php';

$message = trim($_POST['message'] ?? '');
if (empty($message)) {
    echo json_encode(['reply' => 'Dạ bạn cần NovaShop hỗ trợ thông tin gì ạ?']);
    exit();
}

$clean_msg = mb_strtolower($message, 'UTF-8');
$clean_msg = preg_replace('/[?!.,;:]/', ' ', $clean_msg);

// 1. ĐỌC THÔNG TIN HỆ THỐNG TỪ BẢNG System_Settings
$settings_raw = executeResult("SELECT `key_name`, `key_value` FROM `System_Settings`");
$cfg = [];
if (!empty($settings_raw)) {
    foreach ($settings_raw as $st) {
        $cfg[$st['key_name']] = $st['key_value'];
    }
}
$shop_name = $cfg['site_name'] ?? 'NovaShop';
$hotline   = $cfg['hotline'] ?? '1900 6868';
$address   = $cfg['address'] ?? '123 Đường ABC, Quận 1, TP. HCM';

// 2. NHẬN DIỆN CÂU HỎI THÔNG TIN CỬA HÀNG (Địa chỉ, Hotline, Bảo hành)
if (preg_match('/(địa chỉ|ở đâu|cửa hàng|showroom|chi nhánh|vị trí|shop ở)/i', $clean_msg)) {
    echo json_encode(['reply' => "📍 Showroom <strong>{$shop_name}</strong> tại: <strong>{$address}</strong>.<br>⏰ Giờ mở cửa: 08:00 - 22:00 tất cả các ngày trong tuần. Rất hân hạnh được đón tiếp bạn!"]);
    exit();
}

if (preg_match('/(hotline|liên hệ|số điện thoại|sđt|tổng đài|gọi|zalo)/i', $clean_msg)) {
    $bh = !empty($cfg['warranty_phone']) ? " (Bảo hành: <strong>{$cfg['warranty_phone']}</strong>)" : '';
    echo json_encode(['reply' => "📞 Tổng đài tư vấn: <strong>{$hotline}</strong>{$bh}. Bạn có thể gọi trực tiếp để được tư vấn nhanh nhất nhé!"]);
    exit();
}

if (preg_match('/(bảo hành|đổi trả|hư hỏng|lỗi kỹ thuật)/i', $clean_msg)) {
    echo json_encode(['reply' => "🛡️ <strong>Chính sách hậu mãi tại {$shop_name}:</strong><br>• Bảo hành điện tử chính hãng 12 tháng.<br>• Lỗi 1 đổi 1 tận nơi trong 30 ngày đầu.<br>• Hỗ trợ cài đặt phần mềm và vệ sinh máy miễn phí!"]);
    exit();
}

// 3. XỬ LÝ LỌC TỪ ĐỆM & TÁCH CỤM TỪ DÍNH LIỀN
// Danh sách từ đệm cần loại bỏ
$stop_words = [
    'cho xin thông tin', 'cho mình thông tin', 'cho thông tin', 'xin thông tin', 
    'thông tin về', 'thông tin', 'cho xem', 'cho hỏi', 'giá bao nhiêu', 
    'bao nhiêu tiền', 'có bán không', 'có hàng không', 'cần mua', 'tư vấn máy', 
    'tư vấn', 'máy', 'chiếc', 'bản', 'dòng', 'em', 'shop ơi', 'ad ơi', 'ạ'
];

$extracted = $clean_msg;
foreach ($stop_words as $sw) {
    $extracted = str_replace($sw, ' ', $extracted);
}

// Tự động chèn khoảng trắng giữa chữ và số: "iphone15" -> "iphone 15", "s24ultra" -> "s 24 ultra"
$extracted = preg_replace('/([a-zA-Z]+)([0-9]+)/u', '$1 $2', $extracted);
$extracted = preg_replace('/([0-9]+)([a-zA-Z]+)/u', '$1 $2', $extracted);
$extracted = trim(preg_replace('/\s+/', ' ', $extracted));

// 4. KIỂM TRA LỜI CHÀO / NẾU KHÁCH CHỈ GÕ CHUNG CHUNG
$greetings = ['xin chào', 'chào shop', 'chào bạn', 'hello', 'hi', 'alo', ''];
if (in_array($extracted, $greetings)) {
    $cats = executeResult("SELECT name FROM Category WHERE deleted = 0 LIMIT 4");
    $cat_names = !empty($cats) ? implode(', ', array_column($cats, 'name')) : 'Điện thoại, Laptop';

    $reply = "Dạ <strong>{$shop_name}</strong> xin chào bạn! 👋<br>";
    $reply .= "Shop có sẵn nhiều dòng sản phẩm <strong>{$cat_names}</strong> chính hãng kèm trả góp 0%.<br><br>";
    $reply .= "👉 Bạn đang quan tâm đến mẫu máy nào (VD: <em>iPhone 15, Galaxy S24, ASUS Vivobook</em>) để shop báo giá chi tiết ạ?";
    echo json_encode(['reply' => $reply]);
    exit();
}

// 5. TÁCH TỪ KHÓA & TRUY VẤN CƠ SỞ DỮ LIỆU SẢN PHẨM
$keywords = explode(' ', $extracted);
$where_parts = [];

foreach ($keywords as $kw) {
    $kw = trim($kw);
    if (empty($kw)) continue;
    $safe_kw = str_replace("'", "\'", $kw);
    
    // Tìm kiếm đồng thời trên Product, Brand, Category và Product_Variant
    $where_parts[] = "(p.title LIKE '%$safe_kw%' 
                      OR c.name LIKE '%$safe_kw%' 
                      OR b.name LIKE '%$safe_kw%' 
                      OR v.storage LIKE '%$safe_kw%'
                      OR v.ram LIKE '%$safe_kw%'
                      OR v.cpu LIKE '%$safe_kw%'
                      OR v.gpu LIKE '%$safe_kw%'
                      OR v.color LIKE '%$safe_kw%')";
}

$where_sql = !empty($where_parts) ? implode(' AND ', $where_parts) : "p.title LIKE '%$extracted%'";

$sql_search = "SELECT p.id, p.title, p.thumbnail, b.name AS brand_name,
                      MIN(v.price - v.discount) AS min_price,
                      MAX(v.price) AS max_old_price,
                      MAX(v.discount) AS max_discount,
                      SUM(v.stock_quantity) AS total_stock
               FROM `Product` p
               LEFT JOIN `Category` c ON p.category_id = c.id
               LEFT JOIN `Brand` b ON p.brand_id = b.id
               LEFT JOIN `Product_Variant` v ON p.id = v.product_id AND v.deleted = 0
               WHERE p.deleted = 0 AND ($where_sql)
               GROUP BY p.id
               ORDER BY p.id DESC
               LIMIT 4";

$search_items = executeResult($sql_search);

// 6. TRẢ VỀ KẾT QUẢ TÌM KIẾM CHO KHÁCH
if (!empty($search_items)) {
    $html = "Dạ thông tin sản phẩm bạn đang quan tâm đây ạ:<br><br>";
    foreach ($search_items as $item) {
        $img_src = (!empty($item['thumbnail']) && (file_exists($item['thumbnail']) || file_exists(__DIR__ . '/' . $item['thumbnail']))) 
                   ? htmlspecialchars($item['thumbnail']) 
                   : ((!empty($item['thumbnail']) && strpos($item['thumbnail'], 'http') === 0) ? $item['thumbnail'] : 'https://via.placeholder.com/60x60?text=No+Img');
        
        $min_p = (int)($item['min_price'] ?? 0);
        $price_str = ($min_p > 0) ? number_format($min_p, 0, ',', '.') . '₫' : 'Liên hệ';
        $brand = htmlspecialchars($item['brand_name'] ?? '');
        $stock = (int)($item['total_stock'] ?? 0);
        
        $stock_badge = ($stock > 0) 
            ? "<span class='badge badge-success' style='font-size: 0.65rem;'>Còn hàng</span>" 
            : "<span class='badge badge-danger' style='font-size: 0.65rem;'>Tạm hết</span>";

        $html .= "
        <div class='d-flex align-items-center mb-2 p-2 bg-white border rounded shadow-sm' style='gap: 10px;'>
            <img src='{$img_src}' style='width: 48px; height: 48px; object-fit: contain; background: #f8fafc; border-radius: 6px; padding: 2px;' class='border'>
            <div style='line-height: 1.3; flex: 1; overflow: hidden;'>
                <a href='detail.php?id={$item['id']}' target='_blank' style='font-weight: 700; color: #0f172a; font-size: 0.82rem; text-decoration: none; display: block; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;'>
                    " . htmlspecialchars($item['title']) . "
                </a>
                <div style='color: #dc2626; font-weight: 700; font-size: 0.85rem;' class='mt-1'>{$price_str}</div>
                <div class='small text-muted' style='font-size: 0.72rem;'>{$stock_badge} <span class='ml-1'>{$brand}</span></div>
            </div>
        </div>";
    }
    $html .= "<small class='text-muted'>Bấm trực tiếp vào máy để xem chi tiết và mua hàng nhé!</small>";
    echo json_encode(['reply' => $html]);
    exit();
}

// 7. NẾU KHÔNG TÌM THẤY SẢN PHẨM KHỚP
echo json_encode([
    'reply' => "Dạ <strong>{$shop_name}</strong> chưa tìm thấy sản phẩm khớp với từ khóa <strong>'" . htmlspecialchars($message) . "'</strong>.<br><br>" .
               "Bạn hãy thử nhập theo các gợi ý sau nhé:<br>" .
               "• Tên máy: <em>iPhone 15, S24 Ultra, Macbook Air</em><br>" .
               "• Hãng: <em>Apple, Samsung, Dell, Asus</em><br>" .
               "• Danh mục: <em>Điện thoại, Laptop, Phụ kiện</em>"
]);