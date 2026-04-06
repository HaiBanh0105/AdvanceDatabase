-- Xóa DB cũ nếu tồn tại để chạy lại từ đầu
DROP DATABASE IF EXISTS hotel_management_db;
CREATE DATABASE hotel_management_db;
USE hotel_management_db;

-- 1. Bảng User
CREATE TABLE `User` (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'Customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Bảng User_detail
CREATE TABLE User_detail (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    full_name VARCHAR(255),
    dob DATE,
    address VARCHAR(500),
    nation VARCHAR(100),
    ID_number VARCHAR(50),
    balance DECIMAL(15, 2) DEFAULT 0.00,
    status VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES `User`(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Bảng Bank_account
CREATE TABLE Bank_account (
    card_id VARCHAR(50) PRIMARY KEY,
    user_id INT,
    provider VARCHAR(100),
    cardholder_name VARCHAR(255),
    CVV SMALLINT,
    expiry_date DATE,
    FOREIGN KEY (user_id) REFERENCES `User`(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Bảng Room_types
CREATE TABLE Room_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(15, 2),
    capacity INT,
    description TEXT,
    image VARCHAR(500)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Bảng Room
CREATE TABLE Room (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT,
    room_number VARCHAR(20) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    note TEXT,
    FOREIGN KEY (type_id) REFERENCES Room_types(type_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Bảng Booking (đã gộp guest info)
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

    -- Guest vãng lai
    guest_name VARCHAR(255),
    guest_phone VARCHAR(20),
    guest_cccd VARCHAR(50),

    FOREIGN KEY (user_id) REFERENCES `User`(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Bảng Booking_detail
CREATE TABLE Booking_detail (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    room_id INT,
    price_at_booking DECIMAL(15, 2),
    sub_total DECIMAL(15, 2),
    status VARCHAR(50),
    FOREIGN KEY (booking_id) REFERENCES Booking(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES Room(room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================
-- INSERT DỮ LIỆU MẪU
-- ========================

-- User
INSERT INTO `User` (email, phone, password, role) VALUES 
('admin@gmail.com', '0987654321', 'admin123', 'Admin'),
('customer1@gmail.com', '0901111111', '123456', 'Customer'),
('customer2@gmail.com', '0902222222', '123456', 'Customer');

-- User_detail
INSERT INTO User_detail (user_id, full_name, dob, address, nation, ID_number, balance, status) VALUES 
(1, 'Admin System', '1990-01-01', 'TP.HCM', 'Việt Nam', '001000000001', 0, 'active'),
(2, 'Nguyễn Văn A', '1995-05-20', 'Hà Nội', 'Việt Nam', '001000000002', 5000000, 'active'),
(3, 'Trần Thị B', '1998-10-15', 'Đà Nẵng', 'Việt Nam', '001000000003', 3000000, 'active');

-- Bank_account
INSERT INTO Bank_account VALUES
('1111222233334444', 2, 'Vietcombank', 'Nguyen Van A', 123, '2028-12-31'),
('5555666677778888', 3, 'ACB', 'Tran Thi B', 456, '2027-06-30');

-- Room_types
INSERT INTO Room_types (name, price, capacity, description, image) VALUES
('Standard', 300000, 2, 'Phòng tiêu chuẩn', 'standard.jpg'),
('Deluxe', 500000, 3, 'Phòng cao cấp', 'deluxe.jpg'),
('Suite', 800000, 4, 'Phòng VIP', 'suite.jpg');

-- Room
INSERT INTO Room (type_id, room_number, status) VALUES
(1, '101', 'available'),
(1, '102', 'available'),
(2, '201', 'available'),
(2, '202', 'maintenance'),
(3, '301', 'available');

-- Booking (có cả user và guest)
INSERT INTO Booking (user_id, check_in, check_out, total_price, payment_method, payment_status, status) VALUES
(2, '2026-04-10 14:00:00', '2026-04-12 12:00:00', 600000, 'Cash', 'Paid', 'Confirmed');

-- Booking cho khách vãng lai
INSERT INTO Booking (guest_name, guest_phone, guest_cccd, check_in, check_out, total_price, payment_method, payment_status, status) VALUES
('Lê Văn C', '0909999999', '001099999999', '2026-04-15 14:00:00', '2026-04-16 12:00:00', 300000, 'Cash', 'Unpaid', 'Pending');

-- Booking_detail
INSERT INTO Booking_detail (booking_id, room_id, price_at_booking, sub_total, status) VALUES
(1, 1, 300000, 600000, 'Booked'),
(2, 2, 300000, 300000, 'Booked');