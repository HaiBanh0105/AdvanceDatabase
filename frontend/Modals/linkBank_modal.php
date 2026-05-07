<div id="linkBankModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight">Liên kết ngân hàng</h3>
            <button onclick="toggleModal('linkBankModal')" class="text-slate-400 hover:text-slate-600"><i
                    class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="../actions/process_link_bank.php" method="POST" class="p-8 space-y-5">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Ngân hàng
                    / Loại thẻ</label>
                <select name="provider" required
                    class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-semibold text-slate-700">
                    <option value="vietcombank">Vietcombank</option>
                    <option value="techcombank">Techcombank</option>
                    <option value="mb">MB Bank</option>
                    <option value="visa">Thẻ VISA Quốc tế</option>
                    <option value="mastercard">Thẻ MasterCard</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tên in
                    trên thẻ (Không dấu)</label>
                <input type="text" name="cardholder_name" required autocomplete="off" placeholder="NGUYEN VAN A"
                    class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm uppercase">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Số thẻ (16
                    số)</label>
                <input type="text" name="card_id" required pattern="\d{16}" maxlength="16"
                    placeholder="XXXX XXXX XXXX XXXX"
                    class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm tracking-widest font-mono">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Ngày
                        hết hạn</label>
                    <input type="month" name="expiry_date" required
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Mã CVV
                        (3 số)</label>
                    <input type="text" name="cvv" required pattern="\d{3}" maxlength="3" placeholder="XXX"
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm tracking-widest text-center font-mono">
                </div>
            </div>
            <button type="submit"
                class="w-full mt-4 px-4 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">Xác
                nhận liên kết</button>
        </form>
    </div>
</div>