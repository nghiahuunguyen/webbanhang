<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/database/dbhelper.php';

$keyword = trim($_GET['q'] ?? '');

if (mb_strlen($keyword, 'UTF-8') < 1) {
    echo json_encode([]);
    exit();
}

$safe_kw = str_replace("'", "\'", $keyword);

// Lấy thêm cột p.slug để tạo link SEO đẹp
$sql = "SELECT p.id, p.title, p.slug, p.thumbnail,
               COALESCE(MIN(v.price - v.discount), 0) AS min_price,
               COALESCE(MIN(v.price), 0) AS min_old_price
        FROM `Product` p
        LEFT JOIN `Product_Variant` v ON p.id = v.product_id AND v.deleted = 0
        WHERE p.deleted = 0 AND (p.title LIKE '%$safe_kw%' OR p.description LIKE '%$safe_kw%')
        GROUP BY p.id, p.title, p.slug, p.thumbnail
        ORDER BY p.id DESC
        LIMIT 6";

$products = executeResult($sql);
$results = [];

if (!empty($products)) {
    foreach ($products as $item) {
        $thumb = $item['thumbnail'];
        $img_src = (!empty($thumb) && file_exists(__DIR__ . '/' . ltrim($thumb, '/')))
                   ? '/webbanhang/' . ltrim($thumb, '/')
                   : ((!empty($thumb) && strpos($thumb, 'http') === 0) ? $thumb : 'https://via.placeholder.com/60x60?text=No+Img');

        $price = (int)$item['min_price'];
        $price_text = $price > 0 ? number_format($price, 0, ',', '.') . ' ₫' : 'Liên hệ';

        // Trỏ chuẩn về san-pham/slug.html hoặc fallback id
        $product_url = !empty($item['slug']) 
                       ? '/webbanhang/san-pham/' . htmlspecialchars($item['slug']) . '.html'
                       : '/webbanhang/product-detail.php?id=' . $item['id'];

        $results[] = [
            'id'    => $item['id'],
            'title' => $item['title'],
            'thumb' => $img_src,
            'price' => $price_text,
            'url'   => $product_url
        ];
    }
}

echo json_encode($results);
exit();