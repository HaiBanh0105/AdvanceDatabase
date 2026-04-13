<div id="depositModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-sm shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-slate-800">Nạp tiền vào ví</h3>
            <button onclick="closeDepositModal()" class="text-slate-400 hover:text-slate-600"><i
                    class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="../actions/process_deposit_admin.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="user_id" id="deposit_user_id">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Khách hàng</p>
                <p id="deposit_user_name" class="font-bold text-slate-700 mb-4"></p>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Số tiền nạp (VNĐ)</label>
                <input type="number" name="amount" required min="10000" step="10000"
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none font-bold text-lg text-indigo-600">
            </div>
            <button type="submit"
                class="w-full mt-2 bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition">Xác
                nhận nạp</button>
        </form>
    </div>
</div>