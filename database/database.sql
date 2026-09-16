-- 1. Bảng Quyền hạn (Role)
CREATE TABLE `Role` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bảng Người dùng (User)
CREATE TABLE `User` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `account` varchar(50) DEFAULT NULL,
  `password` varchar(64) NOT NULL,
  `role_id` int NOT NULL DEFAULT 2,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` tinyint DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bảng Danh mục (Category)
CREATE TABLE `Category` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `deleted` tinyint DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bảng Thương hiệu / Hãng (Brand)
CREATE TABLE `Brand` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `deleted` tinyint DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bảng Sản phẩm gốc (Product)
CREATE TABLE `Product` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `brand_id` int DEFAULT NULL,
  `title` varchar(350) NOT NULL,
  `thumbnail` varchar(500) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` tinyint DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bảng Biến thể cấu hình & Giá (Product_Variant)
-- Quyết định giá bán và số lượng tồn kho theo Màu, RAM, Ổ cứng, GPU
CREATE TABLE `Product_Variant` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `ram` varchar(50) DEFAULT NULL,
  `cpu` varchar(100) DEFAULT NULL,
  `storage` varchar(50) DEFAULT NULL,
  `gpu` varchar(100) DEFAULT NULL,
  `price` int NOT NULL DEFAULT 0,
  `discount` int NOT NULL DEFAULT 0,
  `stock_quantity` int NOT NULL DEFAULT 0,
  `deleted` tinyint DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Bảng Thư viện ảnh sản phẩm & ảnh theo màu (Galery)
CREATE TABLE `Galery` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `variant_id` int DEFAULT NULL,
  `thumbnail` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Bảng Đánh giá & Phản hồi (FeedBack)
CREATE TABLE `FeedBack` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `subject_name` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Bảng Đơn hàng (Orders)
CREATE TABLE `Orders` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `note` varchar(500) DEFAULT NULL,
  `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '0: Chờ xác nhận, 1: Đang vận chuyển, 2: Hoàn thành, 3: Đã hủy',
  `total_money` int NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Bảng Chi tiết đơn hàng (Order_Details)
CREATE TABLE `Order_Details` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `variant_id` int DEFAULT NULL,
  `price` int NOT NULL DEFAULT 0,
  `num` int NOT NULL DEFAULT 1,
  `total_money` int NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================== TẠO KHÓA NGOẠI (FOREIGN KEYS) ====================

-- Người dùng thuộc một Vai trò
ALTER TABLE `User` 
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `Role` (`id`) ON DELETE RESTRICT;

-- Sản phẩm thuộc Danh mục và Hãng
ALTER TABLE `Product` 
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `Category` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_product_brand` FOREIGN KEY (`brand_id`) REFERENCES `Brand` (`id`) ON DELETE SET NULL;

-- Biến thể thuộc Sản phẩm
ALTER TABLE `Product_Variant` 
  ADD CONSTRAINT `fk_variant_product` FOREIGN KEY (`product_id`) REFERENCES `Product` (`id`) ON DELETE CASCADE;

-- Ảnh thư viện gắn với Sản phẩm và Biến thể (màu sắc)
ALTER TABLE `Galery` 
  ADD CONSTRAINT `fk_galery_product` FOREIGN KEY (`product_id`) REFERENCES `Product` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_galery_variant` FOREIGN KEY (`variant_id`) REFERENCES `Product_Variant` (`id`) ON DELETE SET NULL;

-- Đơn hàng gắn với Người dùng (hoặc NULL nếu khách vãng lai)
ALTER TABLE `Orders` 
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`) ON DELETE SET NULL;

-- Chi tiết đơn hàng gắn với Đơn hàng, Sản phẩm gốc và Cấu hình đã chọn
ALTER TABLE `Order_Details` 
  ADD CONSTRAINT `fk_detail_order` FOREIGN KEY (`order_id`) REFERENCES `Orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detail_product` FOREIGN KEY (`product_id`) REFERENCES `Product` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_detail_variant` FOREIGN KEY (`variant_id`) REFERENCES `Product_Variant` (`id`) ON DELETE SET NULL;

-- ==================== DỮ LIỆU BAN ĐẦU CẦN THIẾT ====================

INSERT INTO `Role` (`id`, `name`) VALUES (1, 'Admin'), (2, 'User');

-- 11. Bảng Giỏ hàng lưu theo tài khoản người dùng (Cart)
CREATE TABLE `Cart` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `variant_id` int NOT NULL,
  `num` int NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_user_variant` (`user_id`, `variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Khóa ngoại liên kết
ALTER TABLE `Cart`
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `Product` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_variant` FOREIGN KEY (`variant_id`) REFERENCES `Product_Variant` (`id`) ON DELETE CASCADE;

  -- 1. BẢNG CÀI ĐẶT HỆ THỐNG & BẬT/TẮT TRANG WEB
CREATE TABLE IF NOT EXISTS `System_Settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL UNIQUE,
  `key_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- 3. BẢNG QUẢN LÝ BANNER SLIDE TRANG CHỦ (Chỉ hiển thị ảnh)
CREATE TABLE IF NOT EXISTS `Banner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_url` varchar(255) NOT NULL COMMENT 'Đường dẫn ảnh banner',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1: Hiện, 0: Ẩn',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 4. BẢNG QUẢN LÝ ĐIỀU KHOẢN & CHÍNH SÁCH
CREATE TABLE IF NOT EXISTS `Policy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `content` longtext NOT NULL,
  `group_type` enum('policy', 'support') DEFAULT 'policy' COMMENT 'policy: Chính sách, support: Hỗ trợ',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1: Hiện, 0: Ẩn',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `Banner` ADD COLUMN `deleted` TINYINT(1) DEFAULT 0 COMMENT '0: Hoạt động, 1: Trong thùng rác';
ALTER TABLE `Policy` ADD COLUMN `deleted` TINYINT(1) DEFAULT 0 COMMENT '0: Hoạt động, 1: Trong thùng rác';
-- Bước 1: Thêm cột slug cho phép tạm thời nhận giá trị NULL (chưa đánh UNIQUE)
ALTER TABLE `Category` 
ADD COLUMN `slug` varchar(150) NULL AFTER `name`;

-- Bước 2: Tự động gán giá trị duy nhất tạm thời dựa theo id cho các bản ghi cũ
UPDATE `Category` 
SET `slug` = CONCAT('danh-muc-', `id`) 
WHERE `slug` IS NULL OR `slug` = '';

-- Bước 3: Đổi cột thành NOT NULL và thêm ràng buộc UNIQUE an toàn
ALTER TABLE `Category` 
MODIFY COLUMN `slug` varchar(150) NOT NULL,
ADD UNIQUE (`slug`);
-- 1. Bảng Brand (Thương hiệu)
ALTER TABLE `Brand` ADD COLUMN `slug` varchar(150) NULL AFTER `name`;
UPDATE `Brand` SET `slug` = CONCAT('thuong-hieu-', `id`) WHERE `slug` IS NULL OR `slug` = '';
ALTER TABLE `Brand` MODIFY COLUMN `slug` varchar(150) NOT NULL, ADD UNIQUE (`slug`);

-- 2. Bảng Product (Sản phẩm)
ALTER TABLE `Product` ADD COLUMN `slug` varchar(350) NULL AFTER `title`;
UPDATE `Product` SET `slug` = CONCAT('san-pham-', `id`) WHERE `slug` IS NULL OR `slug` = '';
ALTER TABLE `Product` MODIFY COLUMN `slug` varchar(350) NOT NULL, ADD UNIQUE (`slug`);
ALTER TABLE `Product` 
ADD COLUMN `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: Bình thường, 1: Nổi bật' AFTER `deleted`;