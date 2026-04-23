-- Xóa DB cũ nếu tồn tại để chạy lại từ đầu
USE master;
GO
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'hotel_management_db')
BEGIN
    ALTER DATABASE hotel_management_db SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE hotel_management_db;
END
GO


CREATE DATABASE hotel_management_db;
GO
USE hotel_management_db;
GO

--NHÓM 1: QUẢN LÝ CON NGƯỜI (CUSTOMERS & EMPLOYEES)
-- 1. Bảng Customer: Hồ sơ định danh của tất cả khách hàng (Cũ, mới, online, walk-in)
CREATE TABLE Customer (
    customer_id INT IDENTITY(1,1) PRIMARY KEY,
    full_name NVARCHAR(255) NOT NULL,
    cccd VARCHAR(50) UNIQUE,
    phone VARCHAR(20),
    email NVARCHAR(255),
    address NVARCHAR(500),
    nation NVARCHAR(100) DEFAULT N'Việt Nam'v,
    created_at DATETIME DEFAULT GETDATE()
);

-- 2. Bảng Account: Tài khoản dùng để đăng nhập Web/App (Chỉ khách online mới có)
CREATE TABLE Account (
    account_id INT IDENTITY(1,1) PRIMARY KEY,
    customer_id INT NULL, 
    email NVARCHAR(100) UNIQUE,
    password NVARCHAR(255) NOT NULL,
    status NVARCHAR(50) DEFAULT 'active',
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE
);

CREATE UNIQUE NONCLUSTERED INDEX UQ_Account_CustomerID 
ON Account(customer_id) 
WHERE customer_id IS NOT NULL;
GO

CREATE TABLE Bank_account (
    card_id VARCHAR(50) PRIMARY KEY,
    account_id INT,
    provider NVARCHAR(100),
    cardholder_name NVARCHAR(255),
    CVV SMALLINT, 
    expiry_date DATE,
    FOREIGN KEY (account_id) REFERENCES Account(account_id)  ON DELETE CASCADE
);

-- 3. Bảng Employee: Tài khoản nội bộ dành cho Admin, Quản lý, Lễ tân
CREATE TABLE Employee (
    employee_id INT IDENTITY(1,1) PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password NVARCHAR(255) NOT NULL,
    full_name NVARCHAR(255) NOT NULL,
    role NVARCHAR(50) NOT NULL, -- 'Admin', 'Manager', 'Receptionist'
    status NVARCHAR(20) DEFAULT 'active'
);

-- NHÓM 2: QUẢN LÝ PHÒNG (ROOMS)
-- 4. Bảng Room_types: Loại phòng và cấu hình giá
CREATE TABLE Room_types (
    type_id INT IDENTITY(1,1) PRIMARY KEY,
    name NVARCHAR(255) NOT NULL,
    price_per_hour DECIMAL(15, 2) NOT NULL DEFAULT 0,
    price_per_day DECIMAL(15, 2) NOT NULL DEFAULT 0,
    capacity INT DEFAULT 2,
    description NVARCHAR(MAX)
);

-- 5. Bảng Room: Danh sách phòng vật lý
CREATE TABLE Room (
    room_id INT IDENTITY(1,1) PRIMARY KEY,
    type_id INT,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    status NVARCHAR(50) DEFAULT 'available', -- 'available', 'occupied', 'cleaning', 'maintenance'
    FOREIGN KEY (type_id) REFERENCES Room_types(type_id)
);

-- NHÓM 3: GIAO DỊCH ĐẶT PHÒNG (BOOKING)
-- 6. Bảng Booking: Đơn đặt phòng tổng thể (Hóa đơn chính)
CREATE TABLE Booking (
    booking_id INT IDENTITY(1,1) PRIMARY KEY,
    customer_id INT NOT NULL, -- Người đứng ra đặt/thanh toán
    booking_date DATETIME DEFAULT GETDATE(),
    check_in_planned DATETIME,
    check_out_planned DATETIME,
    total_price DECIMAL(15, 2) DEFAULT 0,
    payment_status NVARCHAR(50) DEFAULT 'unpaid', -- 'unpaid', 'partially_paid', 'paid'
    booking_status NVARCHAR(50) DEFAULT 'pending', -- 'pending', 'confirmed', 'checked-in', 'completed', 'cancelled'
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id)
);

-- 7. Bảng Booking_detail: Chi tiết từng phòng trong đơn đặt
CREATE TABLE Booking_detail (
    detail_id INT IDENTITY(1,1) PRIMARY KEY,
    booking_id INT NOT NULL,
    room_id INT NOT NULL,
    price_at_booking DECIMAL(15, 2), -- Lưu giá lúc đặt để tránh biến động giá sau này
    actual_check_in DATETIME NULL,
    actual_check_out DATETIME NULL,
    FOREIGN KEY (booking_id) REFERENCES Booking(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES Room(room_id)
);

-- 8. Bảng Booking_guests: Danh sách người ở thực tế trong từng phòng (Khai báo tạm trú)
CREATE TABLE Booking_guests (
    guest_id INT IDENTITY(1,1) PRIMARY KEY,
    detail_id INT NOT NULL,   -- Ở phòng nào
    customer_id INT NOT NULL, -- Ai ở
    is_representative BIT DEFAULT 0, -- Có phải người đại diện phòng không
    FOREIGN KEY (detail_id) REFERENCES Booking_detail(detail_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id)
);
GO

-- 1. Thêm nhân viên
INSERT INTO Employee (username, password, full_name, role) VALUES 
('admin', '123', N'Nguyễn Công Thành', 'Admin');

-- 2. Tạo loại phòng (Đa dạng hạng phòng, giá và sức chứa)
INSERT INTO Room_types (name, price_per_hour, price_per_day, capacity, description) VALUES 
(N'Standard Single', 100000, 500000, 1, N'Phòng tiêu chuẩn 1 giường đơn dành cho người đi công tác.'),
(N'Superior Double', 150000, 800000, 2, N'Phòng rộng rãi với 1 giường đôi lớn, view thành phố.'),
(N'Deluxe Ocean View', 250000, 1500000, 2, N'Phòng sang trọng hướng biển, ban công riêng và bồn tắm.'),
(N'Family Suite', 350000, 2500000, 4, N'Phòng gia đình 2 phòng ngủ liên thông, không gian sinh hoạt chung.'),
(N'Presidential Suite', 500000, 5000000, 4, N'Phòng Tổng thống cao cấp nhất, nội thất dát vàng, dịch vụ quản gia.');

-- 3. Tạo danh sách phòng vật lý (Toàn bộ trạng thái là Sẵn sàng - available)
INSERT INTO Room (room_number, type_id, status) VALUES 
('101', 1, 'available'), ('102', 1, 'available'), ('103', 1, 'available'), ('104', 1, 'available'),
('201', 2, 'available'), ('202', 2, 'available'), ('203', 2, 'available'), ('204', 2, 'available'),
('301', 3, 'available'), ('302', 3, 'available'), ('303', 3, 'available'),
('401', 4, 'available'), ('402', 4, 'available'),
('501', 5, 'available');
GO