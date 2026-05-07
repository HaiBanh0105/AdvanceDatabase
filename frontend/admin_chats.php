<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hỗ trợ khách hàng - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-slate-50 flex h-screen overflow-hidden">
    <?php include 'sidebar_admin.php'; ?>

    <main class="flex-1 flex overflow-hidden p-4 md:p-8 gap-6">

        <!-- Danh sách Người dùng nhắn tin -->
        <div
            class="w-1/3 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-hidden shrink-0">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h2 class="font-bold text-slate-800 text-lg"><i class="fa-solid fa-inbox text-indigo-500 mr-2"></i>Hộp
                    thư đến</h2>
            </div>
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100" id="userList">
                <div class="p-6 text-center text-slate-400 italic text-sm">Đang tải danh sách...</div>
            </div>
        </div>

        <!-- Khu vực Chat (Bên phải) -->
        <div class="flex-1 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-hidden hidden"
            id="chatArea">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-black">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-800 text-lg leading-tight" id="chatUserName">Tên khách hàng</h2>
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold" id="chatSessionId">ID
                        </p>
                    </div>
                </div>
            </div>

            <!-- Nội dung tin nhắn -->
            <div class="flex-1 p-6 overflow-y-auto bg-slate-50 space-y-4 flex flex-col" id="adminChatMessages"></div>

            <!-- Thanh nhập tin nhắn -->
            <div class="p-4 border-t border-slate-100 bg-white flex gap-3 items-center">
                <input type="hidden" id="currentSessionId">
                <input type="text" id="adminChatInput"
                    class="flex-1 px-5 py-3.5 bg-slate-100 rounded-xl border border-transparent focus:bg-white focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100 outline-none text-sm transition"
                    placeholder="Nhập tin nhắn trả lời khách hàng...">
                <button id="adminSendChat"
                    class="bg-indigo-600 text-white px-6 py-3.5 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 shrink-0"><i
                        class="fa-solid fa-paper-plane mr-2"></i>Gửi</button>
            </div>
        </div>

        <!-- Box rỗng khi chưa chọn người dùng -->
        <div class="flex-1 bg-slate-100 rounded-2xl border border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400"
            id="emptyState">
            <i class="fa-regular fa-comments text-6xl mb-4 opacity-50"></i>
            <p class="font-medium">Chọn một cuộc trò chuyện để bắt đầu hỗ trợ</p>
        </div>

    </main>

    <script>
    let currentSession = '';
    let adminLastMessageCount = 0;

    function loadUsers() {
        fetch('../actions/process_chat.php?action=admin_fetch_users')
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    const list = document.getElementById('userList');
                    list.innerHTML = '';
                    res.data.forEach(u => {
                        const activeCls = u.session_id === currentSession ?
                            'bg-indigo-50 border-l-4 border-indigo-600' :
                            'hover:bg-slate-50 border-l-4 border-transparent';

                        const unreadBadge = u.unread > 0 ?
                            `<span class="bg-rose-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-full animate-pulse shadow-[0_0_8px_rgba(244,63,94,0.6)] shrink-0">${u.unread} MỚI</span>` :
                            '';

                        list.innerHTML += `
                            <div class="p-5 cursor-pointer ${activeCls} transition duration-200" onclick="selectUser('${u.session_id}', '${u.user_name}')">
                                <div class="flex justify-between items-center mb-1">
                                    <h4 class="font-bold text-slate-700 text-sm truncate">${u.user_name}</h4>
                                    <span class="text-[10px] text-slate-400 font-bold">${u.time}</span>
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <p class="text-xs ${u.unread > 0 ? 'text-slate-800 font-bold' : 'text-slate-500'} truncate line-clamp-1 flex-1">${u.last_message}</p>
                                    ${unreadBadge}
                                </div>
                            </div>`;
                    });
                }
            });
    }

    function selectUser(sessionId, userName) {
        currentSession = sessionId;
        adminLastMessageCount = 0; // Reset số đếm khi chuyển sang chat với người khác
        document.getElementById('emptyState').classList.add('hidden');
        document.getElementById('chatArea').classList.remove('hidden');
        document.getElementById('chatUserName').innerText = userName;
        document.getElementById('chatSessionId').innerText = sessionId.toUpperCase();
        document.getElementById('adminChatMessages').innerHTML = ''; // Xóa sạch khung chat cũ
        loadMessages();
    }

    function loadMessages() {
        if (!currentSession) return;
        fetch(`../actions/process_chat.php?action=admin_fetch_messages&session_id=${currentSession}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    const box = document.getElementById('adminChatMessages');

                    // Chỉ render lại và cuộn màn hình NẾU số lượng tin nhắn thay đổi HOẶC đây là lần mở chat đầu tiên
                    if (res.data.length !== adminLastMessageCount) {
                        adminLastMessageCount = res.data.length;
                        box.innerHTML = '';
                        res.data.forEach(m => {
                            const align = m.is_admin ? 'self-end' : 'self-start';
                            const bg = m.is_admin ? 'bg-indigo-600 text-white' :
                                'bg-white border border-slate-200 text-slate-700';
                            box.innerHTML += `
                                <div class="max-w-[70%] ${align}">
                                    <div class="px-4 py-2.5 rounded-2xl ${bg} text-sm inline-block break-words shadow-sm">${m.message}</div>
                                    <div class="text-[9px] text-slate-400 mt-1 ${m.is_admin ? 'text-right pr-1' : 'text-left pl-1'} font-bold">${m.time}</div>
                                </div>`;
                        });
                        // Dùng setTimeout để đảm bảo DOM đã render xong HTML thì mới thực hiện cuộn
                        setTimeout(() => {
                            box.scrollTo({
                                top: box.scrollHeight,
                                behavior: 'smooth'
                            });
                        }, 50);
                    }
                }
            });
    }

    document.getElementById('adminSendChat').addEventListener('click', () => {
        const msg = document.getElementById('adminChatInput').value.trim();
        if (msg && currentSession) {
            const fd = new FormData();
            fd.append('action', 'admin_send');
            fd.append('session_id', currentSession);
            fd.append('message', msg);
            fetch('../actions/process_chat.php', {
                method: 'POST',
                body: fd
            }).then(() => {
                document.getElementById('adminChatInput').value = '';
                loadMessages();
                loadUsers();
            });
        }
    });

    document.getElementById('adminChatInput').addEventListener('keypress', e => {
        if (e.key === 'Enter') document.getElementById('adminSendChat').click();
    });
    setInterval(() => {
        loadUsers();
        loadMessages();
    }, 3000);
    loadUsers();
    </script>
</body>

</html>