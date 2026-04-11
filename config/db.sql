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

-- 1. Bảng User: Tài khoản đăng nhập
CREATE TABLE [User] (
    user_id INT IDENTITY(1,1) PRIMARY KEY, -- IDENTITY thay cho AUTO_INCREMENT
    email NVARCHAR(255) NOT NULL UNIQUE,   -- NVARCHAR để hỗ trợ Unicode
    phone VARCHAR(20),
    password NVARCHAR(255) NOT NULL,
    role NVARCHAR(50) DEFAULT 'Customer',
    created_at DATETIME DEFAULT GETDATE()
);

-- 2. Bảng User_detail: Chi tiết tài khoản
CREATE TABLE User_detail (
    detail_id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT,
    full_name NVARCHAR(255),
    dob DATE,
    address NVARCHAR(500),
    nation NVARCHAR(100),
    ID_number VARCHAR(50),
    balance DECIMAL(15, 2) DEFAULT 0.00,
    status NVARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES [User](user_id) ON DELETE CASCADE
);

-- 3. Bảng Bank_account
CREATE TABLE Bank_account (
    card_id VARCHAR(50) PRIMARY KEY,
    user_id INT,
    provider NVARCHAR(100),
    cardholder_name NVARCHAR(255),
    CVV SMALLINT, -- SQL Server TINYINT chỉ từ 0-255, dùng SMALLINT cho an toàn
    expiry_date DATE,
    FOREIGN KEY (user_id) REFERENCES [User](user_id) ON DELETE CASCADE
);

-- 4. Bảng Room_types: Loại phòng
CREATE TABLE Room_types (
    type_id INT IDENTITY(1,1) PRIMARY KEY,
    name NVARCHAR(255) NOT NULL,
    price_per_hour DECIMAL(15, 2) NOT NULL DEFAULT 0,
    price_per_day DECIMAL(15, 2) NOT NULL DEFAULT 0,
    capacity INT,
    description NVARCHAR(MAX), -- NVARCHAR(MAX) thay cho TEXT
    image NVARCHAR(500)
);

-- 5. Bảng Room: Danh sách phòng cụ thể 
CREATE TABLE Room (
    room_id INT IDENTITY(1,1) PRIMARY KEY,
    type_id INT,
    room_number VARCHAR(20) NOT NULL,
    status NVARCHAR(50) DEFAULT 'active', -- active / maintenance 
    note NVARCHAR(MAX),
    FOREIGN KEY (type_id) REFERENCES Room_types(type_id) ON DELETE CASCADE
);

-- 6. Bảng Booking: Thông tin đặt phòng
CREATE TABLE Booking (
    booking_id INT IDENTITY(1,1) PRIMARY KEY,
    rental_type VARCHAR(20) DEFAULT 'daily', -- 'hourly' hoặc 'daily'
    user_id INT,
    booking_date DATETIME DEFAULT GETDATE(), -- GETDATE() thay cho CURRENT_TIMESTAMP
    check_in DATETIME,
    check_out DATETIME,
    total_price DECIMAL(15, 2),
    payment_method NVARCHAR(100),
    payment_status NVARCHAR(50),
    note NVARCHAR(MAX),

    status NVARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES [User](user_id) ON DELETE CASCADE
);

-- 7. Bảng Booking_detail: Chi tiết từng phòng
CREATE TABLE Booking_detail (
    detail_id INT IDENTITY(1,1) PRIMARY KEY,
    booking_id INT,
    room_id INT,
    price_at_booking DECIMAL(15, 2),
    sub_total DECIMAL(15, 2),
    status NVARCHAR(50),
    FOREIGN KEY (booking_id) REFERENCES Booking(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES Room(room_id)
);

-- 8. Bảng Booking_guests: Danh sách khách lưu trú thực tế trong từng phòng
CREATE TABLE Booking_guests (
    guest_id INT IDENTITY(1,1) PRIMARY KEY,
    booking_id INT,              
    detail_id INT,             
    full_name NVARCHAR(255) NOT NULL,     
    cccd VARCHAR(50) NOT NULL,                               
    phone VARCHAR(20),
    is_representative BIT DEFAULT 0, 
    
    FOREIGN KEY (booking_id) REFERENCES Booking(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (detail_id) REFERENCES Booking_detail(detail_id)
);
GO

-- CHÈN DỮ LIỆU MẪU
INSERT INTO [User] (email, phone, password, role) VALUES 
(N'vodathai91thcsduclap@gmail.com', '0772663776', N'admin123', N'Admin'),
(N'admin@gmail.com', '0987654321', N'admin123', N'Admin'),
(N'nv@gmail.com', '0901234567', N'nv123', N'Customer');

INSERT INTO User_detail (user_id, full_name, dob, address, nation, ID_number, balance, status) VALUES 
(1, N'Võ Đạt hải', '2005-01-01', N'123 Đường Admin, TP.HCM', N'Việt Nam', '001090123456', 0.00, N'active'),
(2, N'Võ Tấn Bền (Khách)', '1995-05-20', N'456 Đường Python, Đà Nẵng', N'Việt Nam', '001095654321', 10500000.00, N'active'),
(3, N'Nguyễn Văn Lợi', '1998-10-15', N'789 Đường SQL, Hà Nội', N'Việt Nam', '001098111222', 5450000.00, N'active');
GO

--Cập nhật dữ liệu mẫu cho Room_types (Nếu bạn chạy từ đầu)
INSERT INTO Room_types (name, price_per_hour, price_per_day, capacity, description) VALUES 
(N'Phòng Standard', 100000, 500000, 2, N'Tiêu chuẩn'),
(N'Phòng VIP', 200000, 1000000, 2, N'Có bồn tắm');