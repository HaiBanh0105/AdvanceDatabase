// --- HIỆU ỨNG ẢNH PARALLAX CHO HERO SECTION ---
    const heroContainer = document.getElementById('heroContainer');
    const heroImage = document.getElementById('heroImage');

    if (heroContainer && heroImage) {
        heroContainer.addEventListener('mousemove', (e) => {
            // Lấy chiều rộng và chiều cao của vùng chứa
            const width = heroContainer.offsetWidth;
            const height = heroContainer.offsetHeight;

            // Tính toán tọa độ chuột so với tâm của vùng chứa
            const mouseX = e.clientX - (width / 2);
            const mouseY = e.clientY - (height / 2);

            // Chia độ nhạy (Số càng lớn, ảnh di chuyển càng ít. Khuyến nghị: 30-50)
            const moveX = (mouseX / 40);
            const moveY = (mouseY / 40);

            // Áp dụng di chuyển (translate) kết hợp với phóng to (scale)
            heroImage.style.transform = `translate(${moveX}px, ${moveY}px) scale(1.1)`;
        });

        // Reset ảnh về vị trí trung tâm khi chuột rời khỏi vùng Hero
        heroContainer.addEventListener('mouseleave', () => {
            // Thêm thời gian mượt mà khi trả về vị trí cũ
            heroImage.style.transitionDuration = '500ms';
            heroImage.style.transform = `translate(0px, 0px) scale(1.1)`;

            // Gỡ bỏ duration dài sau khi transition xong để lúc hover lại không bị delay
            setTimeout(() => {
                heroImage.style.transitionDuration = '100ms';
            }, 500);
        });
    }
    // --- HIỆU ỨNG GÕ CHỮ (TYPEWRITER) ---
    document.addEventListener('DOMContentLoaded', () => {
        const typeSpeed = 80;

        // 1. XỬ LÝ PHẦN HERO (Gõ ngay khi load trang)
        const titleElement = document.getElementById('heroTitle');
        const descElement = document.getElementById('heroDesc');
        const titleText = "Nơi nghỉ dưỡng hoàn hảo";
        const descText = "Trải nghiệm dịch vụ đẳng cấp 5 sao với không gian sang trọng và tiện nghi hiện đại.";
        
        let i = 0; let j = 0;

        function typeTitle() {
            if (i < titleText.length) {
                titleElement.innerHTML += titleText.charAt(i);
                i++;
                setTimeout(typeTitle, typeSpeed);
            } else {
                setTimeout(typeDesc, 300);
            }
        }

        function typeDesc() {
            if (j < descText.length) {
                descElement.innerHTML += descText.charAt(j);
                j++;
                setTimeout(typeDesc, typeSpeed - 30);
            }
        }
        
        if (titleElement) setTimeout(typeTitle, 500);

        // 2. XỬ LÝ PHẦN REVIEW (Chỉ gõ khi cuộn chuột tới)
        const reviewElement = document.getElementById('reviewTitle');
        const subreviewElement = document.getElementById('subreviewTitle');
        const reviewText = "Khách hàng nói gì về chúng tôi?";
        const subreviewText = "Hàng ngàn khách hàng đã trải nghiệm và hài lòng với dịch vụ tại Grand Horizon. Hãy chia sẻ cảm nhận của bạn nhé!";
        
        let k = 0; let l = 0;
        let reviewStarted = false; // Biến cờ để đảm bảo chỉ gõ 1 lần

        function typeReviewTitle() {
            if (k < reviewText.length) {
                reviewElement.innerHTML += reviewText.charAt(k);
                k++;
                setTimeout(typeReviewTitle, typeSpeed);
            } else {
                setTimeout(typeSubreviewTitle, 300);
            }
        }

        function typeSubreviewTitle() {
            if (l < subreviewText.length) {
                subreviewElement.innerHTML += subreviewText.charAt(l);
                l++;
                setTimeout(typeSubreviewTitle, typeSpeed - 40); // Gõ nhanh hơn một xíu vì câu dài
            }
        }

        // Cài đặt "Lính gác" (Observer) chờ cuộn tới vùng Review
        if (reviewElement) {
            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting && !reviewStarted) {
                    reviewStarted = true; // Đánh dấu là đã bắt đầu gõ
                    setTimeout(typeReviewTitle, 200); // Nghỉ 0.2s rồi gõ
                }
            }, { threshold: 0.5 }); // Kích hoạt khi thấy được 50% khối tiêu đề

            observer.observe(reviewElement);
        }
    });
    // --- HIỆU ỨNG SCROLL REVEAL CHO THẺ PHÒNG ---
    document.addEventListener('DOMContentLoaded', () => {
        const cards = document.querySelectorAll('.reveal-card');

        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1 // Kích hoạt khi cuộn thấy 10% thẻ
        };

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    // Thêm delay nối tiếp nhau (cascade) để thẻ hiện ra lần lượt 1-2-3
                    setTimeout(() => {
                        entry.target.classList.remove('opacity-0', 'translate-y-10');
                        entry.target.classList.add('opacity-100', 'translate-y-0');
                        entry.target.style.transition =
                            'all 0.8s cubic-bezier(0.16, 1, 0.3, 1)';
                    }, index * 150); // Cách nhau 150ms

                    observer.unobserve(entry.target); // Chỉ chạy 1 lần
                }
            });
        }, observerOptions);

        cards.forEach(card => observer.observe(card));
    });
    document.addEventListener('DOMContentLoaded', () => {
        // 1. Cấu hình Flatpickr sang Tiếng Việt
        flatpickr.localize(flatpickr.l10ns.vn);

        // 2. Khởi tạo lịch Ngày Trả Phòng trước (để lát nữa Ngày Nhận Phòng có thể gọi đến nó)
        const checkOutPicker = flatpickr("#checkOutDate", {
            dateFormat: "Y-m-d", // Định dạng gửi lên server (Database)
            altInput: true, // Kích hoạt ô hiển thị phụ cho người dùng
            altFormat: "d/m/Y", // Định dạng hiển thị ra màn hình (VD: 25/12/2026)
            minDate: new Date().fp_incr(1), // Mặc định ít nhất là ngày mai
            disableMobile: "true" // Vô hiệu hóa lịch xấu của điện thoại, ép dùng giao diện này
        });

        // 3. Khởi tạo lịch Ngày Nhận Phòng
        const checkInPicker = flatpickr("#checkInDate", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            minDate: "today", // Không cho chọn ngày trong quá khứ
            disableMobile: "true",
            onChange: function(selectedDates, dateStr, instance) {
                // MAGIC: Khi khách đổi ngày nhận phòng, tự động ép ngày trả phòng phải cách ít nhất 1 ngày!
                if (selectedDates.length > 0) {
                    let minCheckOut = new Date(selectedDates[0]);
                    minCheckOut.setDate(minCheckOut.getDate() + 1);

                    checkOutPicker.set('minDate', minCheckOut);

                    // Nếu ngày trả phòng hiện tại đang nhỏ hơn ngày nhận phòng, xóa nó đi
                    if (checkOutPicker.selectedDates[0] <= selectedDates[0]) {
                        checkOutPicker.clear();
                    }
                }
            }
        });
    });

// --- LIVE CHAT WIDGET ---
document.addEventListener('DOMContentLoaded', () => {
    const chatWindow = document.getElementById('chatWindow');
    const chatInput = document.getElementById('chatInput');
    const sendChatBtn = document.getElementById('sendChatBtn');
    const chatMessages = document.getElementById('chatMessages');
    const chatNotifBadge = document.getElementById('chatNotifBadge');
    const chatWidget = document.getElementById('chatWidget');
    const chatBubble = document.getElementById('chatBubble');

    let chatInterval = null;
    let lastMessageCount = 0;

    // Hàm bật/tắt cửa sổ Chat (Gắn vào window để HTML có thể gọi)
    window.toggleChat = function() {
        chatWindow.classList.toggle('hidden');
        chatWindow.classList.toggle('flex');
        if (!chatWindow.classList.contains('hidden')) {
            chatNotifBadge.classList.add('hidden'); // Ẩn thông báo khi mở chat
            chatNotifBadge.classList.remove('animate-pulse', 'shadow-[0_0_10px_rgba(239,68,68,1)]');
            
            // Cuộn xuống tin nhắn cuối cùng ngay khi mở cửa sổ chat
            setTimeout(() => {
                if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 50);

            loadMessages();
            chatInterval = setInterval(loadMessages, 3000); // Làm mới mỗi 3s khi đang mở
            setTimeout(() => chatInput.focus(), 100);
        } else {
            clearInterval(chatInterval); // Dừng làm mới liên tục để tiết kiệm tài nguyên
        }
    };

    // Hàm tải danh sách tin nhắn từ Server
    function loadMessages() {
        const isOpen = chatWindow && !chatWindow.classList.contains('hidden') ? 1 : 0;
        fetch(`../actions/process_chat.php?action=fetch&is_open=${isOpen}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Chỉ render lại nếu có tin nhắn mới
                    if (data.data.length > lastMessageCount) {
                        lastMessageCount = data.data.length;
                        renderMessages(data.data);
                    }
                    // Hiện chấm đỏ phát sáng nếu có tin mới từ Admin mà khung chat đang ẩn
                    if (data.unread_admin_count > 0 && !isOpen) {
                        chatNotifBadge.classList.remove('hidden');
                        chatNotifBadge.classList.add('animate-pulse', 'shadow-[0_0_10px_rgba(239,68,68,1)]');
                    } else if (isOpen) {
                        chatNotifBadge.classList.add('hidden');
                        chatNotifBadge.classList.remove('animate-pulse', 'shadow-[0_0_10px_rgba(239,68,68,1)]');
                    }
                }
            })
            .catch(err => console.error("Chat error: ", err));
    }

    // Hàm vẽ giao diện tin nhắn
    function renderMessages(messages) {
        if (!chatMessages) return;
        chatMessages.innerHTML = '<div class="text-center text-[10px] text-slate-400 font-bold uppercase my-2">Bắt đầu cuộc trò chuyện</div>';
        messages.forEach(m => {
            const isUser = !m.is_admin;
            const align = isUser ? 'self-end' : 'self-start';
            const bg = isUser ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-700';
            const timeAlign = isUser ? 'text-right pr-1' : 'text-left pl-1';
            
            chatMessages.innerHTML += `
            <div class="max-w-[80%] ${align}">
                <div class="px-4 py-2.5 rounded-2xl ${bg} text-sm inline-block break-words shadow-sm">${m.message}</div>
                <div class="text-[9px] text-slate-400 mt-1 font-bold ${timeAlign}">${m.time}</div>
            </div>`;
        });
        // Tự động cuộn xuống tin nhắn mới nhất
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Hàm gửi tin nhắn
    function sendMessage() {
        const msg = chatInput.value.trim();
        if (!msg) return;

        const formData = new FormData();
        formData.append('action', 'send');
        formData.append('message', msg);

        chatInput.value = ''; // Xóa trắng khung nhập
        
        fetch('../actions/process_chat.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') loadMessages();
        });
    }

    if (sendChatBtn) sendChatBtn.addEventListener('click', sendMessage);
    if (chatInput) {
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
    }

    // Ngầm kiểm tra tin nhắn mới mỗi 5s để hiện chấm đỏ dù khách đang không mở khung chat
    setInterval(() => {
        if (chatWindow && chatWindow.classList.contains('hidden')) {
            loadMessages();
        }
    }, 5000);
    
    // Tải tin nhắn lần đầu khi tải trang
    loadMessages();

    // --- CLICK EVENT CHO CHAT WIDGET ---
    if (chatBubble) {
        chatBubble.addEventListener('click', (e) => {
            window.toggleChat();
        });
    }
});