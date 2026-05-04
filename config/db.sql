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

-- 4. Bảng Hotels (Áp dụng Global Hotels)
CREATE TABLE Hotels (
    hotel_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Bảng Room_types
CREATE TABLE Room_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(15, 2),
    capacity INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Bảng Room
CREATE TABLE Room (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    type_id INT,
    room_number VARCHAR(20) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    note TEXT,
    FOREIGN KEY (hotel_id) REFERENCES Hotels(hotel_id) ON DELETE CASCADE,
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

-- Hotels
INSERT INTO Hotels (name, city, status) VALUES
('Ocean View Resort', 'Nha Trang', 'active'),
('Mountain Retreat Hotel', 'Da Lat', 'active');

-- Room_types
INSERT INTO Room_types (name, price, capacity) VALUES
('Standard', 300000, 2),
('Deluxe', 500000, 3),
('Suite', 800000, 4);

-- Room
INSERT INTO Room (hotel_id, type_id, room_number, status) VALUES
(1, 1, '101', 'active'),
(1, 1, '102', 'active'),
(1, 2, '201', 'active'),
(2, 2, '202', 'maintenance'),
(2, 3, '301', 'active');

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

-- ==========================================
-- ADVANCED DB FEATURES (Triggers, Views)
-- ==========================================

-- Bảng Price_Change_Log (Dành cho Trigger)
CREATE TABLE Price_Change_Log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT,
    old_price DECIMAL(15, 2),
    new_price DECIMAL(15, 2),
    change_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES Room_types(type_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Trigger: Ghi log khi giá phòng thay đổi trên 50%
DELIMITER $$
CREATE TRIGGER check_price_change
AFTER UPDATE ON Room_types
FOR EACH ROW
BEGIN
    DECLARE price_diff DECIMAL(15,2);
    DECLARE percentage_change DECIMAL(5,2);
    
    SET price_diff = ABS(NEW.price - OLD.price);
    IF OLD.price > 0 THEN
        SET percentage_change = (price_diff / OLD.price) * 100;
        IF percentage_change > 50 THEN
            INSERT INTO Price_Change_Log (type_id, old_price, new_price) 
            VALUES (NEW.type_id, OLD.price, NEW.price);
        END IF;
    END IF;
END$$
DELIMITER ;

-- View: Sử dụng SQL Window Functions tìm Top 3 phòng doanh thu cao nhất theo từng Khách sạn
CREATE VIEW vw_top_revenue_rooms AS
WITH RoomRevenue AS (
    SELECT 
        r.hotel_id, h.name AS hotel_name, r.room_id, r.room_number,
        SUM(bd.sub_total) AS total_revenue
    FROM Booking_detail bd
    JOIN Room r ON bd.room_id = r.room_id
    JOIN Hotels h ON r.hotel_id = h.hotel_id
    JOIN Booking b ON bd.booking_id = b.booking_id
    WHERE b.status = 'completed' OR b.status = 'Confirmed'
    GROUP BY r.hotel_id, h.name, r.room_id, r.room_number
),
RankedRooms AS (
    SELECT *, RANK() OVER (PARTITION BY hotel_id ORDER BY total_revenue DESC) as revenue_rank
    FROM RoomRevenue
)
SELECT * FROM RankedRooms WHERE revenue_rank <= 3;

-- ==========================================
-- STORED PROCEDURE (Xử lý nghiệp vụ phức tạp)
-- ==========================================
-- Stored Procedure: Hủy đặt phòng an toàn và hoàn trả phòng
DELIMITER $$
CREATE PROCEDURE sp_cancel_booking(IN p_booking_id INT)
BEGIN
    DECLARE v_status VARCHAR(50);
    
    -- Khóa dòng dữ liệu để tránh update đồng thời
    SELECT status INTO v_status FROM Booking WHERE booking_id = p_booking_id FOR UPDATE;
    
    IF v_status IN ('Pending', 'Confirmed', 'Booked') THEN
        -- Cập nhật trạng thái Booking và Booking_detail
        UPDATE Booking SET status = 'cancelled' WHERE booking_id = p_booking_id;
        UPDATE Booking_detail SET status = 'cancelled' WHERE booking_id = p_booking_id;
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chỉ có thể hủy đơn ở trạng thái Pending hoặc Confirmed.';
    END IF;
END$$
DELIMITER ;