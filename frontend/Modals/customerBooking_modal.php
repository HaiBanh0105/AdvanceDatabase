<div id="customerBookingModal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] hidden items-center justify-center p-4">
    <div
        class="bg-white rounded-[2rem] w-full max-w-xl shadow-2xl overflow-hidden animate-in zoom-in duration-200 max-h-[90vh] flex flex-col">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
            <div>
                <h3 class="font-black text-lg text-slate-800 tracking-tight" id="cb_modal_title">Thông tin Đặt phòng
                </h3>
                <p class="text-xs text-indigo-600 font-bold mt-1">Bước 1/2: Khai báo lưu trú</p>
            </div>
            <button onclick="closeBookingModal()" class="text-slate-400 hover:text-slate-600"><i
                    class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form id="customerBookingForm" class="p-6 space-y-6 overflow-y-auto">
            <input type="hidden" name="type_id" id="cb_type_id">
            <input type="hidden" name="check_in" value="<?= htmlspecialchars($search_in) ?>">
            <input type="hidden" name="check_out" value="<?= htmlspecialchars($search_out) ?>">
            <input type="hidden" id="cb_price_per_day" value="0">
            <input type="hidden" name="promo_code" id="cb_promo_code_hidden">

            <!-- Khách đại diện (Không cho sửa) -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-5 relative">
                <span
                    class="absolute -top-3 left-4 bg-indigo-600 text-white px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">Khách
                    đại diện (Bạn)</span>
                <div class="grid grid-cols-2 gap-4 mt-2">
                    <div>
                        <label
                            class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1.5">Họ
                            và tên</label>
                        <input type="text" value="<?= htmlspecialchars($user_profile['full_name'] ?? '') ?>"
                            readonly
                            class="w-full px-4 py-2.5 bg-white/50 border border-indigo-100 rounded-xl text-sm font-bold text-slate-700 outline-none cursor-not-allowed">
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1.5">Số
                            CCCD</label>
                        <input type="text" value="<?= htmlspecialchars($user_profile['cccd'] ?? '') ?>" readonly
                            class="w-full px-4 py-2.5 bg-white/50 border border-indigo-100 rounded-xl text-sm font-bold text-slate-700 outline-none cursor-not-allowed"
                            placeholder="Chưa cập nhật">
                    </div>
                </div>
                <?php if (empty($user_profile['cccd'])): ?>
                    <p class="text-xs text-rose-500 font-bold mt-3"><i
                            class="fa-solid fa-triangle-exclamation mr-1"></i> Hồ sơ của bạn chưa có CCCD. Vui lòng cập
                        nhật trong mục Hồ sơ để được đặt phòng.</p>
                <?php endif; ?>
            </div>

            <!-- Khách đi cùng -->
            <?php if ($search_guests > 1): ?>
                <div class="space-y-4">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Thông tin khách đi cùng
                        (<?= $search_guests - 1 ?> người)</h4>
                    <?php for ($i = 1; $i < $search_guests; $i++): ?>
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1.5">Số CCCD
                                    *</label>
                                <input type="text" name="guests[<?= $i ?>][cccd]" id="g_cccd_<?= $i ?>" required
                                    pattern="\d{12}" title="Vui lòng nhập 12 số CCCD"
                                    onchange="checkCCCD_Customer(this, <?= $i ?>)" placeholder="12 chữ số"
                                    class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1.5">Họ và Tên
                                    *</label>
                                <input type="text" name="guests[<?= $i ?>][name]" id="g_name_<?= $i ?>" required
                                    class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-indigo-500 outline-none transition">
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

            <div class="pt-4 border-t border-slate-100 shrink-0">
                <button type="button" onclick="goToStep2()" <?= empty($user_profile['cccd']) ? 'disabled' : '' ?>
                    class="w-full bg-slate-900 text-white py-4 rounded-2xl font-bold shadow-lg hover:bg-slate-800 transition active:scale-95 disabled:bg-slate-300 disabled:cursor-not-allowed">
                    Tiếp tục Xác nhận
                </button>
            </div>
        </form>
    </div>
</div>