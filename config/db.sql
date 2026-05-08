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
    dob DATE,
    address NVARCHAR(500),
    nation NVARCHAR(100) DEFAULT N'Việt Nam',
    created_at DATETIME DEFAULT GETDATE()
);

-- 2. Bảng Account: Tài khoản dùng để đăng nhập Web/App (Chỉ khách online mới có)
CREATE TABLE Account (
    account_id INT IDENTITY(1,1) PRIMARY KEY,
    customer_id INT NULL, 
    email NVARCHAR(100) UNIQUE,
    password NVARCHAR(255) NOT NULL,
    balance DECIMAL(15, 2) DEFAULT 0,
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

-- TRIGGER
CREATE TRIGGER trg_SyncRoomStatus
ON Booking
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    -- Chỉ chạy Trigger khi có sự thay đổi trên cột booking_status
    IF UPDATE(booking_status)
    BEGIN
        -- Đổi sang 'occupied' khi Khách Online đến Check-in
        UPDATE r SET r.status = 'occupied'
        FROM Room r
        JOIN Booking_detail bd ON r.room_id = bd.room_id
        JOIN inserted i ON bd.booking_id = i.booking_id
        JOIN deleted d ON bd.booking_id = d.booking_id
        WHERE i.booking_status = 'checked-in' AND d.booking_status <> 'checked-in';

        -- Đổi sang 'available' khi Hủy đơn (cancelled)
        UPDATE r SET r.status = 'available'
        FROM Room r
        JOIN Booking_detail bd ON r.room_id = bd.room_id
        JOIN inserted i ON bd.booking_id = i.booking_id
        JOIN deleted d ON bd.booking_id = d.booking_id
        WHERE i.booking_status = 'cancelled' AND d.booking_status <> 'cancelled';

        -- Đổi sang 'cleaning' khi Trả phòng (completed)
        UPDATE r SET r.status = 'cleaning'
        FROM Room r
        JOIN Booking_detail bd ON r.room_id = bd.room_id
        JOIN inserted i ON bd.booking_id = i.booking_id
        JOIN deleted d ON bd.booking_id = d.booking_id
        WHERE i.booking_status = 'completed' AND d.booking_status <> 'completed';
    END
END;
GO

-- PROCEDURE
CREATE PROCEDURE sp_ProcessCheckout
    @booking_id INT,
    @actual_checkout DATETIME,
    @final_total DECIMAL(15,2)
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        BEGIN TRANSACTION;
        -- Cập nhật hóa đơn chính
        UPDATE Booking SET booking_status = 'completed', total_price = @final_total, payment_status = 'paid' 
        WHERE booking_id = @booking_id;

        -- Cập nhật chi tiết giờ ra
        UPDATE Booking_detail SET actual_check_out = @actual_checkout 
        WHERE booking_id = @booking_id;

        -- Không cần cập nhật Room vì Trigger trg_SyncRoomStatus ở trên sẽ tự động được kích hoạt
        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        THROW;
    END CATCH
END;
GO

-- PROCEDURE: DUYỆT ĐƠN HOẶC CHECK-IN (CÓ TRỪ CỌC)
CREATE PROCEDURE sp_UpdateBookingStatus
    @booking_id INT,
    @new_status NVARCHAR(50),
    @deduct_amount DECIMAL(15,2) = 0
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        BEGIN TRANSACTION;
        
        -- Cập nhật trạng thái
        UPDATE Booking SET booking_status = @new_status WHERE booking_id = @booking_id;

        -- Nếu là checked-in thì tự động cập nhật giờ vào thực tế
        IF @new_status = 'checked-in'
        BEGIN
            UPDATE Booking_detail SET actual_check_in = GETDATE() WHERE booking_id = @booking_id;
        END

        -- Trừ tiền cọc trong ví tài khoản
        IF @deduct_amount > 0
        BEGIN
            DECLARE @customer_id INT;
            SELECT @customer_id = customer_id FROM Booking WHERE booking_id = @booking_id;
            UPDATE Account SET balance = balance - @deduct_amount WHERE customer_id = @customer_id;
        END

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        THROW;
    END CATCH
END;
GO

-- PROCEDURE: HỦY ĐƠN, PHẠT TIỀN VÀ HOÀN TIỀN
CREATE PROCEDURE sp_CancelBooking
    @booking_id INT,
    @refund_amount DECIMAL(15,2),
    @new_total_price DECIMAL(15,2)
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        BEGIN TRANSACTION;
        
        -- Chuyển trạng thái hủy và cập nhật giá tiền thành phí phạt (hoặc 0đ)
        UPDATE Booking SET booking_status = 'cancelled', total_price = @new_total_price WHERE booking_id = @booking_id;

        -- Hoàn tiền vào ví
        IF @refund_amount > 0
        BEGIN
            DECLARE @cancel_customer_id INT;
            SELECT @cancel_customer_id = customer_id FROM Booking WHERE booking_id = @booking_id;
            UPDATE Account SET balance = balance + @refund_amount WHERE customer_id = @cancel_customer_id;
        END

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        THROW;
    END CATCH
END;
GO

--VIEW
CREATE VIEW vw_TopRoomsByRevenue AS
WITH RoomRevenue AS (
    SELECT 
        r.room_id, 
        r.room_number,
        rt.name AS type_name,
        ISNULL(SUM(b.total_price), 0) AS total_revenue
    FROM Room r
    JOIN Room_types rt ON r.type_id = rt.type_id
    JOIN Booking_detail bd ON r.room_id = bd.room_id
    JOIN Booking b ON bd.booking_id = b.booking_id
    WHERE b.booking_status = 'completed'
    GROUP BY r.room_id, r.room_number, rt.name
),
RankedRooms AS (
    SELECT *, 
           DENSE_RANK() OVER (ORDER BY total_revenue DESC) AS revenue_rank, -- Hàm DENSE_RANK (Xếp hạng khít - Khuyên dùng)
           RANK() OVER (ORDER BY total_revenue DESC) AS test_rank,          -- Hàm RANK (Xếp hạng nhảy cóc)
           ROW_NUMBER() OVER (ORDER BY total_revenue DESC) AS test_row_num  -- Hàm ROW_NUMBER (Chỉ đánh số thứ tự 1,2,3...)
    FROM RoomRevenue
)
SELECT * FROM RankedRooms;
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