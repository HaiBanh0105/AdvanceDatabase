-- Script dành cho MongoDB Shell (mongosh)
-- Chạy lệnh: mongosh < mongodb.sql

use hotel_management_db;

db.HotelCatalog.drop();

db.HotelCatalog.insertMany([
  {
    "hotel_id": 1,
    "name": "Ocean View Resort",
    "description": "Khu nghỉ dưỡng 5 sao ven biển tuyệt đẹp với góc nhìn ra đại dương bao la.",
    "contact": {
      "phone": "+84 123 456 789",
      "email": "contact@oceanview.com"
    },
    "amenities": ["Free WiFi", "Infinity Pool", "Spa & Massage", "Gym"],
    "images": [
      {"url": "/img/ocean_1.jpg", "caption": "Hồ bơi vô cực", "is_cover": true},
      {"url": "/img/ocean_2.jpg", "caption": "Sảnh chờ", "is_cover": false}
    ],
    "last_updated": new Date()
  },
  {
    "hotel_id": 2,
    "name": "Mountain Retreat Hotel",
    "description": "Nghỉ dưỡng trên vùng cao nguyên mát mẻ, hòa mình vào thiên nhiên.",
    "contact": {
      "phone": "+84 987 654 321",
      "email": "booking@mountainretreat.com"
    },
    "amenities": ["Free WiFi", "Heated Pool", "BBQ Area"],
    "images": [
      {"url": "/img/mountain_1.jpg", "caption": "Toàn cảnh resort", "is_cover": true}
    ],
    "last_updated": new Date()
  }
]);