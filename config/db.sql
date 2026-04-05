DROP DATABASE IF EXISTS hotel_management_db;
CREATE DATABASE hotel_management_db;
USE hotel_management_db;

-- 1. Bảng User: Tài khoản đăng nhập [cite: 2]
CREATE TABLE `User` (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'Customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Bảng User_detail: Chi tiết tài khoản [cite: 4]
CREATE TABLE User_detail (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    full_name VARCHAR(255),
    dob DATE,
    address VARCHAR(500),
    nation VARCHAR(100),
    ID_number VARCHAR(50),
    balance DECIMAL(15, 2) DEFAULT 0.00, -- Chuyển từ MONEY sang DECIMAL [cite: 4]
    status VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES `User`(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Bảng Bank_account: Tài khoản ngân hàng (Không bắt buộc) [cite: 6]
CREATE TABLE Bank_account (
    card_id VARCHAR(50) PRIMARY KEY,
    user_id INT,
    provider VARCHAR(100),
    cardholder_name VARCHAR(255),
    CVV TINYINT,
    expiry_date DATE,
    FOREIGN KEY (user_id) REFERENCES `User`(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 4. Bảng Room_types: Loại phòng [cite: 10]
CREATE TABLE Room_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(15, 2), -- Chuyển từ MONEY sang DECIMAL [cite: 10]
    capacity INT,
    description TEXT,
    image VARCHAR(500)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Bảng Room: Danh sách phòng cụ thể 
CREATE TABLE Room (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT,
    room_number VARCHAR(20) NOT NULL,
    status VARCHAR(50) DEFAULT 'active', -- active / maintenance 
    note TEXT,
    FOREIGN KEY (type_id) REFERENCES Room_types(type_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Bảng Booking: Thông tin đặt phòng [cite: 14]
CREATE TABLE Booking (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    check_in DATETIME,
    check_out DATETIME,
    total_price DECIMAL(15, 2),
    payment_method VARCHAR(100),
    payment_status VARCHAR(50),
    note TEXT,
    status VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES `User`(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Bảng Booking_detail: Chi tiết từng phòng trong đơn đặt [cite: 16]
CREATE TABLE Booking_detail (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    room_id INT,
    price_at_booking DECIMAL(15, 2),
    sub_total DECIMAL(15, 2), -- price_at_booking * số ngày [cite: 16]
    status VARCHAR(50),
    FOREIGN KEY (booking_id) REFERENCES Booking(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES Room(room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

USE hotel_management_db;

-- 1. Chèn dữ liệu vào bảng User (Mật khẩu nên được hash trong thực tế) 
INSERT INTO `User` (email, phone, password, role) VALUES 
('vodathai91thcsduclap@gmail.com', '0772663776', 'admin123', 'Admin'),
('admin@gmail.com', '0987654321', 'admin123', 'Admin'),
('nv@gmail.com', '0901234567', 'nv123', 'Customer');

-- 2. Chèn dữ liệu vào bảng User_detail (Liên kết với User qua user_id) 
-- Giả định ID tự tăng bắt đầu từ 1
INSERT INTO User_detail (user_id, full_name, dob, address, nation, ID_number, balance, status) VALUES 
(1, 'Võ Đạt hải', '2005-01-01', '123 Đường Admin, TP.HCM', 'Việt Nam', '001090123456', 0.00, 'active'),
(2, 'Võ Tấn Bền (Khách)', '1995-05-20', '456 Đường Python, Đà Nẵng', 'Việt Nam', '001095654321', 10500000.00, 'active'),
(3, 'Nguyễn Văn Lợi', '1998-10-15', '789 Đường SQL, Hà Nội', 'Việt Nam', '001098111222', 5450000.00, 'active');

-- 3. Chèn dữ liệu mẫu cho Bank_account (Tùy chọn) [cite: 6]
INSERT INTO Bank_account (card_id, user_id, provider, cardholder_name, CVV, expiry_date) VALUES 
('VCB123456789', 2, 'Vietcombank', 'VO TAN BEN', 123, '2028-12-31'),
('TCB987654321', 3, 'Techcombank', 'NGUYEN VAN LOI', 456, '2027-06-30');